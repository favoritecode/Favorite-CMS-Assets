<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Services;

use FavoriteCMS\Tools\Models\PythonService;
use FavoriteCMS\Tools\Repositories\PythonServiceRepository;
use InvalidArgumentException;
use RuntimeException;

class BulkMediaDownloadService
{
    public const DEFAULT_MAX_URLS = 50;
    public const DEFAULT_CONCURRENCY = 3;

    protected ?PythonServiceRepository $serviceRepo;
    protected ?PythonClientService $client;
    protected string $defaultBaseUrl;

    public function __construct(
        ?PythonServiceRepository $serviceRepo = null,
        ?PythonClientService $client = null,
        string $defaultBaseUrl = 'https://server.favoriteweb.net'
    ) {
        $this->serviceRepo = $serviceRepo;
        $this->client = $client;
        $this->defaultBaseUrl = rtrim($defaultBaseUrl, '/');
    }

    /**
     * Parse and validate bulk media URLs.
     *
     * @param string|array $input Textarea content (one per line/space separated) or array of URLs
     * @param int $maxUrls Maximum allowed URLs
     * @return array{
     *     total_input_count: int,
     *     valid_count: int,
     *     invalid_count: int,
     *     duplicate_count: int,
     *     valid_urls: string[],
     *     invalid_urls: array<array{url: string, reason: string}>,
     *     duplicate_urls: string[]
     * }
     */
    public function parseBulkUrls(string|array $input, int $maxUrls = self::DEFAULT_MAX_URLS): array
    {
        $rawList = [];
        if (is_array($input)) {
            foreach ($input as $item) {
                if (is_string($item)) {
                    $rawList[] = $item;
                }
            }
        } else {
            // Split by newlines, carriage returns, or multiple spaces
            $lines = preg_split('/[\r\n\t]+/', trim($input));
            if (is_array($lines)) {
                foreach ($lines as $line) {
                    $tokens = preg_split('/\s+/', trim($line));
                    if (is_array($tokens)) {
                        foreach ($tokens as $tok) {
                            $t = trim($tok);
                            if ($t !== '') {
                                $rawList[] = $t;
                            }
                        }
                    }
                }
            }
        }

        $totalInputCount = count($rawList);
        $validUrls = [];
        $invalidUrls = [];
        $duplicateUrls = [];
        $seen = [];

        foreach ($rawList as $rawUrl) {
            $normalized = $this->normalizeUrl($rawUrl);

            // Validation check
            $validationError = $this->validateMediaUrl($normalized);
            if ($validationError !== null) {
                $invalidUrls[] = [
                    'url'    => $rawUrl,
                    'reason' => $validationError,
                ];
                continue;
            }

            // Deduplication check
            $dedupKey = strtolower($normalized);
            if (isset($seen[$dedupKey])) {
                $duplicateUrls[] = $rawUrl;
                continue;
            }

            if (count($validUrls) >= $maxUrls) {
                $invalidUrls[] = [
                    'url'    => $rawUrl,
                    'reason' => "Maximum URL limit ({$maxUrls}) reached.",
                ];
                continue;
            }

            $seen[$dedupKey] = true;
            $validUrls[] = $normalized;
        }

        return [
            'total_input_count' => $totalInputCount,
            'valid_count'       => count($validUrls),
            'invalid_count'     => count($invalidUrls),
            'duplicate_count'   => count($duplicateUrls),
            'valid_urls'        => $validUrls,
            'invalid_urls'      => $invalidUrls,
            'duplicate_urls'    => $duplicateUrls,
        ];
    }

