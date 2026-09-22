<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Tests;

use FavoriteCMS\Core\Application;
use FavoriteCMS\Core\Database;
use FavoriteCMS\Core\Request;
use FavoriteCMS\Tools\Controllers\Admin\AdminToolController;
use FavoriteCMS\Tools\Controllers\Frontend\CatalogController;
use FavoriteCMS\Tools\Controllers\Frontend\ToolController;
use FavoriteCMS\Tools\Handlers\PhpHandlerRegistry;
use FavoriteCMS\Tools\Models\Tool;
use FavoriteCMS\Tools\Models\ToolCategory;
use FavoriteCMS\Tools\Repositories\CategoryRepository;
use FavoriteCMS\Tools\Repositories\PythonServiceRepository;
use FavoriteCMS\Tools\Repositories\ToolRepository;
use FavoriteCMS\Tools\Services\AccessControlService;
use FavoriteCMS\Tools\Services\ToolExecutionService;
use FavoriteCMS\Tools\Services\ToolRegistryService;
use FavoriteCMS\Tools\Support\CsrfGuard;
use FavoriteCMS\Tools\Support\IconRenderer;
use PHPUnit\Framework\TestCase;

class UniversalToolAuthoringTest extends TestCase
{
    private ToolRepository $toolRepo;
    private CategoryRepository $catRepo;
    private PythonServiceRepository $pythonRepo;
    private ToolRegistryService $registry;
    private AccessControlService $accessControl;
    private ToolExecutionService $executionService;
    private Application $app;

    /** @var ToolCategory[] */
    private array $categories = [];

    /** @var Tool[] */
    private array $tools = [];

