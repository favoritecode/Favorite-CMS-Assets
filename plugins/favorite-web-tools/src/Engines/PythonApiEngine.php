<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Engines;

use FavoriteCMS\Tools\Contracts\EngineInterface;
use FavoriteCMS\Tools\Models\Tool;
use FavoriteCMS\Tools\Repositories\PythonServiceRepository;
use FavoriteCMS\Tools\Services\PythonClientService;
use FavoriteCMS\Tools\Support\EngineType;
use FavoriteCMS\Tools\Support\ResultType;
use RuntimeException;
use InvalidArgumentException;

class PythonApiEngine implements EngineInterface
{
    protected static ?array $lastExecutionDiagnostics = null;

    public static function getLastDiagnostics(): ?array
    {
        return static::$lastExecutionDiagnostics;
    }

    public static function clearDiagnostics(): void
    {
        static::$lastExecutionDiagnostics = null;
    }

    protected ?PythonServiceRepository $serviceRepo = null;
    protected PythonClientService $client;

    public function __construct(?PythonServiceRepository $serviceRepo = null, ?PythonClientService $client = null)
    {
        $this->serviceRepo = $serviceRepo;
        $this->client = $client ?? new PythonClientService();
    }

    public function canHandle(Tool $tool): bool
    {
        return $tool->engine === EngineType::PYTHON_API;
    }

    public function validate(Tool $tool, array $inputs): array
    {
        if ($this->serviceRepo === null) {
            return ['No Python service repository configured.'];
        }

        $serviceId = (int)($tool->python_service_id ?: ($tool->configuration['python_service_id'] ?? 0));
        $service = null;
        if ($serviceId <= 0) {
            // Check if any active service exists
            $services = $this->serviceRepo->getAll(true);
            if (empty($services)) {
                return ['No active Python API service is configured for this tool.'];
            }
            $service = $services[0];
        } else {
            $service = $this->serviceRepo->findById($serviceId);
            if (!$service || !$service->isActive()) {
                return ['Configured Python service is unavailable or disabled.'];
            }
        }

        // Validate that endpoint does not attempt to override host to an arbitrary external domain
        $endpoint = (string)($tool->python_endpoint ?: ($tool->configuration['endpoint'] ?? ''));
        if (preg_match('#^https?://#i', $endpoint) && $service) {
            $endpointHost = strtolower((string)parse_url($endpoint, PHP_URL_HOST));
            $serviceHost  = strtolower((string)parse_url($service->base_url, PHP_URL_HOST));
            if ($endpointHost !== '' && $endpointHost !== $serviceHost) {
                return ["The endpoint host '{$endpointHost}' does not match service host '{$serviceHost}'. Host override is forbidden."];
            }
        }

        return [];
    }

