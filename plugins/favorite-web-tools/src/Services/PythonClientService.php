<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Services;

use FavoriteCMS\Tools\Models\PythonService;
use RuntimeException;
use InvalidArgumentException;

class PythonClientService
{
    /**
     * Send a request to a configured Python service.
     *
     * @param PythonService $service
     * @param string $endpoint (e.g. '/api/process')
     * @param array $payload
     * @param string $method
     * @return array
     */
    public function sendRequest(PythonService $service, string $endpoint, array $payload = [], string $method = 'POST'): array
    {
        $baseUrl = rtrim($service->base_url, '/');
        $this->validateUrl($baseUrl);

        $url = $baseUrl . '/' . ltrim($endpoint, '/');

        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
            'User-Agent: Favorite-Web-Tools/1.0',
        ];

        if ($service->auth_type === 'bearer' && !empty($service->api_key)) {
            $headers[] = 'Authorization: Bearer ' . $service->api_key;
        } elseif ($service->auth_type === 'api_key' && !empty($service->api_key)) {
            $headers[] = 'X-API-Key: ' . $service->api_key;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false, // Prevent open redirect SSRF
            CURLOPT_TIMEOUT        => max(5, min(120, $service->timeout)),
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER     => $headers,
        ]);

        if (strtoupper($method) === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        }

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $errno = curl_errno($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $errno !== 0) {
            throw new RuntimeException("Python service connection failed: " . ($error ?: "error code {$errno}"));
        }

        if ($httpCode >= 400) {
            $decoded = json_decode((string)$response, true);
            $msg = $decoded['message'] ?? $decoded['error'] ?? "Service returned HTTP {$httpCode}";
            throw new RuntimeException("Python service error: {$msg}");
        }

        $decoded = json_decode((string)$response, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        return [
            'raw_response' => (string)$response,
            'http_code'    => $httpCode,
        ];
    }

    /**
     * Ping a service to test its connection and health.
     */
    public function ping(PythonService $service): array
    {
        try {
            $start = microtime(true);
            $res = $this->sendRequest($service, '/health', [], 'GET');
            $duration = round((microtime(true) - $start) * 1000, 2);

            return [
                'success'       => true,
                'response_time' => $duration . ' ms',
                'data'          => $res,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error'   => $e->getMessage(),
            ];
        }
    }

    /**
     * Request wrapper with standard success/error return structure for tests and admin actions.
     */
    public function request(PythonService $service, string $method = 'POST', string $endpoint = '/', array $payload = []): array
    {
        try {
            $data = $this->sendRequest($service, $endpoint, $payload, $method);
            return [
                'success' => true,
                'data'    => $data,
                'status'  => 200,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error'   => $e->getMessage(),
                'status'  => 500,
            ];
        }
    }

    /**
     * Validate URL against SSRF and unauthorized protocols.
     */
    public function validateUrl(string $url): void
    {
        $parts = parse_url($url);
        if ($parts === false || !isset($parts['scheme'])) {
            throw new InvalidArgumentException("Invalid Python service URL: '{$url}'");
        }

        $scheme = strtolower($parts['scheme']);
        if (!in_array($scheme, ['http', 'https'], true)) {
            throw new InvalidArgumentException("Unsupported URL protocol '{$scheme}'. Only http and https are permitted.");
        }

        if (!isset($parts['host'])) {
            throw new InvalidArgumentException("Invalid Python service URL: '{$url}' missing host.");
        }

        $host = strtolower($parts['host']);
        // Strip square brackets if IPv6
        $cleanHost = trim($host, '[]');

        // Block AWS/GCP/Azure link-local metadata endpoints and names
        if ($cleanHost === '169.254.169.254' || $cleanHost === 'metadata.google.internal' || $cleanHost === 'instance-data') {
            throw new InvalidArgumentException("Connection to cloud metadata services is forbidden.");
        }

        // Direct IP check for link-local / metadata addresses
        if (filter_var($cleanHost, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            if (str_starts_with($cleanHost, '169.254.') || str_starts_with($cleanHost, '0.0.')) {
                throw new InvalidArgumentException("Connection to link-local/metadata IP addresses is forbidden.");
            }
        }

        // Resolve DNS and verify that resolved IP does not point to forbidden metadata or link-local address
        if (!filter_var($cleanHost, FILTER_VALIDATE_IP)) {
            $resolvedIp = @gethostbyname($cleanHost);
            if ($resolvedIp !== $cleanHost && filter_var($resolvedIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                if (str_starts_with($resolvedIp, '169.254.') || str_starts_with($resolvedIp, '0.0.')) {
                    throw new InvalidArgumentException("Resolved address '{$resolvedIp}' points to a forbidden cloud metadata service.");
                }
            }
        }
    }
}
