<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Controllers\Admin;

use FavoriteCMS\Core\Application;
use FavoriteCMS\Core\Request;
use FavoriteCMS\Core\Response;
use FavoriteCMS\Models\User;
use FavoriteCMS\Tools\Models\PythonService;
use FavoriteCMS\Tools\Repositories\PythonServiceRepository;
use FavoriteCMS\Tools\Repositories\ToolRepository;
use FavoriteCMS\Tools\Services\PythonClientService;
use FavoriteCMS\Tools\Services\ToolExecutionService;
use FavoriteCMS\Tools\Support\CsrfGuard;
use FavoriteCMS\Tools\Support\ViewRenderer;
use Throwable;

class AdminPythonServiceController
{
    protected Application $app;
    protected PythonServiceRepository $serviceRepo;
    protected ToolRepository $toolRepo;
    protected PythonClientService $clientService;

    public function __construct(
        Application $app,
        PythonServiceRepository $serviceRepo,
        ToolRepository $toolRepo,
        PythonClientService $clientService
    ) {
        $this->app = $app;
        $this->serviceRepo = $serviceRepo;
        $this->toolRepo = $toolRepo;
        $this->clientService = $clientService;
    }

    public function handle(Request $request): Response|string
    {
        if (!$this->isAuthorized()) {
            return Response::make('<h1>403 Access Denied</h1><p>You do not have permission to manage Python services.</p>', 403);
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
            default  => $this->index($request),
        };
    }

    protected function handlePost(Request $request): Response
    {
        if (!CsrfGuard::verify($request)) {
            $_SESSION['flash_error'] = 'Security token invalid or expired (CSRF failure). Please try again.';
            return Response::redirect('/admin/page/favorite-web-tools-python-services');
        }

        $action = (string)$request->post('action', 'save');
        $id = (int)$request->post('id', 0);

        return match ($action) {
            'save'          => $this->saveService($request, $id),
            'test'          => $this->testConnection($request, $id),
            'delete'        => $this->deleteService($request, $id),
            'toggle_status' => $this->toggleStatus($request, $id),
            'activate'      => $this->setStatus($request, $id, 'active'),
            'disable'       => $this->setStatus($request, $id, 'disabled'),
            default         => Response::redirect('/admin/page/favorite-web-tools-python-services'),
        };
    }

    public function index(Request $request): string
    {
        $services = $this->serviceRepo->all();
        $toolCounts = [];
        $allTools = $this->toolRepo->all();
        foreach ($services as $srv) {
            $count = count(array_filter($allTools, fn($t) => (int)$t->python_service_id === (int)$srv->id));
            $toolCounts[$srv->id] = $count;
        }

        $flashSuccess = $_SESSION['flash_success'] ?? null;
        $flashError   = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);

