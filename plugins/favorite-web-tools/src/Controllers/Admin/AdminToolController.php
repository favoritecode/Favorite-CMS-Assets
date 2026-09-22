<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Controllers\Admin;

use FavoriteCMS\Core\Application;
use FavoriteCMS\Core\Request;
use FavoriteCMS\Core\Response;
use FavoriteCMS\Models\User;
use FavoriteCMS\Tools\Models\Tool;
use FavoriteCMS\Tools\Repositories\CategoryRepository;
use FavoriteCMS\Tools\Repositories\PythonServiceRepository;
use FavoriteCMS\Tools\Repositories\ToolRepository;
use FavoriteCMS\Tools\Services\ToolExecutionService;
use FavoriteCMS\Tools\Support\CsrfGuard;
use FavoriteCMS\Tools\Support\ViewRenderer;
use Throwable;

class AdminToolController
{
    protected Application $app;
    protected ToolRepository $toolRepo;
    protected CategoryRepository $categoryRepo;
    protected PythonServiceRepository $pythonRepo;
    protected ToolExecutionService $executionService;

    public function __construct(
        Application $app,
        ToolRepository $toolRepo,
        CategoryRepository $categoryRepo,
        PythonServiceRepository $pythonRepo,
        ToolExecutionService $executionService
    ) {
        $this->app = $app;
        $this->toolRepo = $toolRepo;
        $this->categoryRepo = $categoryRepo;
        $this->pythonRepo = $pythonRepo;
        $this->executionService = $executionService;
    }

    public function handle(Request $request): Response|string
    {
        if (!$this->isAuthorized()) {
            return Response::make('<h1>403 Access Denied</h1><p>You do not have permission to manage tools.</p>', 403);
        }

        if ($request->method() === 'POST') {
            return $this->handlePost($request);
        }

        return $this->handleGet($request);
    }

    protected function handleGet(Request $request): Response|string
    {
        $action = (string)$request->get('action', 'index');
        $id = (int)$request->get('id', 0);

        return match ($action) {
            'create' => $this->createForm($request),
            'edit'   => $this->editForm($request, $id),
            'test'   => $this->testScreen($request, $id),
            default  => $this->index($request),
        };
    }

    protected function handlePost(Request $request): Response
    {
        if (!CsrfGuard::verify($request)) {
            $_SESSION['flash_error'] = 'Security token invalid or expired (CSRF failure). Please try again.';
            return Response::redirect('/admin/page/favorite-web-tools');
        }

        $action = (string)$request->post('action', 'save');
        $id = (int)$request->post('id', 0);

        return match ($action) {
            'save'         => $this->saveTool($request, $id),
            'activate'     => $this->activateTool($request, $id),
            'disable'      => $this->disableTool($request, $id),
            'delete'       => $this->deleteTool($request, $id),
            'execute_test' => $this->executeTest($request, $id),
            default        => Response::redirect('/admin/page/favorite-web-tools'),
        };
    }

    public function index(Request $request): string
    {
        $search = trim((string)$request->get('search', ''));
        $status = trim((string)$request->get('status', ''));
        $catId = (int)$request->get('category_id', 0);

        $allTools = $this->toolRepo->all();
        $counts = [
            'all'      => count($allTools),
            'active'   => count(array_filter($allTools, fn($t) => $t->isActive())),
            'draft'    => count(array_filter($allTools, fn($t) => $t->isDraft())),
            'disabled' => count(array_filter($allTools, fn($t) => $t->isDisabled())),
        ];

        // Filter
        $filteredTools = array_filter($allTools, function ($t) use ($search, $status, $catId) {
            if ($status !== '' && strtolower($t->status) !== strtolower($status)) {
                return false;
            }
            if ($catId > 0 && $t->category_id !== $catId) {
                return false;
            }
            if ($search !== '') {
                $term = strtolower($search);
                $nameMatch = str_contains(strtolower($t->name), $term);
                $slugMatch = str_contains(strtolower($t->slug), $term);
                $descMatch = str_contains(strtolower($t->description ?? ''), $term);
                if (!$nameMatch && !$slugMatch && !$descMatch) {
                    return false;
                }
            }
            return true;
        });

        // Map categories for quick display
        $categories = $this->categoryRepo->all();
        $categoryMap = [];
        foreach ($categories as $cat) {
            $categoryMap[$cat->id] = $cat->name;
        }

        $flashSuccess = $_SESSION['flash_success'] ?? null;
        $flashError   = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);

