<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Controllers\Frontend;

use FavoriteCMS\Core\Application;
use FavoriteCMS\Core\Request;
use FavoriteCMS\Core\Response;
use FavoriteCMS\Tools\Repositories\CategoryRepository;
use FavoriteCMS\Tools\Services\AccessControlService;
use FavoriteCMS\Tools\Services\ToolRegistryService;
use FavoriteCMS\Tools\Support\ToolThemeShell;
use FavoriteCMS\Tools\Support\ViewRenderer;

class CatalogController
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

    public function index(Request $request): Response|string
    {
        $search = trim((string)($request->get('search') ?? $request->get('q') ?? ''));
        $categorySlug = trim((string)$request->get('category', ''));
        $engine = trim((string)$request->get('engine', ''));
        $accessMode = trim((string)$request->get('access_mode', ''));
        $sort = trim((string)$request->get('sort', 'order'));
        $page = max(1, (int)$request->get('page', 1));
        $perPage = 12;

        $categories = $this->categoryRepo->allActive();
        $selectedCategory = null;
        $categoryId = null;

        if ($categorySlug !== '') {
            foreach ($categories as $cat) {
                if ($cat->slug === $categorySlug) {
                    $selectedCategory = $cat;
                    $categoryId = $cat->id;
                    break;
                }
            }
        }

        $filters = [
            'search'      => $search,
            'category_id' => $categoryId,
            'engine'      => $engine,
            'access_mode' => $accessMode,
            'sort'        => $sort,
        ];

        $results = $this->toolRegistry->searchTools($filters, $page, $perPage);
        $userId = $this->resolveCurrentUserId();

        // Calculate tool counts per category for chip badges
        $categoryCounts = [];
        foreach ($categories as $cat) {
            $catTools = $this->toolRegistry->getActiveToolsByCategory($cat->id);
            $categoryCounts[$cat->id] = count($catTools);
        }

        $allActiveTools = $this->toolRegistry->getActiveTools();
        $totalAllTools = count($allActiveTools);

        $pageTitle = $selectedCategory ? "{$selectedCategory->name} Tools — Web Tools" : 'Free Online Web Tools & Utilities';
        $pageDescription = $selectedCategory ? ($selectedCategory->description ?? '') : 'Browse our complete catalog of free and powerful developer tools, utilities, and converters.';

        $viewData = [
            'tools'            => $results['items'],
            'total'            => $results['total'],
            'totalAllTools'    => $totalAllTools,
            'page'             => $results['page'],
            'perPage'          => $results['perPage'],
            'totalPages'       => $results['totalPages'],
            'categories'       => $categories,
            'categoryCounts'   => $categoryCounts,
            'selectedCategory' => $selectedCategory,
            'currentSearch'    => $search,
            'currentCategory'  => $categorySlug,
            'currentEngine'    => $engine,
            'currentAccess'    => $accessMode,
            'currentSort'      => $sort,
            'userId'           => $userId,
            'accessControl'    => $this->accessControl,
        ];

        $viewPath = ViewRenderer::getViewsDir() . '/frontend/catalog.php';
        return ToolThemeShell::render($viewPath, $viewData, $pageTitle, $pageDescription);
    }

    public function category(Request $request, string $slug): Response|string
    {
        $cat = $this->categoryRepo->findBySlug($slug);
        if ($cat === null || !$cat->isActive()) {
            return $this->renderNotFound("Category '{$slug}' was not found.");
        }

        // Forward with category filter
        $_GET['category'] = $slug;
        return $this->index($request);
    }

    protected function resolveCurrentUserId(): int
    {
        if (isset($GLOBALS['_test_current_user']) && isset($GLOBALS['_test_current_user']->id)) {
            return (int)$GLOBALS['_test_current_user']->id;
        }
        return (int)($_SESSION['auth_user_id'] ?? 0);
    }

    protected function renderNotFound(string $message): Response|string
    {
        $viewPath = ViewRenderer::getViewsDir() . '/frontend/not-found.php';
        $html = ToolThemeShell::render($viewPath, ['message' => $message], '404 Not Found');
        return Response::make($html, 404);
    }
}

