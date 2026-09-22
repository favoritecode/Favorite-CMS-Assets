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

        $serviceId = (int)($tool->configuration['python_service_id'] ?? 0);
        if ($serviceId <= 0) {
            // Check if any active service exists
            $services = $this->serviceRepo->getAll(true);
            if (empty($services)) {
                return ['No active Python API service is configured for this tool.'];
            }
        } else {
            $service = $this->serviceRepo->findById($serviceId);
            if (!$service || !$service->isActive()) {
                return ['Configured Python service is unavailable or disabled.'];
            }
        }

        return [];
    }

    public function execute(Tool $tool, array $inputs): array
    {
        if ($this->serviceRepo === null) {
            throw new RuntimeException('No Python service repository configured.');
        }

        $serviceId = (int)($tool->configuration['python_service_id'] ?? 0);
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

        $endpoint = (string)($tool->configuration['endpoint'] ?? '/process');
        $payload = [
            'tool'      => $tool->slug,
            'inputs'    => $inputs,
            'timestamp' => time(),
        ];

        $response = $this->client->sendRequest($service, $endpoint, $payload);

        // Normalize response
        if (isset($response['data'])) {
            $data = $response['data'];
            $type = is_string($data) ? ResultType::TEXT : ResultType::JSON;
            return [
                'type'  => $response['type'] ?? $type,
                'value' => $data,
                'meta'  => $response['meta'] ?? ['service' => $service->name],
            ];
        }

        return [
            'type'  => ResultType::JSON,
            'value' => $response,
            'meta'  => [
                'service'  => $service->name,
                'endpoint' => $endpoint,
            ],
        ];
    }
}
