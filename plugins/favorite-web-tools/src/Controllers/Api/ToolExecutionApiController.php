<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Controllers\Api;

use FavoriteCMS\Core\Application;
use FavoriteCMS\Core\Request;
use FavoriteCMS\Core\Response;
use FavoriteCMS\Models\User;
use FavoriteCMS\Tools\Services\ToolExecutionService;
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
            } else {
                $allPost = $request->post();
                if (is_array($allPost)) {
                    unset($allPost['_token']);
                    $inputs = $allPost;
                }
            }
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

        $result = $this->executionService->execute($slug, $inputs, $userId > 0 ? $userId : null, $isAdmin);

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

            return Response::json($result, $statusCode);
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

