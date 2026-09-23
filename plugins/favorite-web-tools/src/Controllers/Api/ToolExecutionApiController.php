<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Controllers\Api;

use FavoriteCMS\Core\Application;
use FavoriteCMS\Core\Request;
use FavoriteCMS\Core\Response;
use FavoriteCMS\Models\User;
use FavoriteCMS\Tools\Normalizers\NormalizerResolver;
use FavoriteCMS\Tools\Repositories\FrontendDesignRepository;
use FavoriteCMS\Tools\Repositories\ToolRepository;
use FavoriteCMS\Tools\Services\ToolExecutionService;
use FavoriteCMS\Tools\Support\SafeTemplateRenderer;
use Throwable;

class ToolExecutionApiController
{
    protected Application $app;
    protected ToolExecutionService $executionService;

    public function __construct(Application $app, ToolExecutionService $executionService)
    {
        $this->app = $app;
        $this->executionService = $executionService;
    }

    public function execute(Request $request, string $slug): Response
    {
        $userId = $this->resolveCurrentUserId();
        $isAdmin = $this->isAdmin($userId);

        // Gather inputs: support both application/json and multipart/form-data
        $inputs = [];

        // Check JSON body
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
                $inputs = $decoded['inputs'] ?? $decoded;
            }
        }

        // If not JSON, check standard post inputs
        if (empty($inputs)) {
            $postInputs = $request->post('inputs');
            if (is_array($postInputs)) {
                $inputs = $postInputs;
            } elseif (is_string($postInputs)) {
                $decoded = json_decode($postInputs, true);
                if (is_array($decoded)) {
                    $inputs = $decoded;
                }
            }
        }

        if (empty($inputs)) {
            $allPost = !empty($_POST) ? $_POST : (method_exists($request, 'all') ? $request->all() : []);
            if (is_array($allPost)) {
                unset($allPost['_token'], $allPost['_fcms_json_payload']);
                if (isset($allPost['inputs']) && is_array($allPost['inputs'])) {
                    $inputs = $allPost['inputs'];
                } else {
                    $inputs = $allPost;
                }
            }
        }

        // Unwrap single nested inputs key if present
        if (isset($inputs['inputs']) && is_array($inputs['inputs']) && count($inputs) === 1) {
            $inputs = $inputs['inputs'];
        }

        // Merge uploaded files if present
        if (!empty($_FILES)) {
            foreach ($_FILES as $key => $fileData) {
                if (is_array($fileData) && isset($fileData['tmp_name']) && $fileData['error'] === UPLOAD_ERR_OK) {
                    $inputs[$key] = [
                        'name'     => $fileData['name'] ?? 'upload.bin',
                        'type'     => $fileData['type'] ?? 'application/octet-stream',
                        'size'     => $fileData['size'] ?? 0,
                        'tmp_name' => $fileData['tmp_name'],
                        'content'  => (string)@file_get_contents($fileData['tmp_name']),
                    ];
                }
            }
        }

        try {
            $rawUrlInput = $inputs['urls'] ?? ($inputs['video_url'] ?? ($inputs['url'] ?? ($inputs['text'] ?? null)));
            $isBulkExecution = false;

            if ($slug === 'favorite-media-downloader' && $rawUrlInput !== null && $this->app->has(\FavoriteCMS\Tools\Services\BulkMediaDownloadService::class)) {
                $bulkService = $this->app->make(\FavoriteCMS\Tools\Services\BulkMediaDownloadService::class);
                $parsed = $bulkService->parseBulkUrls($rawUrlInput);
                if ($parsed['valid_count'] > 1) {
                    $items = [];
                    foreach ($parsed['valid_urls'] as $vUrl) {
                        $rawFmt = $bulkService->discoverFormats($vUrl);
                        if (empty($rawFmt['error'])) {
                            $rawFmt['url'] = $vUrl;
                            $rawFmt['source_url'] = $vUrl;
                            $items[] = $rawFmt;
                        }
                    }
                    if (!empty($items)) {
                        $isBulkExecution = true;
                        $result = [
                            'success' => true,
                            'tool'    => ['name' => 'Favorite Media Downloader', 'slug' => $slug],
                            'data'    => [
                                'type'  => 'JSON',
                                'value' => [
                                    'is_bulk' => true,
                                    'items'   => $items,
                                ],
                                'data'  => [
                                    'is_bulk' => true,
                                    'items'   => $items,
                                ],
                            ],
                            'result'  => [
                                'type'  => 'JSON',
                                'value' => [
                                    'is_bulk' => true,
                                    'items'   => $items,
                                ],
                            ],
                        ];
                    }
                }
            }

            if (!$isBulkExecution) {
                $result = $this->executionService->execute($slug, $inputs, $userId > 0 ? $userId : null, $isAdmin);
            }
        } catch (Throwable $e) {
            $errMsg = ToolExecutionService::sanitizeErrorMessage($e);
            return Response::json([
                'success' => false,
                'error'   => [
                    'code'    => 'INTERNAL_EXECUTION_ERROR',
                    'message' => $errMsg,
                ],
                'message' => $errMsg,
            ], 500);
        }

        if (!$result['success']) {
            $errCode = $result['error']['code'] ?? 'PROCESSING_ERROR';
            $statusCode = match ($errCode) {
                'ACCESS_DENIED_AUTH'       => 401,
                'ACCESS_DENIED_MEMBERSHIP' => 403,
                'TOOL_NOT_FOUND'           => 404,
                'TOOL_UNAVAILABLE'         => 403,
                'VALIDATION_FAILED'        => 422,
                'TIMEOUT'                  => 504,
                'PYTHON_SERVICE_ERROR'     => 502,
                default                    => 400,
            };

            if (!isset($result['message']) && isset($result['error']['message'])) {
                $result['message'] = $result['error']['message'];
            }

            return Response::json($result, $statusCode);
        }

        // Resolve Tool and Assigned Frontend Design
        $toolRepo = $this->app->has(ToolRepository::class) ? $this->app->make(ToolRepository::class) : null;
        $tool = $toolRepo ? $toolRepo->findBySlug($slug) : null;
        $designSlug = $tool ? $tool->getFrontendDesignSlug() : null;

        $rawResult = $result['data']['value'] ?? $result['data']['data'] ?? $result['data'] ?? [];
        if (is_string($rawResult) && (str_starts_with(trim($rawResult), '{') || str_starts_with(trim($rawResult), '['))) {
            $decoded = json_decode($rawResult, true);
            if (is_array($decoded)) {
                $rawResult = $decoded;
            }
        }

        // Auto-fallback: If no explicit design was configured on tool, check if payload is media format
        if (empty($designSlug) && is_array($rawResult) && (!empty($rawResult['videos']) || !empty($rawResult['audios']))) {
            $designSlug = 'media-downloader-cards';
        }

        if (!empty($designSlug) && $designSlug !== 'default' && $this->app->has(FrontendDesignRepository::class)) {
            $designRepo = $this->app->make(FrontendDesignRepository::class);
            $design = $designRepo->findBySlug($designSlug);
            if ($design && $design->isActive()) {
                $resolver = $this->app->has(NormalizerResolver::class)
                    ? $this->app->make(NormalizerResolver::class)
                    : new NormalizerResolver();

                $normalizedData = $resolver->normalize(
                    is_array($rawResult) ? $rawResult : ['result' => $rawResult],
                    $designSlug
                );

                $renderedHtml = SafeTemplateRenderer::render($design->getTemplateHtml(), $normalizedData);

                $result['design'] = [
                    'slug'          => $design->getSlug(),
                    'name'          => $design->getName(),
                    'template_html' => $design->getTemplateHtml(),
                    'css'           => $design->getCssContent(),
                    'js'            => $design->isJsEnabled() ? $design->getJsContent() : '',
                    'js_enabled'    => $design->isJsEnabled(),
                ];
                $result['rendered_html'] = $renderedHtml;
                $result['normalized_data'] = $normalizedData;
            }
        }

        if (!isset($result['data']) && isset($result['result'])) {
            $result['data'] = $result['result'];
        } elseif (!isset($result['result']) && isset($result['data'])) {
            $result['result'] = $result['data'];
        }

        return Response::json($result, 200);
    }

    protected function resolveCurrentUserId(): int
    {
        if (isset($GLOBALS['_test_current_user']) && isset($GLOBALS['_test_current_user']->id)) {
            return (int)$GLOBALS['_test_current_user']->id;
        }
        return (int)($_SESSION['auth_user_id'] ?? 0);
    }

    protected function isAdmin(int $userId): bool
    {
        if (isset($GLOBALS['_test_current_user'])) {
            $u = $GLOBALS['_test_current_user'];
            if (method_exists($u, 'can') && $u->can('manage_options')) {
                return true;
            }
        }

        if ($userId <= 0) {
            return false;
        }

        if (class_exists(User::class)) {
            try {
                $user = User::find($userId);
                if ($user && method_exists($user, 'can') && $user->can('manage_options')) {
                    return true;
                }
            } catch (Throwable) {
            }
        }

        if (function_exists('current_user_can')) {
            try {
                return current_user_can('manage_options');
            } catch (Throwable) {
            }
        }

        return false;
    }
}