        return ViewRenderer::render('admin/tools/index', [
            'tools'        => array_values($filteredTools),
            'counts'       => $counts,
            'categories'   => $categories,
            'categoryMap'  => $categoryMap,
            'statusFilter' => $status !== '' ? $status : null,
            'catFilter'    => $catId > 0 ? $catId : null,
            'search'       => $search,
            'csrfToken'    => CsrfGuard::token(),
            'flashSuccess' => $flashSuccess,
            'flashError'   => $flashError,
        ]);
    }

    public function createForm(Request $request): string
    {
        $categories = $this->categoryRepo->all();
        $pythonServices = $this->pythonRepo->allActive();
        $registeredHandlers = \FavoriteCMS\Tools\Handlers\PhpHandlerRegistry::listHandlers();

        $tool = new Tool([
            'engine'        => 'PHP',
            'access_mode'   => 'FREE',
            'status'        => 'DRAFT',
            'display_order' => 0,
            'input_schema'  => [],
            'output_schema' => [],
            'ui_schema'     => [],
        ]);

        $flashError = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_error']);

        return ViewRenderer::render('admin/tools/form', [
            'tool'               => $tool,
            'categories'         => $categories,
            'pythonServices'     => $pythonServices,
            'registeredHandlers' => $registeredHandlers,
            'csrfToken'          => CsrfGuard::token(),
            'isEdit'             => false,
            'flashError'         => $flashError,
        ]);
    }

    public function editForm(Request $request, int $id): Response|string
    {
        $tool = $this->toolRepo->find($id);
        if ($tool === null) {
            $_SESSION['flash_error'] = "Tool #{$id} was not found.";
            return Response::redirect('/admin/page/favorite-web-tools');
        }

        $categories = $this->categoryRepo->all();
        $pythonServices = $this->pythonRepo->allActive();
        if ($tool->python_service_id) {
            $hasCurrent = false;
            foreach ($pythonServices as $ps) {
                if ($ps->id === $tool->python_service_id) {
                    $hasCurrent = true;
                    break;
                }
            }
            if (!$hasCurrent) {
                $currentSrv = $this->pythonRepo->findById((int)$tool->python_service_id);
                if ($currentSrv) {
                    $pythonServices[] = $currentSrv;
                }
            }
        }
        $registeredHandlers = \FavoriteCMS\Tools\Handlers\PhpHandlerRegistry::listHandlers();

        $flashSuccess = $_SESSION['flash_success'] ?? null;
        $flashError   = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);

        return ViewRenderer::render('admin/tools/form', [
            'tool'               => $tool,
            'categories'         => $categories,
            'pythonServices'     => $pythonServices,
            'registeredHandlers' => $registeredHandlers,
            'csrfToken'          => CsrfGuard::token(),
            'isEdit'             => true,
            'flashSuccess'       => $flashSuccess,
            'flashError'         => $flashError,
        ]);
    }

    public function testScreen(Request $request, int $id): Response|string
    {
        $tool = $this->toolRepo->find($id);
        if ($tool === null) {
            $_SESSION['flash_error'] = "Tool #{$id} was not found.";
            return Response::redirect('/admin/page/favorite-web-tools');
        }

        $flashSuccess = $_SESSION['flash_success'] ?? null;
        $flashError   = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);

        return ViewRenderer::render('admin/tools/test', [
            'tool'         => $tool,
            'csrfToken'    => CsrfGuard::token(),
            'flashSuccess' => $flashSuccess,
            'flashError'   => $flashError,
        ]);
    }

    protected function saveTool(Request $request, int $id): Response
    {
        $name = trim((string)$request->post('name', ''));
        $slug = trim((string)$request->post('slug', ''));
        $description = trim((string)$request->post('description', ''));
        $categoryId = (int)$request->post('category_id', 0) ?: null;
        $engine = strtoupper(trim((string)$request->post('engine', 'PHP')));
        $handlerId = trim((string)$request->post('handler_id', ''));
        $handlerClass = trim((string)$request->post('handler_class', ''));

        // Map handler_id to class if registered
        if ($handlerId !== '' && \FavoriteCMS\Tools\Handlers\PhpHandlerRegistry::has($handlerId)) {
            $handlerInstance = \FavoriteCMS\Tools\Handlers\PhpHandlerRegistry::get($handlerId);
            if ($handlerInstance) {
                $handlerClass = get_class($handlerInstance);
            }
        } elseif ($handlerClass !== '') {
            foreach (\FavoriteCMS\Tools\Handlers\PhpHandlerRegistry::listHandlers() as $hid => $hname) {
                $h = \FavoriteCMS\Tools\Handlers\PhpHandlerRegistry::get($hid);
                if ($h && get_class($h) === $handlerClass) {
                    $handlerId = $hid;
                    break;
                }
            }
        }

        $htmlSource = (string)$request->post('html_source', '');
        $cssSource = (string)$request->post('css_source', '');
        $jsSource = (string)$request->post('js_source', '');

        // Parse external libraries (one per line, validate http/https)
        $rawLibs = (string)$request->post('external_libraries', '');
        $externalLibs = [];
        if (trim($rawLibs) !== '') {
            $lines = preg_split('/[\r\n]+/', $rawLibs);
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }
                if (preg_match('/^https?:\/\//i', $line) && filter_var($line, FILTER_VALIDATE_URL)) {
                    $externalLibs[] = $line;
                }
            }
        }

        $pythonServiceId = (int)$request->post('python_service_id', 0) ?: null;
        $pythonEndpoint = trim((string)$request->post('python_endpoint', ''));
        $accessMode = strtoupper(trim((string)$request->post('access_mode', 'FREE')));
        $status = strtoupper(trim((string)$request->post('status', 'DRAFT')));
        $icon = trim((string)$request->post('icon', ''));
        $thumbnail = trim((string)$request->post('thumbnail', ''));
        $displayOrder = (int)$request->post('display_order', 0);

        // Validate JSON schemas
        $rawInput = trim((string)$request->post('input_schema', ''));
        $rawOutput = trim((string)$request->post('output_schema', ''));
        $rawUi = trim((string)$request->post('ui_schema', ''));

        if ($rawInput !== '' && $rawInput !== '{}' && $rawInput !== '[]') {
            $decoded = json_decode($rawInput, true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
                $_SESSION['flash_error'] = 'Invalid JSON in Input Schema: ' . json_last_error_msg();
                return Response::redirect($id > 0 ? "/admin/page/favorite-web-tools?action=edit&id={$id}" : '/admin/page/favorite-web-tools?action=create');
            }
            $inputSchema = $decoded;
        } else {
            $inputSchema = [];
        }

        if ($rawOutput !== '' && $rawOutput !== '{}' && $rawOutput !== '[]') {
            $decoded = json_decode($rawOutput, true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
                $_SESSION['flash_error'] = 'Invalid JSON in Output Schema: ' . json_last_error_msg();
                return Response::redirect($id > 0 ? "/admin/page/favorite-web-tools?action=edit&id={$id}" : '/admin/page/favorite-web-tools?action=create');
            }
            $outputSchema = $decoded;
        } else {
            $outputSchema = [];
        }

        if ($rawUi !== '' && $rawUi !== '{}' && $rawUi !== '[]') {
            $decoded = json_decode($rawUi, true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
                $_SESSION['flash_error'] = 'Invalid JSON in UI Schema: ' . json_last_error_msg();
                return Response::redirect($id > 0 ? "/admin/page/favorite-web-tools?action=edit&id={$id}" : '/admin/page/favorite-web-tools?action=create');
            }
            $uiSchema = $decoded;
        } else {
            $uiSchema = [];
        }

        if ($name === '') {
            $_SESSION['flash_error'] = 'Tool Name is required.';
            return Response::redirect($id > 0 ? "/admin/page/favorite-web-tools?action=edit&id={$id}" : '/admin/page/favorite-web-tools?action=create');
        }

        if ($engine === 'PYTHON_API') {
            if (empty($pythonServiceId)) {
                $_SESSION['flash_error'] = 'Please select an active Python API Service for this tool.';
                return Response::redirect($id > 0 ? "/admin/page/favorite-web-tools?action=edit&id={$id}" : '/admin/page/favorite-web-tools?action=create');
            }

            $service = $this->pythonRepo->findById($pythonServiceId);
            if ($service === null || !$service->isActive()) {
                $_SESSION['flash_error'] = 'The selected Python API Service is invalid or disabled.';
                return Response::redirect($id > 0 ? "/admin/page/favorite-web-tools?action=edit&id={$id}" : '/admin/page/favorite-web-tools?action=create');
            }

            if ($pythonEndpoint === '') {
                $pythonEndpoint = $service->default_endpoint_path ?: '/download/api';
            }

            if (preg_match('#^https?://#i', $pythonEndpoint)) {
                $endpointHost = strtolower((string)parse_url($pythonEndpoint, PHP_URL_HOST));
                $serviceHost  = strtolower((string)parse_url($service->base_url, PHP_URL_HOST));
                if ($endpointHost !== '' && $endpointHost !== $serviceHost) {
                    $_SESSION['flash_error'] = "The endpoint URL host '{$endpointHost}' does not match the configured service host '{$serviceHost}'. Host override is forbidden.";
                    return Response::redirect($id > 0 ? "/admin/page/favorite-web-tools?action=edit&id={$id}" : '/admin/page/favorite-web-tools?action=create');
                }
                $path = parse_url($pythonEndpoint, PHP_URL_PATH) ?? '/';
                $query = parse_url($pythonEndpoint, PHP_URL_QUERY);
                $pythonEndpoint = $path . ($query ? '?' . $query : '');
            }

            if (!str_starts_with($pythonEndpoint, '/')) {
                $pythonEndpoint = '/' . $pythonEndpoint;
            }
        }

        if ($slug === '') {
            $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $name), '-'));
        }

        // Validate slug uniqueness
        $existing = $this->toolRepo->findBySlug($slug);
        if ($existing !== null && $existing->id !== $id) {
            $_SESSION['flash_error'] = "Slug '{$slug}' is already in use by another tool. Please choose a unique slug.";
            return Response::redirect($id > 0 ? "/admin/page/favorite-web-tools?action=edit&id={$id}" : '/admin/page/favorite-web-tools?action=create');
        }

        $data = [
            'name'               => $name,
            'slug'               => $slug,
            'description'        => $description,
            'category_id'        => $categoryId,
            'engine'             => $engine,
            'handler_class'      => $handlerClass !== '' ? $handlerClass : null,
            'handler_id'         => $handlerId !== '' ? $handlerId : null,
            'html_source'        => $htmlSource,
            'css_source'         => $cssSource,
            'js_source'          => $jsSource,
            'external_libraries' => $externalLibs,
            'python_service_id'  => $pythonServiceId,
            'python_endpoint'    => $pythonEndpoint !== '' ? $pythonEndpoint : null,
            'access_mode'        => $accessMode,
            'status'             => $status,
            'icon'               => $icon,
            'thumbnail'          => $thumbnail,
            'display_order'      => $displayOrder,
            'input_schema'       => $inputSchema,
            'output_schema'      => $outputSchema,
            'ui_schema'          => $uiSchema,
        ];

        try {
            if ($id > 0) {
                $this->toolRepo->update($id, $data);
                $_SESSION['flash_success'] = "Tool '{$name}' was updated successfully.";
                return Response::redirect("/admin/page/favorite-web-tools?action=edit&id={$id}");
            } else {
                $created = $this->toolRepo->create($data);
                $_SESSION['flash_success'] = "Tool '{$name}' was created successfully.";
                return Response::redirect("/admin/page/favorite-web-tools?action=edit&id={$created->id}");
            }
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = 'Failed to save tool: ' . $e->getMessage();
            return Response::redirect($id > 0 ? "/admin/page/favorite-web-tools?action=edit&id={$id}" : '/admin/page/favorite-web-tools?action=create');
        }
    }

    protected function activateTool(Request $request, int $id): Response
    {
        try {
            $this->toolRepo->updateStatus($id, 'ACTIVE');
            $_SESSION['flash_success'] = "Tool #{$id} is now ACTIVE.";
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = 'Failed to activate tool: ' . $e->getMessage();
        }
        return Response::redirect('/admin/page/favorite-web-tools');
    }

    protected function disableTool(Request $request, int $id): Response
    {
        try {
            $this->toolRepo->updateStatus($id, 'DISABLED');
            $_SESSION['flash_success'] = "Tool #{$id} is now DISABLED.";
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = 'Failed to disable tool: ' . $e->getMessage();
        }
        return Response::redirect('/admin/page/favorite-web-tools');
    }

    protected function deleteTool(Request $request, int $id): Response
    {
        try {
            $this->toolRepo->delete($id);
            $_SESSION['flash_success'] = "Tool #{$id} was deleted.";
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = 'Failed to delete tool: ' . $e->getMessage();
        }
        return Response::redirect('/admin/page/favorite-web-tools');
    }

    protected function executeTest(Request $request, int $id): Response
    {
        $tool = $this->toolRepo->find($id);
        if ($tool === null) {
            $_SESSION['flash_error'] = "Tool #{$id} not found.";
            return Response::redirect('/admin/page/favorite-web-tools');
        }

        $rawInputs = (string)$request->post('test_inputs', '');
        $inputs = json_decode($rawInputs, true);
        if (!is_array($inputs)) {
            // Treat as single text input
            $inputs = ['text' => $rawInputs, 'code' => $rawInputs, 'input' => $rawInputs, 'html' => $rawInputs, 'css' => $rawInputs, 'json' => $rawInputs];
        }

        $userId = (int)($_SESSION['auth_user_id'] ?? 1);
        $result = $this->executionService->execute($tool->slug, $inputs, $userId, true);

        $_SESSION['last_test_result'] = $result;
        return Response::redirect("/admin/page/favorite-web-tools?action=test&id={$id}");
    }

    private function parseJsonField(mixed $val): array
    {
        if (is_array($val)) {
            return $val;
        }
        if (is_string($val) && trim($val) !== '') {
            $decoded = json_decode($val, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        return [];
    }

    protected function isAuthorized(): bool
    {
        if (isset($GLOBALS['_test_current_user'])) {
            $u = $GLOBALS['_test_current_user'];
            if (method_exists($u, 'can') && $u->can('manage_options')) {
                return true;
            }
        }

        $userId = (int)($_SESSION['auth_user_id'] ?? 0);
        if ($userId > 0 && class_exists(User::class)) {
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

