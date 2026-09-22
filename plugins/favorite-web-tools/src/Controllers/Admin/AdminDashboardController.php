<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Controllers\Admin;

use FavoriteCMS\Core\Application;
use FavoriteCMS\Core\Request;
use FavoriteCMS\Core\Response;
use FavoriteCMS\Models\User;
use FavoriteCMS\Tools\Repositories\CategoryRepository;
use FavoriteCMS\Tools\Repositories\PythonServiceRepository;
use FavoriteCMS\Tools\Repositories\ToolRepository;
use FavoriteCMS\Tools\Support\ViewRenderer;
use Throwable;

class AdminDashboardController
{
    protected Application $app;
    protected ToolRepository $toolRepo;
    protected CategoryRepository $categoryRepo;
    protected PythonServiceRepository $pythonRepo;

    public function __construct(
        Application $app,
        ToolRepository $toolRepo,
        CategoryRepository $categoryRepo,
        PythonServiceRepository $pythonRepo
    ) {
        $this->app = $app;
        $this->toolRepo = $toolRepo;
        $this->categoryRepo = $categoryRepo;
        $this->pythonRepo = $pythonRepo;
    }

    public function handle(Request $request): Response|string
    {
        if (!$this->isAuthorized()) {
            return Response::make('<h1>403 Access Denied</h1><p>You do not have permission to view Web Tools settings.</p>', 403);
        }

        $allTools = $this->toolRepo->all();
        $totalTools = count($allTools);
        $activeTools = count(array_filter($allTools, fn($t) => $t->isActive()));
        $draftTools = count(array_filter($allTools, fn($t) => $t->isDraft()));
        $disabledTools = count(array_filter($allTools, fn($t) => $t->isDisabled()));

        $categories = $this->categoryRepo->all();
        $pythonServices = $this->pythonRepo->all();
        $activePythonServices = count(array_filter($pythonServices, fn($s) => $s->is_active));

        // Sort by updated_at desc for recent
        usort($allTools, fn($a, $b) => strcmp((string)$b->updated_at, (string)$a->updated_at));
        $recentTools = array_slice($allTools, 0, 8);

        $viewData = [
            'totalTools'           => $totalTools,
            'activeTools'          => $activeTools,
            'draftTools'           => $draftTools,
            'disabledTools'        => $disabledTools,
            'categoriesCount'      => count($categories),
            'pythonServicesCount'  => count($pythonServices),
            'activePythonServices' => $activePythonServices,
            'recentTools'          => $recentTools,
            'categories'           => $categories,
        ];

        return ViewRenderer::render('admin/dashboard', $viewData);
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

