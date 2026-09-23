<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Controllers\Api;

use FavoriteCMS\Core\Application;
use FavoriteCMS\Core\Request;
use FavoriteCMS\Core\Response;
use FavoriteCMS\Tools\Normalizers\MediaResultNormalizer;
use FavoriteCMS\Tools\Services\BulkMediaDownloadService;
use Throwable;

class BulkMediaApiController
{
    protected Application $app;
    protected BulkMediaDownloadService $bulkService;
    protected MediaResultNormalizer $normalizer;

    public function __construct(
        Application $app,
        BulkMediaDownloadService $bulkService,
        ?MediaResultNormalizer $normalizer = null
    ) {
        $this->app = $app;
        $this->bulkService = $bulkService;
        $this->normalizer = $normalizer ?? new MediaResultNormalizer();
    }

    /**
     * Parse, validate, and deduplicate bulk media URLs from input text or array.
     *
     * POST /api/tools/media-downloader/parse-urls
     */
    public function parseUrls(Request $request): Response
    {
        $input = $this->resolveRequestInput($request, ['urls', 'text', 'input', 'video_url']);
        if ($input === null || (is_string($input) && trim($input) === '') || (is_array($input) && empty($input))) {
            return Response::json([
                'success' => false,
                'error'   => 'Please provide one or more media URLs to parse.',
            ], 422);
        }

        $maxUrls = (int)($request->get('max_urls') ?: BulkMediaDownloadService::DEFAULT_MAX_URLS);
        if ($maxUrls <= 0 || $maxUrls > 200) {
            $maxUrls = BulkMediaDownloadService::DEFAULT_MAX_URLS;
        }

        try {
            $parsed = $this->bulkService->parseBulkUrls($input, $maxUrls);
            return Response::json([
                'success' => true,
                ...$parsed,
            ], 200);
        } catch (Throwable $e) {
            return Response::json([
                'success' => false,
                'error'   => 'Error parsing URLs: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Retrieve and normalize format options for a single media URL.
     *
     * GET or POST /api/tools/media-downloader/formats
     */
    public function getFormats(Request $request): Response
    {
        $url = (string)($request->get('url') ?: $this->resolveRequestInput($request, ['url', 'video_url', 'source_url']) ?: '');
        $url = trim($url);

        if ($url === '') {
            return Response::json([
                'success' => false,
                'error'   => 'Missing required "url" parameter.',
            ], 422);
        }

        $validationErr = $this->bulkService->validateMediaUrl($url);
        if ($validationErr !== null) {
            return Response::json([
                'success' => false,
                'error'   => $validationErr,
            ], 422);
        }

        try {
            $rawFormats = $this->bulkService->discoverFormats($url);
            if (!empty($rawFormats['error'])) {
                return Response::json([
                    'success' => false,
                    'error'   => (string)$rawFormats['error'],
                ], 400);
            }

            // Attach original URL to raw formats payload
            $rawFormats['url'] = $url;
            $rawFormats['source_url'] = $url;

            $normalized = $this->normalizer->normalizeItem($rawFormats, 0);

            return Response::json([
                'success' => true,
                'data'    => $normalized,
                'item'    => $normalized,
            ], 200);
        } catch (Throwable $e) {
            return Response::json([
                'success' => false,
                'error'   => 'Failed to discover media formats: ' . $e->getMessage(),
            ], 502);
        }
    }

    /**
     * Start a download job for a media stream.
     *
     * POST /api/tools/media-downloader/start-job
     */
    public function startJob(Request $request): Response
    {
        $rawUrl = (string)($this->resolveRequestInput($request, ['url', 'video_url', 'source_url']) ?: '');
        $url = trim($rawUrl);

        if ($url === '') {
            return Response::json([
                'success' => false,
                'error'   => 'Missing required media "url".',
            ], 422);
        }

        $format = $this->resolveRequestInput($request, ['format', 'formatId', 'format_id']);
        $formatId = is_string($format) || is_numeric($format) ? (string)$format : null;

        $type = (string)($this->resolveRequestInput($request, ['type']) ?: 'video');
        $hasAudioRaw = $this->resolveRequestInput($request, ['hasAudio', 'has_audio']);
        $hasAudio = ($hasAudioRaw === null || $hasAudioRaw === true || $hasAudioRaw === '1' || $hasAudioRaw === 1);

        $compatRaw = $this->resolveRequestInput($request, ['compat', 'convert', 'compatibility']);
        $compat = ($compatRaw === true || $compatRaw === '1' || $compatRaw === 1);

        try {
            $result = $this->bulkService->startDownloadJob($url, $formatId, $type, $hasAudio, $compat);
            if (!$result['success']) {
                return Response::json([
                    'success' => false,
                    'error'   => $result['error'] ?? 'Could not start download job.',
                ], 400);
            }

            return Response::json([
                'success' => true,
                'jobId'   => $result['jobId'],
            ], 200);
        } catch (Throwable $e) {
            return Response::json([
                'success' => false,
                'error'   => 'Download initiation failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Poll the progress of an active download job.
     *
     * GET /api/tools/media-downloader/job-status/{jobId}
     */
    public function getJobStatus(Request $request, string $jobId): Response
    {
        $cleanJobId = trim($jobId);
        if ($cleanJobId === '') {
            return Response::json([
                'status' => 'error',
                'error'  => 'Missing jobId parameter.',
            ], 422);
        }

        try {
            $status = $this->bulkService->getJobStatus($cleanJobId);
            return Response::json($status, 200);
        } catch (Throwable $e) {
            return Response::json([
                'status' => 'error',
                'error'  => 'Could not poll job status: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Stream or redirect to completed job binary file.
     *
     * GET /api/tools/media-downloader/job-file/{jobId}
     */
    public function getJobFile(Request $request, string $jobId): Response
    {
        $cleanJobId = trim($jobId);
        if ($cleanJobId === '') {
            return Response::json([
                'status' => 'error',
                'error'  => 'Missing jobId parameter.',
            ], 422);
        }

        $fileUrl = $this->bulkService->getJobFileUrl($cleanJobId);

        // Direct 302 redirect allows browser to stream directly with native resume/speed
        return Response::redirect($fileUrl, 302);
    }

    /**
     * Helper to resolve request parameters from JSON or Form body.
     */
    protected function resolveRequestInput(Request $request, array $keys): mixed
    {
        // 1. Check direct request GET/POST
        foreach ($keys as $k) {
            $val = $request->get($k) ?? $request->post($k);
            if ($val !== null && $val !== '') {
                return $val;
            }
        }

        // 2. Check JSON payload body
        $rawJson = '';
        if (method_exists($request, 'getContent')) {
            $rawJson = (string)$request->getContent();
        } elseif (isset($GLOBALS['_test_raw_input'])) {
            $rawJson = (string)$GLOBALS['_test_raw_input'];
        } else {
            $rawJson = (string)@file_get_contents('php://input');
        }

        if ($rawJson !== '') {
            $decoded = json_decode($rawJson, true);
            if (is_array($decoded)) {
                foreach ($keys as $k) {
                    if (isset($decoded[$k]) && $decoded[$k] !== '') {
                        return $decoded[$k];
                    }
                }
            }
        }

        // 3. Fallback to $_POST
        foreach ($keys as $k) {
            if (isset($_POST[$k]) && $_POST[$k] !== '') {
                return $_POST[$k];
            }
        }

        return null;
    }
}

