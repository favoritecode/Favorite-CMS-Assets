<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Controllers\Frontend;

use FavoriteCMS\Core\Application;
use FavoriteCMS\Core\Request;
use FavoriteCMS\Core\Response;
use FavoriteCMS\Models\User;
use FavoriteCMS\Tools\Repositories\CategoryRepository;
use FavoriteCMS\Tools\Services\AccessControlService;
use FavoriteCMS\Tools\Services\ToolRegistryService;
use FavoriteCMS\Tools\Support\CsrfGuard;
use FavoriteCMS\Tools\Support\ToolThemeShell;
use FavoriteCMS\Tools\Support\ViewRenderer;
use Throwable;

class ToolController
{
    protected Application $app;
    protected ToolRegistryService $toolRegistry;
    protected CategoryRepository $categoryRepo;
    protected AccessControlService $accessControl;

    public function __construct(
        Application $app,
        ToolRegistryService $toolRegistry,
        CategoryRepository $categoryRepo,
        AccessControlService $accessControl
    ) {
        $this->app = $app;
        $this->toolRegistry = $toolRegistry;
        $this->categoryRepo = $categoryRepo;
        $this->accessControl = $accessControl;
    }

    public function show(Request $request, string $slug): Response|string
    {
        $tool = $this->toolRegistry->findBySlug($slug);
        if ($tool === null) {
            return $this->renderNotFound("The requested tool '{$slug}' does not exist or has been removed.");
        }

        $userId = $this->resolveCurrentUserId();
        $isAdmin = $this->isAdmin($userId);

        // Public visibility: only ACTIVE tools are visible publicly unless viewer is an admin previewing
        if (!$tool->isActive()) {
            if (!$isAdmin) {
                return $this->renderNotFound("The requested tool is currently unavailable.");
            }
        }

        $category = null;
        if ($tool->category_id !== null) {
            $category = $this->categoryRepo->find($tool->category_id);
        }

        $accessCheck = $this->accessControl->canAccess($tool, $userId > 0 ? $userId : null);

        // Fetch related tools in the same category
        $relatedTools = [];
        if ($tool->category_id !== null) {
            $catTools = $this->toolRegistry->getActiveToolsByCategory($tool->category_id);
            foreach ($catTools as $t) {
                if ($t->id !== $tool->id) {
                    $relatedTools[] = $t;
                    if (count($relatedTools) >= 4) {
                        break;
                    }
                }
            }
        }

        $pageTitle = $tool->name . ' — Web Tools';
        $pageDescription = $tool->description ?? '';

        $viewData = [
            'tool'          => $tool,
            'category'      => $category,
            'accessCheck'   => $accessCheck,
            'userId'        => $userId,
            'isAdmin'       => $isAdmin,
            'relatedTools'  => $relatedTools,
            'csrfToken'     => CsrfGuard::token(),
            'accessControl' => $this->accessControl,
        ];

        $viewPath = ViewRenderer::getViewsDir() . '/frontend/tool.php';
        return ToolThemeShell::render($viewPath, $viewData, $pageTitle, $pageDescription);
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

    protected function renderNotFound(string $message): Response|string
    {
        $viewPath = ViewRenderer::getViewsDir() . '/frontend/not-found.php';
        $html = ToolThemeShell::render($viewPath, ['message' => $message], '404 Not Found');
        return Response::make($html, 404);
    }
}