    public function execute(Tool $tool, array $inputs): array
    {
        if ($this->serviceRepo === null) {
            throw new RuntimeException('No Python service repository configured.');
        }

        $serviceId = (int)($tool->python_service_id ?: ($tool->configuration['python_service_id'] ?? 0));
        $service = null;

        if ($serviceId > 0) {
            $service = $this->serviceRepo->findById($serviceId);
        } else {
            $services = $this->serviceRepo->getAll(true);
            if (!empty($services)) {
                $service = $services[0];
            }
        }

        if (!$service || !$service->isActive()) {
            throw new RuntimeException('No active Python API service available.');
        }

        // Determine endpoint
        $endpoint = (string)($tool->python_endpoint ?: ($tool->configuration['endpoint'] ?? ($service->default_endpoint_path ?: '/download/api')));

        // Backward compatibility: If endpoint was stored as full URL matching service base_url
        if (preg_match('#^https?://#i', $endpoint)) {
            $endpointHost = strtolower((string)parse_url($endpoint, PHP_URL_HOST));
            $serviceHost  = strtolower((string)parse_url($service->base_url, PHP_URL_HOST));
            if ($endpointHost !== '' && $endpointHost !== $serviceHost) {
                throw new RuntimeException("Endpoint host mismatch with configured service host.");
            }
            $path = parse_url($endpoint, PHP_URL_PATH) ?? '/';
            $query = parse_url($endpoint, PHP_URL_QUERY);
            $endpoint = $path . ($query ? '?' . $query : '');
        }

        // Determine HTTP method
        $method = strtoupper((string)($tool->configuration['http_method'] ?? ($service->http_method ?? 'GET')));

        // Normalize generic UI keys to video_url if video_url is not explicitly provided
        if (!isset($inputs['video_url']) || trim((string)$inputs['video_url']) === '') {
            foreach (['url', 'text', 'input', 'content', 'input_content'] as $k) {
                if (isset($inputs[$k]) && is_string($inputs[$k]) && trim($inputs[$k]) !== '') {
                    $inputs['video_url'] = trim($inputs[$k]);
                    break;
                }
            }
        }

        // Map inputs to query/request parameters
        $mappedInputs = [];
        if (isset($tool->configuration['param_mapping']) && is_array($tool->configuration['param_mapping'])) {
            foreach ($tool->configuration['param_mapping'] as $from => $to) {
                if (isset($inputs[$from]) && $inputs[$from] !== '') {
                    $mappedInputs[$to] = $inputs[$from];
                }
            }
        } elseif (isset($inputs['video_url']) && $inputs['video_url'] !== '') {
            $mappedInputs['url'] = $inputs['video_url'];
        }

        if (isset($inputs['url']) && $inputs['url'] !== '' && !isset($mappedInputs['url'])) {
            $mappedInputs['url'] = $inputs['url'];
        }

        // If no explicit mapping occurred, fall back to inputs
        if (empty($mappedInputs)) {
            $mappedInputs = $inputs;
        }

        // For GET requests, mapped inputs are sent as URL query parameters
        $payload = ($method === 'GET') ? $mappedInputs : [
            'tool'      => $tool->slug,
            'inputs'    => $mappedInputs,
            'timestamp' => time(),
        ];

        try {
            $response = $this->client->sendRequest($service, $endpoint, $payload, $method);
        } catch (\Throwable $e) {
            $clientDiag = PythonClientService::getLastDiagnostics() ?? [];
            static::$lastExecutionDiagnostics = [
                'tool_slug'                     => $tool->slug,
                'python_service_id'             => $service->id,
                'base_url'                      => $service->base_url,
                'endpoint_path'                 => $endpoint,
                'http_method'                   => $method,
                'input_variable_names'          => array_keys($inputs),
                'mapped_query_parameter_names'  => array_keys($mappedInputs),
                'final_request_url'             => $clientDiag['final_request_url'] ?? null,
                'http_status'                   => $clientDiag['http_status'] ?? 0,
                'response_content_type'         => $clientDiag['response_content_type'] ?? 'unknown',
                'sanitized_response_body'       => $clientDiag['sanitized_response'] ?? '',
                'exception'                     => get_class($e) . ': ' . $e->getMessage(),
            ];
            throw $e;
        }

        $clientDiag = PythonClientService::getLastDiagnostics() ?? [];
        static::$lastExecutionDiagnostics = [
            'tool_slug'                    => $tool->slug,
            'python_service_id'            => $service->id,
            'base_url'                     => $service->base_url,
            'endpoint_path'                => $endpoint,
            'http_method'                  => $method,
            'input_variable_names'         => array_keys($inputs),
            'mapped_query_parameter_names' => array_keys($mappedInputs),
            'final_request_url'            => $clientDiag['final_request_url'] ?? null,
            'http_status'                  => $clientDiag['http_status'] ?? 200,
            'response_content_type'        => $clientDiag['response_content_type'] ?? 'application/json',
            'sanitized_response_body'      => $clientDiag['sanitized_response'] ?? '',
            'exception'                    => null,
        ];

        // If the external service returned an error structure (e.g. {"error": "..."}) even on HTTP 200
        if (isset($response['error']) && !isset($response['data']) && !isset($response[0])) {
            return [
                'success' => false,
                'error'   => (string)$response['error'],
            ];
        }

        // Normalize response
        if (isset($response['data'])) {
            $data = $response['data'];
            $type = is_string($data) ? ResultType::TEXT : ResultType::JSON;
            return [
                'success' => true,
                'type'    => $response['type'] ?? $type,
                'value'   => $data,
                'data'    => $data,
                'meta'    => $response['meta'] ?? ['service' => $service->name, 'endpoint' => $endpoint],
            ];
        }

        return [
            'success' => true,
            'type'    => ResultType::JSON,
            'value'   => $response,
            'data'    => $response,
            'meta'    => [
                'service'  => $service->name,
                'endpoint' => $endpoint,
            ],
        ];
    }
}