    protected function setUp(): void
    {
        parent::setUp();

        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        $_SESSION['_token'] = 'test-token-universal-authoring';
        $_SESSION['csrf_token'] = 'test-token-universal-authoring';
        $_SESSION['auth_user_id'] = 1;

        $mockUser = new class {
            public int $id = 1;
            public function can(string $cap): bool { return true; }
        };
        $GLOBALS['_test_current_user'] = $mockUser;

        $this->categories = [
            1 => new ToolCategory(['id' => 1, 'name' => 'HTML', 'slug' => 'html', 'icon' => 'code', 'status' => 'active']),
            2 => new ToolCategory(['id' => 2, 'name' => 'CSS', 'slug' => 'css', 'icon' => 'palette', 'status' => 'active']),
            3 => new ToolCategory(['id' => 3, 'name' => 'JavaScript', 'slug' => 'javascript', 'icon' => 'terminal', 'status' => 'active']),
            4 => new ToolCategory(['id' => 4, 'name' => 'Developer', 'slug' => 'developer', 'icon' => 'cpu', 'status' => 'active']),
            5 => new ToolCategory(['id' => 5, 'name' => 'Text', 'slug' => 'text', 'icon' => 'file-text', 'status' => 'active']),
            6 => new ToolCategory(['id' => 6, 'name' => 'PHP', 'slug' => 'php', 'icon' => 'server', 'status' => 'active']),
            7 => new ToolCategory(['id' => 7, 'name' => 'Python', 'slug' => 'python', 'icon' => 'activity', 'status' => 'active']),
        ];

        $this->tools = [
            1 => new Tool([
                'id' => 1, 'name' => 'JSON Formatter', 'slug' => 'json-formatter', 'category_id' => 4,
                'engine' => 'PHP', 'access_mode' => 'FREE', 'status' => 'ACTIVE',
                'handler_class' => 'FavoriteCMS\\Tools\\Handlers\\JsonFormatterHandler',
            ]),
            2 => new Tool([
                'id' => 2, 'name' => 'HTML Minifier', 'slug' => 'html-minifier', 'category_id' => 1,
                'engine' => 'HTML', 'access_mode' => 'FREE', 'status' => 'ACTIVE',
            ]),
        ];

        // Mock ToolRepository
        $this->toolRepo = $this->createMock(ToolRepository::class);
        $this->toolRepo->method('all')->willReturnCallback(fn() => array_values($this->tools));
        $this->toolRepo->method('findById')->willReturnCallback(fn($id) => $this->tools[$id] ?? null);
        $this->toolRepo->method('find')->willReturnCallback(fn($id) => $this->tools[$id] ?? null);
        $this->toolRepo->method('findBySlug')->willReturnCallback(function($slug) {
            foreach ($this->tools as $t) {
                if ($t->slug === $slug) return $t;
            }
            return null;
        });

        // Mock CategoryRepository
        $this->catRepo = $this->createMock(CategoryRepository::class);
        $this->catRepo->method('all')->willReturn(array_values($this->categories));
        $this->catRepo->method('allActive')->willReturn(array_values($this->categories));
        $this->catRepo->method('find')->willReturnCallback(fn($id) => $this->categories[$id] ?? null);
        $this->catRepo->method('findBySlug')->willReturnCallback(function($slug) {
            foreach ($this->categories as $c) {
                if ($c->slug === $slug) return $c;
            }
            return null;
        });

        // Mock PythonServiceRepository
        $this->pythonRepo = $this->createMock(PythonServiceRepository::class);
        $this->pythonRepo->method('all')->willReturn([]);

        // Mock ToolRegistryService
        $this->registry = $this->createMock(ToolRegistryService::class);
        $this->registry->method('findBySlug')->willReturnCallback(function($slug) {
            foreach ($this->tools as $t) {
                if ($t->slug === $slug) return $t;
            }
            return null;
        });
        $this->registry->method('getActiveTools')->willReturnCallback(function() {
            return array_values(array_filter($this->tools, fn($t) => $t->isActive()));
        });
        $this->registry->method('getActiveToolsByCategory')->willReturnCallback(function($catId) {
            return array_values(array_filter($this->tools, fn($t) => $t->isActive() && $t->category_id === $catId));
        });
        $this->registry->method('searchTools')->willReturnCallback(function($filters, $page, $perPage) {
            $items = array_values(array_filter($this->tools, fn($t) => $t->isActive()));
            return [
                'items' => $items,
                'total' => count($items),
                'page' => 1,
                'perPage' => 12,
                'totalPages' => 1,
            ];
        });

        $this->accessControl = new AccessControlService();
        $this->executionService = $this->createMock(ToolExecutionService::class);
        $this->app = $this->createMock(Application::class);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['_test_current_user']);
        parent::tearDown();
    }

    public function testIconRendererMapsCategoryIdentifiersToSvg(): void
    {
        $identifiers = ['code', 'html', 'palette', 'css', 'terminal', 'javascript', 'cpu', 'developer', 'file-text', 'server', 'php', 'activity', 'python', 'grid', 'qrcode'];

        foreach ($identifiers as $id) {
            $rendered = IconRenderer::render($id);
            $this->assertStringContainsString('<svg', $rendered, "Identifier '$id' should render an SVG");
            $this->assertStringContainsString('aria-hidden="true"', $rendered);
            $this->assertStringNotContainsString("code HTML", $rendered);
            $this->assertStringNotContainsString("palette CSS", $rendered);
        }
    }

    public function testIconRendererHandlesEmojisGracefully(): void
    {
        $rendered = IconRenderer::render('🛠️');
        $this->assertStringContainsString('🛠️', $rendered);
        $this->assertStringContainsString('fwt-cat-icon', $rendered);
    }

    public function testCatalogControllerComputesTotalAllToolsAccurately(): void
    {
        $controller = new CatalogController($this->app, $this->registry, $this->catRepo, $this->accessControl);
        $req = new Request([], [], [], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/tools']);

        $response = $controller->index($req);
        $html = is_string($response) ? $response : (string)$response->getContent();

        // Must contain All Tools with total count
        $this->assertStringContainsString('All Tools', $html);
        $this->assertStringContainsString('fwt-chip', $html);
        $this->assertStringContainsString('fwt-chip-count', $html);
    }

    public function testAdminToolControllerProvidesRegisteredHandlers(): void
    {
        $controller = new AdminToolController($this->app, $this->toolRepo, $this->catRepo, $this->pythonRepo, $this->executionService);
        $req = new Request(['action' => 'create'], [], [], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/admin/page/favorite-web-tools?action=create']);

        $html = $controller->createForm($req);

        // Must list controlled handlers
        $this->assertStringContainsString('Universal Tool Builder', $html);
        $this->assertStringContainsString('json_formatter', $html);
        $this->assertStringContainsString('base64_encoder', $html);
        $this->assertStringContainsString('uuid_generator', $html);
        $this->assertStringContainsString('updateEnginePanels', $html);
    }

    public function testAdminToolCreationWithJavaScriptEngineAndExternalLibraries(): void
    {
        $createdData = null;
        $this->toolRepo = $this->createMock(ToolRepository::class);
        $this->toolRepo->method('findBySlug')->willReturn(null);
        $this->toolRepo->method('create')->willReturnCallback(function($data) use (&$createdData) {
            $createdData = $data;
            return new Tool(array_merge($data, ['id' => 99]));
        });

        $controller = new AdminToolController($this->app, $this->toolRepo, $this->catRepo, $this->pythonRepo, $this->executionService);

        $postData = [
            '_token'             => 'test-token-universal-authoring',
            'action'             => 'save',
            'name'               => 'QR Code Generator',
            'slug'               => 'qr-code-generator',
            'description'        => 'Generate custom QR codes with batch CSV export',
            'category_id'        => '4',
            'engine'             => 'JAVASCRIPT',
            'access_mode'        => 'FREE',
            'status'             => 'ACTIVE',
            'icon'               => 'qrcode',
            'external_libraries' => "https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js\nhttps://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js\njavascript:alert(1)",
            'html_source'        => '<div id="qr-workspace"><input id="qr-text"><button id="qr-gen-btn">Generate</button><div id="qr-output"></div></div>',
            'css_source'         => '#qr-workspace { padding: 20px; border-radius: 8px; }',
            'js_source'          => 'document.getElementById("qr-gen-btn").onclick = function() { new QRCode(document.getElementById("qr-output"), document.getElementById("qr-text").value); };',
        ];

        $req = Request::create('POST', '/admin/page/favorite-web-tools', $postData);
        $resp = $controller->handle($req);

        $this->assertEquals(302, $resp->getStatusCode());
        $this->assertNotNull($createdData);
        $this->assertEquals('QR Code Generator', $createdData['name']);
        $this->assertEquals('JAVASCRIPT', $createdData['engine']);
        $this->assertEquals('FREE', $createdData['access_mode']);
        $this->assertEquals('ACTIVE', $createdData['status']);
        $this->assertStringContainsString('qr-gen-btn', $createdData['html_source']);
        $this->assertStringContainsString('qr-workspace', $createdData['css_source']);
        $this->assertStringContainsString('QRCode', $createdData['js_source']);

        // Check external libraries sanitization: only https:// URLs allowed, javascript: rejected
        $this->assertCount(2, $createdData['external_libraries']);
        $this->assertEquals('https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js', $createdData['external_libraries'][0]);
        $this->assertEquals('https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js', $createdData['external_libraries'][1]);
    }

    public function testAdminToolCreationRejectsInvalidJsonSchemaGracefully(): void
    {
        $controller = new AdminToolController($this->app, $this->toolRepo, $this->catRepo, $this->pythonRepo, $this->executionService);

        $postData = [
            '_token'       => 'test-token-universal-authoring',
            'action'       => 'save',
            'name'         => 'Broken Schema Tool',
            'slug'         => 'broken-schema',
            'engine'       => 'PHP',
            'input_schema' => '{ invalid json here: missing quotes }',
        ];

        $req = Request::create('POST', '/admin/page/favorite-web-tools', $postData);
        $resp = $controller->handle($req);

        // Must redirect with error, not fatal error or uncaught exception
        $this->assertEquals(302, $resp->getStatusCode());
        $this->assertStringContainsString('Invalid JSON in Input Schema', $_SESSION['flash_error'] ?? '');
    }

    public function testFrontendRendersCustomToolWorkspaceAndExternalLibraries(): void
    {
        $qrTool = new Tool([
            'id'                 => 10,
            'name'               => 'QR Code Generator',
            'slug'               => 'qr-code-generator',
            'category_id'        => 4,
            'engine'             => 'JAVASCRIPT',
            'access_mode'        => 'FREE',
            'status'             => 'ACTIVE',
            'icon'               => 'qrcode',
            'html_source'        => '<div id="qr-live-workspace">Interactive QR Generator Interface</div>',
            'css_source'         => '#qr-live-workspace { background: #fafafa; }',
            'js_source'          => 'console.log("QR Ready");',
            'external_libraries' => ['https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js'],
        ]);

        $this->tools[10] = $qrTool;

        $controller = new ToolController($this->app, $this->registry, $this->catRepo, $this->accessControl);
        $req = new Request([], [], [], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/tools/qr-code-generator']);

        $response = $controller->show($req, 'qr-code-generator');
        $html = is_string($response) ? $response : (string)$response->getContent();

        // Must render custom workspace, scoped CSS, external library script tag, and JS
        $this->assertStringContainsString('Interactive QR Generator Interface', $html);
        $this->assertStringContainsString('#qr-live-workspace { background: #fafafa; }', $html);
        $this->assertStringContainsString('https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js', $html);
        $this->assertStringContainsString('console.log("QR Ready");', $html);
        $this->assertStringContainsString('fwt-custom-tool-workspace', $html);
    }
}
