<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Controllers\Admin;

use FavoriteCMS\Core\Application;
use FavoriteCMS\Core\Request;
use FavoriteCMS\Core\Response;
use FavoriteCMS\Models\User;
use FavoriteCMS\Tools\Models\FrontendDesign;
use FavoriteCMS\Tools\Normalizers\NormalizerResolver;
use FavoriteCMS\Tools\Repositories\FrontendDesignRepository;
use FavoriteCMS\Tools\Repositories\ToolRepository;
use FavoriteCMS\Tools\Services\DesignPackageImporter;
use FavoriteCMS\Tools\Support\CsrfGuard;
use FavoriteCMS\Tools\Support\CssScoper;
use FavoriteCMS\Tools\Support\SafeTemplateRenderer;
use FavoriteCMS\Tools\Support\ViewRenderer;
use Throwable;

class AdminFrontendDesignController
{
    protected Application $app;
    protected FrontendDesignRepository $designRepo;
    protected ToolRepository $toolRepo;
    protected DesignPackageImporter $importer;

    public function __construct(
        Application $app,
        FrontendDesignRepository $designRepo,
        ToolRepository $toolRepo,
        DesignPackageImporter $importer
    ) {
        $this->app = $app;
        $this->designRepo = $designRepo;
        $this->toolRepo = $toolRepo;
        $this->importer = $importer;
    }

    public function handle(Request $request): Response|string
    {
        if (!$this->isAuthorized()) {
            return Response::make('<h1>403 Access Denied</h1><p>You do not have permission to manage frontend designs.</p>', 403);
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
            'create'  => $this->createForm($request),
            'edit'    => $this->editForm($request, $id),
            'import'  => $this->importForm($request),
            'export'  => $this->handleExport($request, $id),
            'preview' => $this->previewScreen($request, $id),
            default   => $this->index($request),
        };
    }

    protected function handlePost(Request $request): Response
    {
        if (!CsrfGuard::verify($request)) {
            $_SESSION['flash_error'] = 'Security token invalid or expired (CSRF failure). Please try again.';
            return Response::redirect('/admin/web-tools/frontend-designs');
        }

        $action = (string)$request->post('action', 'save');
        $id = (int)$request->post('id', 0);

        return match ($action) {
            'save'           => $this->saveDesign($request, $id),
            'import'         => $this->handleImport($request),
            'delete'         => $this->deleteDesign($request, $id),
            'duplicate'      => $this->duplicateDesign($request, $id),
            'toggle_status'  => $this->toggleStatus($request, $id),
            'render_preview' => $this->previewRenderAjax($request),
            default          => Response::redirect('/admin/web-tools/frontend-designs'),
        };
    }

    public function index(Request $request): string
    {
        $designs = $this->designRepo->all();
        $usageCounts = [];
        foreach ($designs as $d) {
            $usageCounts[$d->getSlug()] = $this->designRepo->getUsageCount($d->getSlug());
        }

        $flashSuccess = $_SESSION['flash_success'] ?? null;
        $flashError   = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);