        return ViewRenderer::render('admin/python-services/index', [
            'services'     => $services,
            'toolCounts'   => $toolCounts,
            'csrfToken'    => CsrfGuard::token(),
            'flashSuccess' => $flashSuccess,
            'flashError'   => $flashError,
        ]);
    }

    public function createForm(Request $request): string
    {
        $service = new PythonService([
            'is_active'             => 1,
            'status'                => 'active',
            'timeout'               => 30,
            'http_method'           => 'GET',
            'default_endpoint_path' => '/download/api',
            'auth_type'             => 'none',
        ]);

        $flashError = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_error']);

        return ViewRenderer::render('admin/python-services/form', [
            'service'    => $service,
            'csrfToken'  => CsrfGuard::token(),
            'isEdit'     => false,
            'flashError' => $flashError,
        ]);
    }

    public function editForm(Request $request, int $id): Response|string
    {
        $service = $this->serviceRepo->find($id);
        if ($service === null) {
            $_SESSION['flash_error'] = "Python Service #{$id} was not found.";
            return Response::redirect('/admin/page/favorite-web-tools-python-services');
        }

        $flashSuccess = $_SESSION['flash_success'] ?? null;
        $flashError   = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);

        return ViewRenderer::render('admin/python-services/form', [
            'service'      => $service,
            'csrfToken'    => CsrfGuard::token(),
            'isEdit'       => true,
            'flashSuccess' => $flashSuccess,
            'flashError'   => $flashError,
        ]);
    }

    protected function saveService(Request $request, int $id): Response
    {
        $name = trim((string)$request->post('name', ''));
        $slug = trim((string)$request->post('slug', ''));
        $description = trim((string)$request->post('description', ''));
        $baseUrl = trim((string)$request->post('base_url', ''));
        $defaultEndpoint = trim((string)$request->post('default_endpoint_path', ''));
        $httpMethod = strtoupper(trim((string)$request->post('http_method', 'GET')));
        $authType = strtolower(trim((string)$request->post('auth_type', 'none')));
        $apiKey = trim((string)$request->post('api_key', ''));
        $timeout = max(1, min(120, (int)$request->post('timeout', 30)));
        $status = strtolower(trim((string)$request->post('status', 'active')));

        if ($request->post('status') === null && $request->post('is_active') !== null) {
            $status = !empty($request->post('is_active')) ? 'active' : 'disabled';
        }

        if ($name === '' || $baseUrl === '') {
            $_SESSION['flash_error'] = 'Service Name and Base URL are required.';
            return Response::redirect($id > 0 ? "/admin/page/favorite-web-tools-python-services?action=edit&id={$id}" : '/admin/page/favorite-web-tools-python-services?action=create');
        }

        if ($slug === '') {
            $slug = strtolower(trim((string)preg_replace('/[^a-zA-Z0-9]+/', '-', $name), '-'));
        }

        // Validate slug uniqueness
        $existingSlug = $this->serviceRepo->findBySlug($slug);
        if ($existingSlug !== null && $existingSlug->id !== $id) {
            $_SESSION['flash_error'] = "Slug '{$slug}' is already in use by another Python service.";
            return Response::redirect($id > 0 ? "/admin/page/favorite-web-tools-python-services?action=edit&id={$id}" : '/admin/page/favorite-web-tools-python-services?action=create');
        }

        // Validate URL format and security (SSRF prevention)
        if (!filter_var($baseUrl, FILTER_VALIDATE_URL)) {
            $_SESSION['flash_error'] = 'Invalid Base URL format.';
            return Response::redirect($id > 0 ? "/admin/page/favorite-web-tools-python-services?action=edit&id={$id}" : '/admin/page/favorite-web-tools-python-services?action=create');
        }

        try {
            // Validate URL and enforce HTTPS for external services
            $this->clientService->validateUrl($baseUrl, true);
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = 'URL Security Validation Failed: ' . ToolExecutionService::sanitizeMessageString($e->getMessage());
            return Response::redirect($id > 0 ? "/admin/page/favorite-web-tools-python-services?action=edit&id={$id}" : '/admin/page/favorite-web-tools-python-services?action=create');
        }

        if ($defaultEndpoint === '') {
            $defaultEndpoint = '/download/api';
        }
        if (!str_starts_with($defaultEndpoint, '/')) {
            $defaultEndpoint = '/' . $defaultEndpoint;
        }

        if (!in_array($httpMethod, ['GET', 'POST'], true)) {
            $httpMethod = 'GET';
        }

        if (!in_array($authType, ['none', 'bearer', 'api_key'], true)) {
            $authType = 'none';
        }

        $data = [
            'name'                  => $name,
            'slug'                  => $slug,
            'description'           => $description !== '' ? $description : null,
            'base_url'              => $baseUrl,
            'default_endpoint_path' => $defaultEndpoint,
            'http_method'           => $httpMethod,
            'auth_type'             => $authType,
            'api_key'               => $apiKey !== '' ? $apiKey : null,
            'timeout'               => $timeout,
            'status'                => $status,
        ];

        try {
            if ($id > 0) {
                // If API key left blank on edit and existing has key, preserve existing
                $existing = $this->serviceRepo->find($id);
                if ($apiKey === '' && $existing && $existing->api_key) {
                    $data['api_key'] = $existing->api_key;
                }

                $this->serviceRepo->update($id, $data);
                $_SESSION['flash_success'] = "Python Service '{$name}' was updated successfully.";
                return Response::redirect("/admin/page/favorite-web-tools-python-services?action=edit&id={$id}");
            } else {
                $created = $this->serviceRepo->create($data);
                $_SESSION['flash_success'] = "Python Service '{$name}' was created successfully.";
                return Response::redirect("/admin/page/favorite-web-tools-python-services?action=edit&id={$created->id}");
            }
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = 'Failed to save Python service: ' . ToolExecutionService::sanitizeMessageString($e->getMessage());
            return Response::redirect($id > 0 ? "/admin/page/favorite-web-tools-python-services?action=edit&id={$id}" : '/admin/page/favorite-web-tools-python-services?action=create');
        }
    }

    protected function testConnection(Request $request, int $id): Response
    {
        $service = $this->serviceRepo->find($id);
        if ($service === null) {
            $_SESSION['flash_error'] = "Python Service #{$id} was not found.";
            return Response::redirect('/admin/page/favorite-web-tools-python-services');
        }

        $testEndpoint = trim((string)$request->post('test_endpoint', ''));
        if ($testEndpoint === '') {
            $testEndpoint = $service->default_endpoint_path ?: '/download/api';
        }
        if (!str_starts_with($testEndpoint, '/')) {
            $testEndpoint = '/' . $testEndpoint;
        }

        $method = strtoupper($service->http_method ?? 'GET');
        $result = $this->clientService->request($service, $method, $testEndpoint, []);

        if ($result['success']) {
            $_SESSION['flash_success'] = "Connection successful (HTTP " . ($result['status'] ?? 200) . ") to {$service->name}.";
        } else {
            $safeError = ToolExecutionService::sanitizeMessageString($result['error'] ?? 'Connection failed');
            $_SESSION['flash_error'] = "Connection failed: " . $safeError;
        }

        // Return to edit if requested, else index
        $redirectTarget = (string)$request->post('redirect', '');
        if ($redirectTarget === 'edit') {
            return Response::redirect("/admin/page/favorite-web-tools-python-services?action=edit&id={$id}");
        }

        return Response::redirect('/admin/page/favorite-web-tools-python-services');
    }

    protected function toggleStatus(Request $request, int $id): Response
    {
        $service = $this->serviceRepo->find($id);
        if ($service === null) {
            $_SESSION['flash_error'] = "Python Service #{$id} was not found.";
            return Response::redirect('/admin/page/favorite-web-tools-python-services');
        }

        $newStatus = $service->isActive() ? 'disabled' : 'active';
        $this->serviceRepo->setStatus($id, $newStatus);
        $_SESSION['flash_success'] = "Service '{$service->name}' status changed to " . strtoupper($newStatus) . ".";

        return Response::redirect('/admin/page/favorite-web-tools-python-services');
    }

    protected function setStatus(Request $request, int $id, string $status): Response
    {
        $service = $this->serviceRepo->find($id);
        if ($service === null) {
            $_SESSION['flash_error'] = "Python Service #{$id} was not found.";
            return Response::redirect('/admin/page/favorite-web-tools-python-services');
        }

        $this->serviceRepo->setStatus($id, $status);
        $_SESSION['flash_success'] = "Service '{$service->name}' is now " . strtoupper($status) . ".";

        return Response::redirect('/admin/page/favorite-web-tools-python-services');
    }

    protected function deleteService(Request $request, int $id): Response
    {
        $allTools = $this->toolRepo->all();
        $dependent = array_filter($allTools, fn($t) => (int)$t->python_service_id === $id);
        if (count($dependent) > 0) {
            $_SESSION['flash_error'] = "Cannot delete service #{$id}: " . count($dependent) . " tool(s) depend on it. Please reassign or delete dependent tools first.";
            return Response::redirect('/admin/page/favorite-web-tools-python-services');
        }

        try {
            $this->serviceRepo->delete($id);
            $_SESSION['flash_success'] = "Python Service #{$id} was deleted.";
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = 'Failed to delete Python service: ' . ToolExecutionService::sanitizeMessageString($e->getMessage());
        }
        return Response::redirect('/admin/page/favorite-web-tools-python-services');
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
