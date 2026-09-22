<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Tests;

use Exception;
use FavoriteCMS\Core\Application;
use FavoriteCMS\Core\Request;
use FavoriteCMS\Core\Response;
use FavoriteCMS\Tools\Controllers\Admin\AdminToolController;
use FavoriteCMS\Tools\Controllers\Api\DownloadApiController;
use FavoriteCMS\Tools\Controllers\Api\ToolApiController;
use FavoriteCMS\Tools\Controllers\Api\ToolExecutionApiController;
use FavoriteCMS\Tools\Controllers\Frontend\ToolController;
use FavoriteCMS\Tools\Engines\EngineResolver;
use FavoriteCMS\Tools\Engines\PhpEngine;
use FavoriteCMS\Tools\Handlers\PhpHandlerRegistry;
use FavoriteCMS\Tools\Models\PythonService;
use FavoriteCMS\Tools\Models\Tool;
use FavoriteCMS\Tools\Models\ToolCategory;
use FavoriteCMS\Tools\Repositories\CategoryRepository;
use FavoriteCMS\Tools\Repositories\PythonServiceRepository;
use FavoriteCMS\Tools\Repositories\ToolRepository;
use FavoriteCMS\Tools\Services\AccessControlService;
use FavoriteCMS\Tools\Services\DownloadManagerService;
use FavoriteCMS\Tools\Services\ToolExecutionService;
use FavoriteCMS\Tools\Services\ToolRegistryService;
use FavoriteCMS\Tools\Support\AccessMode;
use FavoriteCMS\Tools\Support\CsrfGuard;
use FavoriteCMS\Tools\Support\EngineType;
use FavoriteCMS\Tools\Support\ToolStatus;
use PDOException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Security Hardening Regression Test Suite
 * Covers all 17 specified hardening requirements for Favorite Web Tools.
 */
class SecurityHardeningRegressionTest extends TestCase
{
    private Application $app;
    private ToolRepository $toolRepo;
    private CategoryRepository $catRepo;
    private PythonServiceRepository $pythonRepo;
    private ToolRegistryService $registry;
    private AccessControlService $accessControl;
    private DownloadManagerService $downloadManager;
    private ToolExecutionService $executionService;
    private EngineResolver $engineResolver;

    /** @var array<int, Tool> */
    private array $tools = [];

    /** @var array<int, ToolCategory> */
    private array $categories = [];

    /** @var array<int, PythonService> */
    private array $pythonServices = [];

    private string $tempStorageDir;