        return ViewRenderer::render('admin/frontend-designs/index', [
            'designs'      => $designs,
            'usageCounts'  => $usageCounts,
            'csrfToken'    => CsrfGuard::token(),
            'flashSuccess' => $flashSuccess,
            'flashError'   => $flashError,
        ]);
    }

    public function createForm(Request $request): string
    {
        $design = new FrontendDesign([
            'is_builtin' => 0,
            'is_active'  => 1,
            'js_enabled' => 0,
            'version'    => '1.0.0',
            'category'   => 'Custom',
        ]);

        $flashError = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_error']);

        return ViewRenderer::render('admin/frontend-designs/form', [
            'design'     => $design,
            'isEdit'     => false,
            'csrfToken'  => CsrfGuard::token(),
            'flashError' => $flashError,
        ]);
    }

    public function editForm(Request $request, int $id): Response|string
    {
        $design = $this->designRepo->find($id);
        if ($design === null) {
            $_SESSION['flash_error'] = "Design #{$id} was not found.";
            return Response::redirect('/admin/web-tools/frontend-designs');
        }

        $flashSuccess = $_SESSION['flash_success'] ?? null;
        $flashError   = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);

        return ViewRenderer::render('admin/frontend-designs/form', [
            'design'       => $design,
            'isEdit'       => true,
            'usageCount'   => $this->designRepo->getUsageCount($design->getSlug()),
            'csrfToken'    => CsrfGuard::token(),
            'flashSuccess' => $flashSuccess,
            'flashError'   => $flashError,
        ]);
    }

    public function importForm(Request $request): string
    {
        $flashError = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_error']);

        return ViewRenderer::render('admin/frontend-designs/import', [
            'csrfToken'  => CsrfGuard::token(),
            'flashError' => $flashError,
        ]);
    }

    public function handleImport(Request $request): Response
    {
        if (empty($_FILES['package']['tmp_name']) || !is_uploaded_file($_FILES['package']['tmp_name'])) {
            $_SESSION['flash_error'] = 'Please select a valid design ZIP package file to upload.';
            return Response::redirect('/admin/web-tools/frontend-designs?action=import');
        }

        $overwrite = !empty($request->post('overwrite'));

        try {
            $imported = $this->importer->importFromZip($_FILES['package']['tmp_name'], $overwrite);
            $_SESSION['flash_success'] = "Design '{$imported->getName()}' ({$imported->getSlug()}) imported successfully.";
            return Response::redirect('/admin/web-tools/frontend-designs');
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = 'Import failed: ' . $e->getMessage();
            return Response::redirect('/admin/web-tools/frontend-designs?action=import');
        }
    }

    public function handleExport(Request $request, int $id): Response
    {
        $design = $this->designRepo->find($id);
        if ($design === null) {
            $_SESSION['flash_error'] = "Design #{$id} was not found.";
            return Response::redirect('/admin/web-tools/frontend-designs');
        }

        $tempFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'design-' . $design->getSlug() . '.zip';
        try {
            $this->importer->exportToZip($design, $tempFile);
            $content = file_get_contents($tempFile);
            @unlink($tempFile);

            $filename = 'design-' . $design->getSlug() . '-v' . $design->getVersion() . '.zip';
            return new Response((string)$content, 200, [
                'Content-Type'        => 'application/zip',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Content-Length'      => (string)strlen((string)$content),
            ]);
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = 'Export failed: ' . $e->getMessage();
            return Response::redirect('/admin/web-tools/frontend-designs');
        }
    }

    public function saveDesign(Request $request, int $id): Response
    {
        $name = trim((string)$request->post('name', ''));
        $slug = trim((string)$request->post('slug', ''));
        $description = trim((string)$request->post('description', ''));
        $category = trim((string)$request->post('category', 'Custom'));
        $version = trim((string)$request->post('version', '1.0.0'));
        $author = trim((string)$request->post('author', ''));
        $templateHtml = (string)$request->post('template_html', '');
        $cssContent = (string)$request->post('css_content', '');
        $jsContent = (string)$request->post('js_content', '');
        $isActive = !empty($request->post('is_active')) ? 1 : 0;
        $jsEnabled = !empty($request->post('js_enabled')) ? 1 : 0;

        if ($name === '') {
            $_SESSION['flash_error'] = 'Design Name is required.';
            return Response::redirect($id > 0 ? "/admin/web-tools/frontend-designs?action=edit&id={$id}" : '/admin/web-tools/frontend-designs?action=create');
        }

        if ($slug === '') {
            $slug = strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '-', $name));
            $slug = trim(preg_replace('/-+/', '-', $slug), '-');
        }

        if (trim($templateHtml) === '') {
            $_SESSION['flash_error'] = 'HTML Template content cannot be empty.';
            return Response::redirect($id > 0 ? "/admin/web-tools/frontend-designs?action=edit&id={$id}" : '/admin/web-tools/frontend-designs?action=create');
        }

        // Validate and scope CSS
        try {
            if (trim($cssContent) !== '') {
                $cssContent = CssScoper::validateAndScope($cssContent, $slug);
            }
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = 'CSS Validation Error: ' . $e->getMessage();
            return Response::redirect($id > 0 ? "/admin/web-tools/frontend-designs?action=edit&id={$id}" : '/admin/web-tools/frontend-designs?action=create');
        }

        $data = [
            'name'          => $name,
            'slug'          => $slug,
            'description'   => $description,
            'category'      => $category,
            'version'       => $version,
            'author'        => $author,
            'template_html' => $templateHtml,
            'css_content'   => $cssContent,
            'js_content'    => $jsContent,
            'is_active'     => $isActive,
            'js_enabled'    => $jsEnabled,
        ];

        try {
            if ($id > 0) {
                $existing = $this->designRepo->find($id);
                if ($existing === null) {
                    $_SESSION['flash_error'] = "Design #{$id} not found.";
                    return Response::redirect('/admin/web-tools/frontend-designs');
                }

                // If builtin, retain builtin status
                $data['is_builtin'] = $existing->isBuiltin() ? 1 : 0;

                $this->designRepo->update($id, $data);
                $_SESSION['flash_success'] = "Design '{$name}' was updated successfully.";
                return Response::redirect("/admin/web-tools/frontend-designs?action=edit&id={$id}");
            } else {
                $data['is_builtin'] = 0;
                $newDesign = $this->designRepo->create($data);
                $_SESSION['flash_success'] = "Design '{$name}' created successfully.";
                return Response::redirect("/admin/web-tools/frontend-designs?action=edit&id={$newDesign->getId()}");
            }
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = 'Error saving design: ' . $e->getMessage();
            return Response::redirect($id > 0 ? "/admin/web-tools/frontend-designs?action=edit&id={$id}" : '/admin/web-tools/frontend-designs?action=create');
        }
    }

    public function duplicateDesign(Request $request, int $id): Response
    {
        try {
            $copy = $this->designRepo->duplicate($id);
            $_SESSION['flash_success'] = "Design duplicated successfully as '{$copy->getName()}'.";
            return Response::redirect("/admin/web-tools/frontend-designs?action=edit&id={$copy->getId()}");
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = 'Duplication failed: ' . $e->getMessage();
            return Response::redirect('/admin/web-tools/frontend-designs');
        }
    }

    public function deleteDesign(Request $request, int $id): Response
    {
        try {
            $this->designRepo->delete($id);
            $_SESSION['flash_success'] = 'Design was deleted successfully.';
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = 'Cannot delete design: ' . $e->getMessage();
        }
        return Response::redirect('/admin/web-tools/frontend-designs');
    }

    public function toggleStatus(Request $request, int $id): Response
    {
        $design = $this->designRepo->find($id);
        if ($design) {
            $newStatus = $design->isActive() ? 0 : 1;
            $this->designRepo->update($id, ['is_active' => $newStatus]);
            $_SESSION['flash_success'] = "Design '{$design->getName()}' " . ($newStatus ? 'activated' : 'deactivated') . ".";
        }
        return Response::redirect('/admin/web-tools/frontend-designs');
    }

    public function previewScreen(Request $request, int $id): string
    {
        $design = $this->designRepo->find($id);
        if (!$design) {
            $design = $this->designRepo->findBySlug('media-downloader-cards') ?: $this->designRepo->all()[0] ?? new FrontendDesign();
        }

        $allDesigns = $this->designRepo->allActive();

        return ViewRenderer::render('admin/frontend-designs/preview', [
            'currentDesign' => $design,
            'allDesigns'    => $allDesigns,
            'csrfToken'     => CsrfGuard::token(),
        ]);
    }

    public function previewRenderAjax(Request $request): Response
    {
        $slug = (string)$request->post('slug', 'media-downloader-cards');
        $rawJson = (string)$request->post('mock_json', '');

        $design = $this->designRepo->findBySlug($slug);
        if (!$design) {
            return new Response(json_encode(['error' => 'Design not found']), 404, ['Content-Type' => 'application/json']);
        }

        $data = json_decode($rawJson, true);
        if (!is_array($data)) {
            $data = ['error' => 'Invalid JSON input'];
        }

        // Normalize data
        $resolver = new NormalizerResolver();
        $normalized = $resolver->normalize($data, $design->getSlug());

        // Render template
        $renderedHtml = SafeTemplateRenderer::render($design->getTemplateHtml(), $normalized);

        return new Response(json_encode([
            'success'       => true,
            'rendered_html' => $renderedHtml,
            'css'           => $design->getCssContent(),
            'slug'          => $design->getSlug(),
            'normalized'    => $normalized,
        ]), 200, ['Content-Type' => 'application/json']);
    }

    protected function isAuthorized(): bool
    {
        if (function_exists('is_admin_logged_in') && is_admin_logged_in()) {
            return true;
        }
        if (class_exists(User::class) && method_exists(User::class, 'current')) {
            $user = User::current();
            if ($user !== null && method_exists($user, 'isAdmin') && $user->isAdmin()) {
                return true;
            }
        }
        if (isset($_SESSION['user_id']) && !empty($_SESSION['is_admin'])) {
            return true;
        }
        return false;
    }
}

