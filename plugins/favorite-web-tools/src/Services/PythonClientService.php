<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Services;

use FavoriteCMS\Tools\Models\PythonService;
use RuntimeException;
use InvalidArgumentException;

class PythonClientService
{
    /**
     * @var array{final_request_url:string,http_status:int,response_content_type:string,sanitized_response:string,exception:?string}|null
     */
    protected static ?array $lastDiagnostics = null;

    public static function getLastDiagnostics(): ?array
    {
        return static::$lastDiagnostics;
    }

    public static function clearDiagnostics(): void
    {
        static::$lastDiagnostics = null;
    }

    /**
     * Send a request to a configured Python service.
     *
     * @param PythonService $service
     * @param string $endpoint (e.g. '/download/api' or '/api/process')
     * @param array $payload Query parameters for GET, or JSON body for POST
     * @param string $method 'GET' or 'POST'
     * @return array
     */
    public function sendRequest(PythonService $service, string $endpoint, array $payload = [], string $method = 'POST'): array
    {
        $baseUrl = rtrim($service->base_url, '/');
        $this->validateUrl($baseUrl);

        $cleanEndpoint = '/' . ltrim($endpoint, '/');
        $url = $baseUrl . $cleanEndpoint;

        $method = strtoupper($method);

        // For GET requests, append query parameters using RFC3986 URL encoding
        if ($method === 'GET' && !empty($payload)) {
            $separator = str_contains($url, '?') ? '&' : '?';
            $url .= $separator . http_build_query($payload, '', '&', PHP_QUERY_RFC3986);
        }

        $headers = [
            'Accept: application/json',
            'User-Agent: Favorite-Web-Tools/1.1',
        ];

        if ($method === 'POST') {
            $headers[] = 'Content-Type: application/json';
        }

        if ($service->auth_type === 'bearer' && !empty($service->api_key)) {
            $headers[] = 'Authorization: Bearer ' . $service->api_key;
        } elseif ($service->auth_type === 'api_key' && !empty($service->api_key)) {
            $headers[] = 'X-API-Key: ' . $service->api_key;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false, // Prevent open redirect SSRF bypass
            CURLOPT_TIMEOUT        => max(5, min(120, $service->timeout)),
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER     => $headers,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        } else {
            curl_setopt($ch, CURLOPT_HTTPGET, true);
        }

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $errno = curl_errno($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        $sanitizedBody = '';
        if (is_string($response)) {
            $sanitizedBody = ToolExecutionService::sanitizeMessageString(substr($response, 0, 500));
        }

        // Redact any query secrets from URL for diagnostics
        $safeUrl = preg_replace('/([?&](?:api_key|token|secret|password)=)[^&]+/i', '$1[REDACTED]', $url);

        static::$lastDiagnostics = [
            'final_request_url'     => $safeUrl,
            'http_status'           => $httpCode,
            'response_content_type' => $contentType ?: 'unknown',
            'sanitized_response'    => $sanitizedBody,
            'exception'             => null,
        ];

        if ($response === false || $errno !== 0) {
            $errText = $error ?: "error code {$errno}";
            static::$lastDiagnostics['exception'] = $errText;
            throw new RuntimeException("Python service connection failed: {$errText}");
        }

        if ($httpCode >= 400) {
            $decoded = json_decode((string)$response, true);
            $msg = $decoded['message'] ?? $decoded['error'] ?? "Service returned HTTP {$httpCode}";
            static::$lastDiagnostics['exception'] = "Service returned HTTP {$httpCode}: {$msg}";
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
    public function ping(PythonService $service, ?string $endpoint = null): array
    {
        try {
            $testEp = $endpoint ?: ($service->default_endpoint_path ?: '/health');
            $method = strtoupper($service->http_method ?? 'GET');

            $start = microtime(true);
            $res = $this->sendRequest($service, $testEp, [], $method);
            $duration = round((microtime(true) - $start) * 1000, 2);

            return [
                'success'       => true,
                'response_time' => $duration . ' ms',
                'data'          => $res,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error'   => ToolExecutionService::sanitizeMessageString($e->getMessage()),
            ];
        }
    }

    /**
     * Request wrapper with standard success/error return structure for tests and admin actions.
     */
    public function request(PythonService $service, string $method = 'GET', string $endpoint = '/', array $payload = []): array
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
                'error'   => ToolExecutionService::sanitizeMessageString($e->getMessage()),
                'status'  => 500,
            ];
        }
    }