    protected function setUp(): void
    {
        parent::setUp();

        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        $_SESSION['_token'] = 'test-security-token';
        $_SESSION['csrf_token'] = 'test-security-token';
        $_SESSION['auth_user_id'] = 1;

        $this->tempStorageDir = sys_get_temp_dir() . '/fwt_test_sec_' . bin2hex(random_bytes(4));
        @mkdir($this->tempStorageDir, 0755, true);

        $this->categories = [
            1 => new ToolCategory(['id' => 1, 'name' => 'Developer', 'slug' => 'developer', 'icon' => 'cpu', 'status' => 'active']),
            2 => new ToolCategory(['id' => 2, 'name' => 'Text', 'slug' => 'text', 'icon' => 'file-text', 'status' => 'active']),
        ];

        $this->pythonServices = [
            1 => new PythonService([
                'id'        => 1,
                'name'      => 'Internal Python Cluster',
                'base_url'  => 'https://python.internal.favoriteweb.net:8443',
                'api_key'   => 'sk-supersecret-production-key-999',
                'auth_type' => 'bearer',
                'timeout'   => 15,
                'status'    => 'active',
            ]),
        ];

        $this->tools = [
            1 => new Tool([
                'id'            => 1,
                'category_id'   => 1,
                'name'          => 'JSON Formatter',
                'slug'          => 'json-formatter',
                'description'   => 'Format and beautify JSON',
                'engine'        => EngineType::PHP,
                'access_mode'   => AccessMode::FREE,
                'status'        => ToolStatus::ACTIVE,
                'handler_class' => 'FavoriteCMS\\Tools\\Handlers\\JsonFormatterHandler',
            ]),
            2 => new Tool([
                'id'            => 2,
                'category_id'   => 1,
                'name'          => 'QR Code Generator',
                'slug'          => 'qr-code-generator',
                'description'   => 'Generate custom QR codes with batch CSV export',
                'engine'        => EngineType::JAVASCRIPT,
                'access_mode'   => AccessMode::FREE,
                'status'        => ToolStatus::ACTIVE,
                'html_source'   => '<div id="qr-test-workspace"><input id="qr-input" value="hello"><button id="qr-btn">Gen</button></div>',
                'css_source'    => '#qr-test-workspace { padding: 10px; }',
                'js_source'     => 'window.qrGenerated = true;',
                'external_libraries' => ['https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js'],
            ]),
            3 => new Tool([
                'id'            => 3,
                'category_id'   => 1,
                'name'          => 'AI Content Analyzer',
                'slug'          => 'ai-content-analyzer',
                'description'   => 'Deep Python NLP analysis',
                'engine'        => EngineType::PYTHON_API,
                'access_mode'   => AccessMode::FREE,
                'status'        => ToolStatus::ACTIVE,
                'configuration' => [
                    'python_service_id' => 1,
                    'endpoint'          => '/analyze',
                ],
            ]),
            4 => new Tool([
                'id'            => 4,
                'category_id'   => 1,
                'name'          => 'Code Minifier',
                'slug'          => 'code-minifier',
                'description'   => 'Related developer tool',
                'engine'        => EngineType::PHP,
                'access_mode'   => AccessMode::FREE,
                'status'        => ToolStatus::ACTIVE,
                'handler_class' => 'FavoriteCMS\\Tools\\Handlers\\PhpMinifierHandler',
            ]),
        ];

        $this->toolRepo = $this->createMock(ToolRepository::class);
        $this->toolRepo->method('all')->willReturnCallback(fn() => array_values($this->tools));
        $this->toolRepo->method('findById')->willReturnCallback(fn($id) => $this->tools[$id] ?? null);
        $this->toolRepo->method('find')->willReturnCallback(fn($id) => $this->tools[$id] ?? null);
        $this->toolRepo->method('findBySlug')->willReturnCallback(function ($slug) {
            foreach ($this->tools as $t) {
                if ($t->slug === $slug) return $t;
            }
            return null;
        });
        $this->toolRepo->method('getPublicTools')->willReturnCallback(function() {
            return [
                'items'      => array_values($this->tools),
                'total'      => count($this->tools),
                'page'       => 1,
                'perPage'    => 12,
                'totalPages' => 1,
            ];
        });
        $this->toolRepo->method('getAllAdminTools')->willReturnCallback(fn() => array_values($this->tools));

        $this->catRepo = $this->createMock(CategoryRepository::class);
        $this->catRepo->method('all')->willReturn(array_values($this->categories));
        $this->catRepo->method('allActive')->willReturn(array_values($this->categories));
        $this->catRepo->method('find')->willReturnCallback(fn($id) => $this->categories[$id] ?? null);
        $this->catRepo->method('findBySlug')->willReturnCallback(function ($slug) {
            foreach ($this->categories as $c) {
                if ($c->slug === $slug) return $c;
            }
            return null;
        });

        $this->pythonRepo = $this->createMock(PythonServiceRepository::class);
        $this->pythonRepo->method('all')->willReturn(array_values($this->pythonServices));
        $this->pythonRepo->method('getAll')->willReturn(array_values($this->pythonServices));
        $this->pythonRepo->method('findById')->willReturnCallback(fn($id) => $this->pythonServices[$id] ?? null);
        $this->pythonRepo->method('find')->willReturnCallback(fn($id) => $this->pythonServices[$id] ?? null);

        $this->registry = new ToolRegistryService($this->toolRepo, $this->catRepo);
        $this->accessControl = new AccessControlService();
        $this->engineResolver = new EngineResolver(null, null, null, null, new \FavoriteCMS\Tools\Engines\PythonApiEngine($this->pythonRepo));
        $this->downloadManager = new DownloadManagerService($this->tempStorageDir);
        $this->executionService = new ToolExecutionService($this->toolRepo, $this->accessControl, $this->engineResolver, $this->downloadManager);

        $this->app = $this->createMock(Application::class);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempStorageDir)) {
            $files = glob($this->tempStorageDir . '/*');
            foreach ($files ?: [] as $f) {
                if (is_file($f)) @unlink($f);
            }
            @rmdir($this->tempStorageDir);
        }
        parent::tearDown();
    }

    /**
     * Requirement 1: PHP source cannot be downloaded.
     */
    public function testRequirement01PhpSourceCannotBeDownloaded(): void
    {
        $pluginDir = dirname(__DIR__);
        $rootHtaccess = file_get_contents($pluginDir . '/.htaccess');
        $this->assertNotEmpty($rootHtaccess);
        $this->assertStringContainsString('php', $rootHtaccess);
        $this->assertStringContainsString('Require all denied', $rootHtaccess);

        $assetsHtaccess = file_get_contents($pluginDir . '/assets/.htaccess');
        $this->assertNotEmpty($assetsHtaccess);
        $this->assertStringContainsString('php', $assetsHtaccess);

        // Verify DownloadApiController cannot download PHP source files
        $controller = new DownloadApiController($this->app, $this->downloadManager);
        $req = Request::create('GET', '/api/tools/download/autoload.php');
        $resp = $controller->download($req, 'autoload.php');
        $this->assertEquals(404, $resp->getStatusCode());
    }

    /**
     * Requirement 2: .env cannot be downloaded.
     */
    public function testRequirement02EnvCannotBeDownloaded(): void
    {
        $pluginDir = dirname(__DIR__);
        $rootHtaccess = file_get_contents($pluginDir . '/.htaccess');
        $this->assertStringContainsString('env', $rootHtaccess);

        $controller = new DownloadApiController($this->app, $this->downloadManager);
        $req = Request::create('GET', '/api/tools/download/.env');
        $resp = $controller->download($req, '.env');
        $this->assertEquals(404, $resp->getStatusCode());
    }

    /**
     * Requirement 3: Internal source directories cannot be accessed publicly.
     */
    public function testRequirement03InternalSourceDirectoriesCannotBeAccessed(): void
    {
        $pluginDir = dirname(__DIR__);
        $rootHtaccess = file_get_contents($pluginDir . '/.htaccess');

        $this->assertStringContainsString('RewriteRule ^(database|migrations|src|tests|views|release)/ - [F,L]', $rootHtaccess);
    }

    /**
     * Requirement 4: Source maps are not served.
     */
    public function testRequirement04SourceMapsAreNotServed(): void
    {
        $pluginDir = dirname(__DIR__);
        $rootHtaccess = file_get_contents($pluginDir . '/.htaccess');
        $this->assertStringContainsString('map', $rootHtaccess);

        $assetsHtaccess = file_get_contents($pluginDir . '/assets/.htaccess');
        $this->assertStringContainsString('map', $assetsHtaccess);

        // Verify zero .map files exist in assets
        $dir = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($pluginDir . '/assets'));
        foreach ($dir as $file) {
            if ($file->isFile()) {
                $this->assertNotEquals('map', $file->getExtension(), "Found source map: " . $file->getPathname());
            }
        }
    }

    /**
     * Requirement 5: API responses do not expose secrets.
     */
    public function testRequirement05ApiResponsesDoNotExposeSecrets(): void
    {
        $controller = new ToolApiController($this->app, $this->registry, $this->catRepo);

        // Index
        $req = Request::create('GET', '/api/tools');
        $resp = $controller->index($req);
        $data = json_decode((string)$resp->getContent(), true);

        $jsonStr = json_encode($data);
        $this->assertStringNotContainsString('sk-supersecret', $jsonStr);
        $this->assertStringNotContainsString('api_key', $jsonStr);
        $this->assertStringNotContainsString('password', $jsonStr);
        $this->assertStringNotContainsString('python_service_id', $jsonStr);

        // Show single tool (including Python API tool)
        $respShow = $controller->show($req, 'ai-content-analyzer');
        $showData = json_decode((string)$respShow->getContent(), true);
        $showJsonStr = json_encode($showData);

        $this->assertStringNotContainsString('sk-supersecret', $showJsonStr);
        $this->assertStringNotContainsString('api_key', $showJsonStr);
        $this->assertStringNotContainsString('configuration', $showJsonStr);
        $this->assertStringNotContainsString('handler_class', $showJsonStr);
    }

    /**
     * Requirement 6: API responses do not expose filesystem paths.
     */
    public function testRequirement06ApiResponsesDoNotExposeFilesystemPaths(): void
    {
        $controller = new ToolExecutionApiController($this->app, $this->executionService);

        // Valid execution
        $req = Request::create('POST', '/api/tools/json-formatter/execute', [
            'inputs' => ['json' => '{"name":"Favorite"}'],
        ]);
        $resp = $controller->execute($req, 'json-formatter');
        $content = (string)$resp->getContent();

        $this->assertDoesNotMatchRegularExpression('/[a-zA-Z]:[\\\\\/]/', $content);
        $this->assertStringNotContainsString('/var/www', $content);
        $this->assertStringNotContainsString('/plugins/favorite-web-tools/src', $content);

        // Error sanitization
        $msgWithPaths = 'Fatal error in E:\\Favorite-CMS-Assets\\plugins\\favorite-web-tools\\src\\Engine.php line 42';
        $sanitized = ToolExecutionService::sanitizeMessageString($msgWithPaths);
        $this->assertStringNotContainsString('E:\\Favorite-CMS-Assets', $sanitized);
        $this->assertStringContainsString('[path]', $sanitized);
    }

    /**
     * Requirement 7: Unauthorized admin builder access is rejected.
     */
    public function testRequirement07UnauthorizedAdminBuilderAccessIsRejected(): void
    {
        // Simulate non-admin / unauthenticated user
        $GLOBALS['_test_current_user'] = new class {
            public int $id = 999;
            public function can(string $cap): bool { return false; }
        };
        $_SESSION['auth_user_id'] = 999;

        $controller = new AdminToolController($this->app, $this->toolRepo, $this->catRepo, $this->pythonRepo, $this->executionService);
        $req = Request::create('GET', '/admin/page/favorite-web-tools');
        $resp = $controller->handle($req);

        // Must reject unauthorized user away from admin builder (302 redirect or 403 forbidden)
        $this->assertTrue(in_array($resp->getStatusCode(), [302, 401, 403], true));
    }

    /**
     * Requirement 8: Invalid tool/handler input cannot instantiate arbitrary classes.
     */
    public function testRequirement08InvalidToolHandlerCannotInstantiateArbitraryClasses(): void
    {
        $maliciousTool = new Tool([
            'id'            => 99,
            'name'          => 'Malicious Tool',
            'slug'          => 'malicious-tool',
            'engine'        => EngineType::PHP,
            'access_mode'   => AccessMode::FREE,
            'status'        => ToolStatus::ACTIVE,
            'handler_class' => 'FavoriteCMS\\Core\\Database', // arbitrary class attempt
        ]);

        $engine = new PhpEngine();
        $this->assertTrue($engine->canHandle($maliciousTool));

        $val = $engine->validate($maliciousTool, ['input' => 'test']);
        $this->assertNotEmpty($val);
        $this->assertStringContainsString('is not registered', $val[0]);

        $res = $engine->execute($maliciousTool, ['input' => 'test']);
        $this->assertFalse($res['success']);
        $this->assertStringContainsString('is not registered', $res['error']);
    }

    /**
     * Requirement 9: Download path traversal is rejected.
     */
    public function testRequirement09DownloadPathTraversalIsRejected(): void
    {
        $traversalAttempts = [
            '../../../../etc/passwd',
            '..\\..\\..\\windows\\win.ini',
            '../../../autoload.php',
            '00000000000000000000000000000000/../../../test',
            'non-hex-token-with-special-chars!',
        ];

        foreach ($traversalAttempts as $attempt) {
            $resolved = $this->downloadManager->resolveDownload($attempt);
            $this->assertNull($resolved, "Traversal attempt '{$attempt}' was not rejected!");
        }

        // Test API download controller directly
        $controller = new DownloadApiController($this->app, $this->downloadManager);
        $req = Request::create('GET', '/api/tools/download/../../etc/passwd');
        $resp = $controller->download($req, '../../etc/passwd');
        $this->assertEquals(404, $resp->getStatusCode());
    }

    /**
     * Requirement 10: Python credentials never appear in frontend responses.
     */
    public function testRequirement10PythonCredentialsNeverAppearInFrontendResponses(): void
    {
        $controller = new ToolController($this->app, $this->registry, $this->catRepo, $this->accessControl);
        $req = Request::create('GET', '/tools/ai-content-analyzer');

        $resp = $controller->show($req, 'ai-content-analyzer');
        $html = is_string($resp) ? $resp : (string)$resp->getContent();

        $this->assertStringNotContainsString('sk-supersecret-production-key-999', $html);
        $this->assertStringNotContainsString('https://python.internal.favoriteweb.net:8443', $html);
    }

    /**
     * Requirement 11: Production error responses do not expose stack traces.
     */
    public function testRequirement11ProductionErrorResponsesDoNotExposeStackTraces(): void
    {
        $pdoException = new PDOException("SQLSTATE[42S02]: Table 'favorite.secret_db' not found at E:\\xampp\\htdocs\\query.php:123");
        $sanitized = ToolExecutionService::sanitizeErrorMessage($pdoException);

        $this->assertEquals('A database error occurred during execution.', $sanitized);
        $this->assertStringNotContainsString('SQLSTATE', $sanitized);
        $this->assertStringNotContainsString('secret_db', $sanitized);
        $this->assertStringNotContainsString('xampp', $sanitized);

        $runtimeException = new RuntimeException("Validation failed in E:\\htdocs\\src\\Handler.php on line 55");
        $sanitizedRuntime = ToolExecutionService::sanitizeErrorMessage($runtimeException);

        $this->assertStringNotContainsString('E:\\htdocs', $sanitizedRuntime);
        $this->assertStringNotContainsString('line 55', $sanitizedRuntime);
        $this->assertStringContainsString('[path]', $sanitizedRuntime);
    }

    /**
     * Requirement 12: Existing QR Code Generator still works.
     */
    public function testRequirement12ExistingQrCodeGeneratorStillWorks(): void
    {
        $controller = new ToolController($this->app, $this->registry, $this->catRepo, $this->accessControl);
        $req = Request::create('GET', '/tools/qr-code-generator');

        $resp = $controller->show($req, 'qr-code-generator');
        $html = is_string($resp) ? $resp : (string)$resp->getContent();

        $this->assertStringContainsString('qr-test-workspace', $html);
        $this->assertStringContainsString('https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js', $html);
        $this->assertStringContainsString('fwt-custom-tool-workspace', $html);
    }

    /**
     * Requirement 13: CSV batch generation still works.
     */
    public function testRequirement13CsvBatchGenerationStillWorks(): void
    {
        // Execute line sorter / duplicate remover / formatters with multi-line CSV batch inputs
        $duplicateTool = new Tool([
            'id'            => 15,
            'name'          => 'Duplicate Lines Remover',
            'slug'          => 'duplicate-lines-remover',
            'engine'        => EngineType::PHP,
            'access_mode'   => AccessMode::FREE,
            'status'        => ToolStatus::ACTIVE,
            'handler_class' => 'FavoriteCMS\\Tools\\Handlers\\DuplicateLinesRemoverHandler',
        ]);
        $this->tools[15] = $duplicateTool;

        $batchCsv = "sku,name,price\nITEM01,Widget A,10.00\nITEM01,Widget A,10.00\nITEM02,Widget B,20.00";
        $result = $this->executionService->execute(15, ['text' => $batchCsv]);

        $this->assertTrue($result['success']);
        $lines = explode("\n", trim((string)$result['data']['value']));
        $this->assertCount(3, $lines); // 1 header + 2 unique rows
    }

    /**
     * Requirement 14: ZIP download still works.
     */
    public function testRequirement14ZipDownloadStillWorks(): void
    {
        $zipPayload = "PK\x03\x04fake-zip-binary-content-test";
        $download = $this->downloadManager->createDownload($zipPayload, 'qr_codes_batch.zip', 'application/zip');

        $this->assertNotEmpty($download['reference']);
        $this->assertEquals('qr_codes_batch.zip', $download['filename']);

        // Download via controller
        $controller = new DownloadApiController($this->app, $this->downloadManager);
        $req = Request::create('GET', $download['download_url']);
        $resp = $controller->download($req, $download['reference']);

        $this->assertEquals(200, $resp->getStatusCode());
        $this->assertEquals('application/zip', $resp->getHeader('Content-Type'));
        $this->assertStringContainsString('attachment; filename="qr_codes_batch.zip"', $resp->getHeader('Content-Disposition'));
        $this->assertEquals($zipPayload, $resp->getContent());
    }

    /**
     * Requirement 15: Dark/light mode still works.
     */
    public function testRequirement15DarkLightModeStillWorks(): void
    {
        $pluginDir = dirname(__DIR__);
        $css = file_get_contents($pluginDir . '/assets/css/tools-frontend.css');

        // Check CSS variables for light and dark modes
        $this->assertStringContainsString('--fwt-primary', $css);
        $this->assertStringContainsString('--fwt-card-bg', $css);
        $this->assertStringContainsString('data-theme="dark"', $css);
        $this->assertStringContainsString('--fwt-bg', $css);
    }

    /**
     * Requirement 16: Right-sidebar-only layout remains correct.
     */
    public function testRequirement16RightSidebarOnlyLayoutRemainsCorrect(): void
    {
        $controller = new ToolController($this->app, $this->registry, $this->catRepo, $this->accessControl);
        $req = Request::create('GET', '/tools/json-formatter');

        $resp = $controller->show($req, 'json-formatter');
        $html = is_string($resp) ? $resp : (string)$resp->getContent();

        // Must have the tool layout with sidebar
        $this->assertStringContainsString('fwt-tool-layout', $html);
        $this->assertStringContainsString('fwt-tool-main-col', $html);
        $this->assertStringContainsString('fwt-tool-sidebar-col', $html);

        // Must NOT contain left sidebar classes or elements
        $this->assertStringNotContainsString('fwt-tool-sidebar-left', $html);
        $this->assertStringNotContainsString('sidebar-left', $html);

        // Sidebar must appear after the main content column in HTML structure
        $mainColPos = strpos($html, 'fwt-tool-main-col');
        $sidebarColPos = strpos($html, 'fwt-tool-sidebar-col');
        $this->assertNotFalse($mainColPos);
        $this->assertNotFalse($sidebarColPos);
        $this->assertGreaterThan($mainColPos, $sidebarColPos, 'Right sidebar must be placed after main column!');
    }

    /**
     * Requirement 17: Normal Favorite Web post/page width matching remains correct.
     */
    public function testRequirement17NormalPostPageWidthMatchingRemainsCorrect(): void
    {
        $pluginDir = dirname(__DIR__);
        $css = file_get_contents($pluginDir . '/assets/css/tools-frontend.css');

        // Must use container token or 1200px container width matching Favorite Web post/page
        $this->assertMatchesRegularExpression('/max-width:\s*(?:var\(--container,\s*1200px\)|1200px)/', $css);

        // Desktop layout must allocate 300px to right sidebar matching theme's --sidebar: 300px
        $this->assertStringContainsString('300px', $css);
    }
}
