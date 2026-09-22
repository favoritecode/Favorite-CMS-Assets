<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Services;

use FavoriteCMS\Tools\Engines\EngineResolver;
use FavoriteCMS\Tools\Models\Tool;
use FavoriteCMS\Tools\Repositories\ToolRepository;
use FavoriteCMS\Tools\Support\ResultType;
use Throwable;

class ToolExecutionService
{
    protected ToolRepository $toolRepo;
    protected AccessControlService $accessControl;
    protected EngineResolver $engineResolver;
    protected DownloadManagerService $downloadManager;

    public function __construct(
        ToolRepository $toolRepo,
        AccessControlService $accessControl,
        EngineResolver $engineResolver,
        ?DownloadManagerService $downloadManager = null
    ) {
        $this->toolRepo = $toolRepo;
        $this->accessControl = $accessControl;
        $this->engineResolver = $engineResolver;
        $this->downloadManager = $downloadManager ?? new DownloadManagerService();
    }

    /**
     * Execute a tool by slug or ID with user context and inputs.
     *
     * @param string|int $slugOrId
     * @param array $inputs
     * @param int|null $userId
     * @param bool $isAdmin
     * @return array
     */
    public function execute(string|int $slugOrId, array $inputs = [], ?int $userId = null, bool $isAdmin = false): array
    {
        $tool = is_numeric($slugOrId)
            ? $this->toolRepo->findById((int)$slugOrId)
            : $this->toolRepo->findBySlug((string)$slugOrId);

        if (!$tool) {
            return [
                'success' => false,
                'error'   => [
                    'code'    => 'TOOL_NOT_FOUND',
                    'message' => 'The requested tool could not be found.',
                ],
            ];
        }

        // 1. Evaluate Access Control
        $access = $this->accessControl->evaluate($tool, $userId, $isAdmin);
        if (!$access['allowed']) {
            return [
                'success' => false,
                'error'   => [
                    'code'    => $access['code'],
                    'message' => $access['reason'],
                ],
            ];
        }

        // 2. Resolve Engine
        try {
            $engine = $this->engineResolver->resolve($tool);
        } catch (Throwable $e) {
            return [
                'success' => false,
                'error'   => [
                    'code'    => 'ENGINE_RESOLUTION_ERROR',
                    'message' => self::sanitizeErrorMessage($e),
                ],
            ];
        }

        // 3. Validate Engine Inputs
        $validationErrors = $engine->validate($tool, $inputs);
        if (!empty($validationErrors)) {
            return [
                'success' => false,
                'error'   => [
                    'code'    => 'VALIDATION_FAILED',
                    'message' => implode(' ', $validationErrors),
                    'details' => $validationErrors,
                ],
            ];
        }

        // 4. Execute Engine
        try {
            $startTime = microtime(true);
            $result = $engine->execute($tool, $inputs);
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            if (isset($result['success']) && $result['success'] === false) {
                $errRaw = $result['error'] ?? 'Execution failed.';
                $errMsg = is_string($errRaw) ? self::sanitizeMessageString($errRaw) : 'Execution failed.';
                return [
                    'success' => false,
                    'error'   => [
                        'code'    => 'EXECUTION_ERROR',
                        'message' => $errMsg,
                    ],
                ];
            }

            $resultType = $result['type'] ?? ResultType::TEXT;
            $resultValue = $result['value'] ?? $result['data'] ?? null;
            $resultData = $result['data'] ?? $result['value'] ?? null;
            $meta = $result['meta'] ?? [];
            $meta['execution_time'] = $duration . ' ms';

            // If user requested file download or result is marked as download
            $downloadContent = is_string($resultValue) ? $resultValue : (is_string($resultData) ? $resultData : json_encode($resultData));
            if (!empty($inputs['request_download']) && $downloadContent !== '' && $downloadContent !== null) {
                $ext = match ($tool->engine) {
                    'HTML' => 'html',
                    'CSS'  => 'css',
                    'JAVASCRIPT' => 'js',
                    'PHP'  => str_contains($tool->slug, 'json') ? 'json' : 'txt',
                    default => 'txt',
                };
                $filename = $tool->slug . '-result.' . $ext;
                $downloadInfo = $this->downloadManager->createDownload($downloadContent, $filename);
                $meta['download'] = $downloadInfo;
            }

            return [
                'success' => true,
                'tool'    => [
                    'name' => $tool->name,
                    'slug' => $tool->slug,
                ],
                'data'    => [
                    'type'  => $resultType,
                    'value' => $resultValue,
                    'data'  => $resultData,
                    'meta'  => $meta,
                ],
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'error'   => [
                    'code'    => 'EXECUTION_ERROR',
                    'message' => self::sanitizeErrorMessage($e),
                ],
            ];
        }
    }

    /**
     * Sanitize any exception to ensure internal filesystem paths, database queries,
     * namespaces, and stack traces are never exposed in API responses.
     */
    public static function sanitizeErrorMessage(Throwable $e): string
    {
        if ($e instanceof \PDOException || str_contains($e->getMessage(), 'SQLSTATE')) {
            return 'A database error occurred during execution.';
        }

        if ($e instanceof \Error) {
            return 'An internal execution error occurred.';
        }

        return self::sanitizeMessageString($e->getMessage());
    }

    /**
     * Strip internal filesystem paths, class names, and hostnames from error strings.
     */
    public static function sanitizeMessageString(string $msg): string
    {
        // 1. Strip Windows drive paths (e.g. C:\... or E:\...)
        $msg = (string)preg_replace('#[a-zA-Z]:[\\\\/][\w\s.-]+([\\\\/][\w\s.-]+)*#', '[path]', $msg);

        // 2. Strip Unix absolute paths
        $msg = (string)preg_replace('#/(?:var|tmp|home|etc|usr|opt|app|src|tests|views|plugins|themes)/[\w\s.-]+(/[w\s.-]+)*#i', '[path]', $msg);

        // 3. Strip internal class names / namespaces
        $msg = (string)preg_replace('#(?:FavoriteCMS\\\\[\w\\\\]+|[A-Z][a-zA-Z0-9_]+Handler|[A-Z][a-zA-Z0-9_]+Engine|[A-Z][a-zA-Z0-9_]+Service)#', '[internal]', $msg);

        // 4. Strip internal IP addresses
        $msg = (string)preg_replace('#\b(?:\d{1,3}\.){3}\d{1,3}(?::\d+)?\b#', '[host]', $msg);

        $clean = trim($msg);
        return $clean !== '' ? $clean : 'An error occurred during tool execution.';
    }
}