    /**
     * Validate URL against SSRF, unauthorized protocols, private ranges, and metadata endpoints.
     *
     * @param string $url
     * @param bool $requireHttps If true, only HTTPS is permitted (for production external services)
     * @throws InvalidArgumentException
     */
    public function validateUrl(string $url, bool $requireHttps = false): void
    {
        $parts = parse_url($url);
        if ($parts === false || !isset($parts['scheme'])) {
            throw new InvalidArgumentException("Invalid Python service URL: '{$url}'");
        }

        $scheme = strtolower($parts['scheme']);
        if (!in_array($scheme, ['http', 'https'], true)) {
            throw new InvalidArgumentException("Unsupported URL protocol '{$scheme}'. Only http and https are permitted.");
        }

        if ($requireHttps && $scheme !== 'https') {
            throw new InvalidArgumentException("External Python services must use HTTPS in production. URL: '{$url}'");
        }

        if (!isset($parts['host'])) {
            throw new InvalidArgumentException("Invalid Python service URL: '{$url}' missing host.");
        }

        $host = strtolower($parts['host']);
        $cleanHost = trim($host, '[]');

        // 1. Block cloud metadata endpoints and names
        $metadataHosts = [
            '169.254.169.254',
            'metadata.google.internal',
            'instance-data',
            'metadata.azure.com',
            'metadata',
        ];
        if (in_array($cleanHost, $metadataHosts, true)) {
            throw new InvalidArgumentException("Connection to cloud metadata services is forbidden.");
        }

        // 2. Block localhost / loopback names
        if ($cleanHost === 'localhost' || $cleanHost === 'localhost.localdomain' || str_ends_with($cleanHost, '.localhost')) {
            throw new InvalidArgumentException("Connection to localhost/loopback address is forbidden.");
        }

        // 3. Direct IP validation (block link-local, loopback, private, and reserved ranges)
        if (filter_var($cleanHost, FILTER_VALIDATE_IP)) {
            if ($this->isForbiddenIp($cleanHost)) {
                throw new InvalidArgumentException("Connection to link-local/metadata/private IP addresses is forbidden.");
            }
        }

        // 4. DNS resolution validation (ensure hostname does not resolve to forbidden IP)
        if (!filter_var($cleanHost, FILTER_VALIDATE_IP)) {
            $resolvedIp = @gethostbyname($cleanHost);
            if ($resolvedIp !== $cleanHost && filter_var($resolvedIp, FILTER_VALIDATE_IP)) {
                if ($this->isForbiddenIp($resolvedIp)) {
                    throw new InvalidArgumentException("Resolved address '{$resolvedIp}' points to a forbidden cloud metadata or private network address.");
                }
            }
        }
    }

    /**
     * Check if an IP address belongs to loopback, link-local, private, or reserved ranges.
     */
    public function isForbiddenIp(string $ip): bool
    {
        // IPv4 loopback (127.0.0.0/8)
        if (str_starts_with($ip, '127.')) {
            return true;
        }

        // IPv4 link-local (169.254.0.0/16) and 0.0.0.0/8
        if (str_starts_with($ip, '169.254.') || str_starts_with($ip, '0.')) {
            return true;
        }

        // IPv6 loopback and link-local
        if ($ip === '::1' || str_starts_with(strtolower($ip), 'fe80:') || str_starts_with(strtolower($ip), 'fc00:') || str_starts_with(strtolower($ip), 'fd00:')) {
            return true;
        }

        // Check if IP is in private or reserved ranges
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                // If this is a private or reserved range
                return true;
            }
        }

        return false;
    }
}