    /**
     * Normalize URL schemes and clean tracking parameters for social links.
     */
    public function normalizeUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        // Add scheme if missing
        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . ltrim($url, '/');
        }

        try {
            $parts = parse_url($url);
            if (!$parts || empty($parts['host'])) {
                return $url;
            }

            $host = strtolower($parts['host']);

            // Handle Instagram redirect wrappers: l.instagram.com/?u=...
            if ($host === 'l.instagram.com' && !empty($parts['query'])) {
                parse_str($parts['query'], $query);
                if (!empty($query['u']) && is_string($query['u'])) {
                    return $this->normalizeUrl($query['u']);
                }
            }

            // Normalize Instagram reel/p/tv URLs
            if (str_ends_with($host, 'instagram.com') && !empty($parts['path'])) {
                if (preg_match('#^/(?:reels?|p|tv)/([A-Za-z0-9_-]+)#', $parts['path'], $m)) {
                    $type = str_starts_with($parts['path'], '/reel') ? 'reel' : 'p';
                    return "https://www.instagram.com/{$type}/{$m[1]}/";
                }
            }
        } catch (\Throwable) {
            // Keep original if parsing fails
        }

        return $url;
    }

    /**
     * Validate URL syntax and check SSRF guardrails.
     *
     * @return string|null Null if valid, error description string if invalid
     */
    public function validateMediaUrl(string $url): ?string
    {
        if ($url === '') {
            return 'URL cannot be empty.';
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return 'Invalid URL format.';
        }

        $scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));
        if ($scheme !== 'http' && $scheme !== 'https') {
            return 'Only HTTP and HTTPS URLs are permitted.';
        }

        $host = strtolower((string)parse_url($url, PHP_URL_HOST));
        if ($host === '') {
            return 'URL must contain a valid domain name.';
        }

        // SSRF protections: prevent access to localhost or internal network
        if ($host === 'localhost' || $host === '127.0.0.1' || $host === '::1' || $host === '0.0.0.0') {
            return 'Localhost and loopback URLs are prohibited.';
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            if (!filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return 'Private and reserved IP addresses are prohibited.';
            }
        }

        return null;
    }

    /**
     * Resolve the base URL of the remote downloader service.
     */
    public function resolveBaseUrl(): string
    {
        if ($this->serviceRepo !== null) {
            $service = $this->serviceRepo->findBySlug('favorite-media-downloader-api');
            if ($service && !empty($service->base_url)) {
                return rtrim($service->base_url, '/');
            }
        }
        return $this->defaultBaseUrl;
    }

    /**
     * Query media formats for a URL from the remote API.
     *
     * @param string $mediaUrl
     * @return array
     */
    public function discoverFormats(string $mediaUrl): array
    {
        $validationErr = $this->validateMediaUrl($mediaUrl);
        if ($validationErr !== null) {
            return ['error' => $validationErr];
        }

        $baseUrl = $this->resolveBaseUrl();
        $endpoint = $baseUrl . '/download/api?url=' . rawurlencode($mediaUrl);

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'User-Agent: Favorite-Web-Tools-Bulk/1.3',
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr !== '' || $httpCode !== 200 || !is_string($response)) {
            return [
                'error'     => 'Failed to retrieve media formats (HTTP ' . $httpCode . ')',
                'http_code' => $httpCode,
            ];
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            return ['error' => 'Invalid JSON response from format provider.'];
        }

        return $decoded;
    }

    /**
     * Create a download job via POST /download/start-download.
     *
     * @param string $mediaUrl
     * @param string|null $formatId
     * @param string $type 'video' or 'audio'
     * @param bool $hasAudio
     * @param bool $convert If true, sets compat=1 for compatibility conversion
     * @return array{success: bool, jobId?: string, error?: string}
     */
    public function startDownloadJob(
        string $mediaUrl,
        ?string $formatId = null,
        string $type = 'video',
        bool $hasAudio = true,
        bool $convert = false
    ): array {
        $validationErr = $this->validateMediaUrl($mediaUrl);
        if ($validationErr !== null) {
            return ['success' => false, 'error' => $validationErr];
        }

        $payload = [
            'url'  => $mediaUrl,
            'type' => strtolower($type),
        ];

        if ($formatId !== null && trim($formatId) !== '') {
            $payload['format'] = trim($formatId);
        }
        if ($hasAudio) {
            $payload['hasAudio'] = '1';
        }
        if ($convert) {
            $payload['compat'] = '1';
        }

        $baseUrl = $this->resolveBaseUrl();
        $endpoint = $baseUrl . '/download/start-download';

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
                'User-Agent: Favorite-Web-Tools-Bulk/1.3',
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr !== '' || $httpCode !== 200 || !is_string($response)) {
            return [
                'success' => false,
                'error'   => 'Could not initiate download job (HTTP ' . $httpCode . ')',
            ];
        }

        $data = json_decode($response, true);
        if (!is_array($data) || empty($data['jobId'])) {
            return [
                'success' => false,
                'error'   => $data['error'] ?? 'No jobId received from remote download provider.',
            ];
        }

        return [
            'success' => true,
            'jobId'   => (string)$data['jobId'],
        ];
    }

    /**
     * Poll download job status via GET /download/job-status/{JOB_ID}.
     *
     * @param string $jobId
     * @return array
     */
    public function getJobStatus(string $jobId): array
    {
        // Sanitize jobId: alphanumeric only
        $safeJobId = preg_replace('/[^a-zA-Z0-9_-]/', '', $jobId);
        if ($safeJobId === '') {
            return ['status' => 'error', 'error' => 'Invalid job ID format.'];
        }

        $baseUrl = $this->resolveBaseUrl();
        $endpoint = $baseUrl . '/download/job-status/' . rawurlencode($safeJobId);

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'Cache-Control: no-store',
                'User-Agent: Favorite-Web-Tools-Bulk/1.3',
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr !== '' || $httpCode !== 200 || !is_string($response)) {
            return [
                'status' => 'error',
                'error'  => 'Status query failed (HTTP ' . $httpCode . ')',
            ];
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            return ['status' => 'error', 'error' => 'Malformed status response.'];
        }

        return $data;
    }

    /**
     * Build the download file URL for a completed job.
     */
    public function getJobFileUrl(string $jobId): string
    {
        $safeJobId = preg_replace('/[^a-zA-Z0-9_-]/', '', $jobId);
        return $this->resolveBaseUrl() . '/download/job-file/' . rawurlencode($safeJobId);
    }
}

