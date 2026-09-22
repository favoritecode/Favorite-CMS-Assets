<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Controllers\Admin;

use FavoriteCMS\Core\Application;
use FavoriteCMS\Core\Request;
use FavoriteCMS\Core\Response;
use FavoriteCMS\Models\User;
use FavoriteCMS\Tools\Models\ToolCategory;
use FavoriteCMS\Tools\Repositories\CategoryRepository;
use FavoriteCMS\Tools\Repositories\ToolRepository;
use FavoriteCMS\Tools\Support\CsrfGuard;
use FavoriteCMS\Tools\Support\ViewRenderer;
use Throwable;

class AdminCategoryController
{
    protected Application $app;
    protected CategoryRepository $categoryRepo;
    protected ToolRepository $toolRepo;

    public function __construct(
        Application $app,
        CategoryRepository $categoryRepo,
        ToolRepository $toolRepo
    ) {
        $this->app = $app;
        $this->categoryRepo = $categoryRepo;
        $this->toolRepo = $toolRepo;
    }

    public function handle(Request $request): Response|string
    {
        if (!$this->isAuthorized()) {
            return Response::make('<h1>403 Access Denied</h1><p>You do not have permission to manage tool categories.</p>', 403);
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
            return Response::redirect('/admin/page/favorite-web-tools-categories');
        }

        $action = (string)$request->post('action', 'save');
        $id = (int)$request->post('id', 0);

        return match ($action) {
            'save'   => $this->saveCategory($request, $id),
            'delete' => $this->deleteCategory($request, $id),
            default  => Response::redirect('/admin/page/favorite-web-tools-categories'),
        };
    }

    public function index(Request $request): string
    {
        $categories = $this->categoryRepo->all();
        $toolCounts = [];
        foreach ($categories as $cat) {
            $catTools = $this->toolRepo->byCategory($cat->id);
            $toolCounts[$cat->id] = count($catTools);
        }

        $flashSuccess = $_SESSION['flash_success'] ?? null;
        $flashError   = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);

        return ViewRenderer::render('admin/categories/index', [
            'categories'   => $categories,
            'toolCounts'   => $toolCounts,
            'csrfToken'    => CsrfGuard::token(),
            'flashSuccess' => $flashSuccess,
            'flashError'   => $flashError,
        ]);
    }

    public function createForm(Request $request): string
    {
        $cat = new ToolCategory([
            'is_active'     => 1,
            'display_order' => 0,
        ]);

        $flashError = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_error']);

        return ViewRenderer::render('admin/categories/form', [
            'category'   => $cat,
            'csrfToken'  => CsrfGuard::token(),
            'isEdit'     => false,
            'flashError' => $flashError,
        ]);
    }

    public function editForm(Request $request, int $id): Response|string
    {
        $cat = $this->categoryRepo->find($id);
        if ($cat === null) {
            $_SESSION['flash_error'] = "Category #{$id} was not found.";
            return Response::redirect('/admin/page/favorite-web-tools-categories');
        }

        $flashSuccess = $_SESSION['flash_success'] ?? null;
        $flashError   = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);

        return ViewRenderer::render('admin/categories/form', [
            'category'     => $cat,
            'csrfToken'    => CsrfGuard::token(),
            'isEdit'       => true,
            'flashSuccess' => $flashSuccess,
            'flashError'   => $flashError,
        ]);
    }

    protected function saveCategory(Request $request, int $id): Response
    {
        $name = trim((string)$request->post('name', ''));
        $slug = trim((string)$request->post('slug', ''));
        $description = trim((string)$request->post('description', ''));
        $icon = trim((string)$request->post('icon', ''));
        $displayOrder = (int)$request->post('display_order', 0);
        $isActive = (int)$request->post('is_active', 1);

        if ($name === '') {
            $_SESSION['flash_error'] = 'Category Name is required.';
            return Response::redirect($id > 0 ? "/admin/page/favorite-web-tools-categories?action=edit&id={$id}" : '/admin/page/favorite-web-tools-categories?action=create');
        }

        if ($slug === '') {
            $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $name), '-'));
        }

        $existing = $this->categoryRepo->findBySlug($slug);
        if ($existing !== null && $existing->id !== $id) {
            $_SESSION['flash_error'] = "Slug '{$slug}' is already taken by another category.";
            return Response::redirect($id > 0 ? "/admin/page/favorite-web-tools-categories?action=edit&id={$id}" : '/admin/page/favorite-web-tools-categories?action=create');
        }

        $data = [
            'name'          => $name,
            'slug'          => $slug,
            'description'   => $description,
            'icon'          => $icon,
            'display_order' => $displayOrder,
            'is_active'     => $isActive,
        ];

        try {
            if ($id > 0) {
                $this->categoryRepo->update($id, $data);
                $_SESSION['flash_success'] = "Category '{$name}' was updated successfully.";
                return Response::redirect("/admin/page/favorite-web-tools-categories?action=edit&id={$id}");
            } else {
                $created = $this->categoryRepo->create($data);
                $_SESSION['flash_success'] = "Category '{$name}' was created successfully.";
                return Response::redirect("/admin/page/favorite-web-tools-categories?action=edit&id={$created->id}");
            }
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = 'Failed to save category: ' . $e->getMessage();
            return Response::redirect($id > 0 ? "/admin/page/favorite-web-tools-categories?action=edit&id={$id}" : '/admin/page/favorite-web-tools-categories?action=create');
        }
    }

    protected function deleteCategory(Request $request, int $id): Response
    {
        try {
            $this->categoryRepo->delete($id);
            $_SESSION['flash_success'] = "Category #{$id} was deleted.";
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = 'Failed to delete category: ' . $e->getMessage();
        }
        return Response::redirect('/admin/page/favorite-web-tools-categories');
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

