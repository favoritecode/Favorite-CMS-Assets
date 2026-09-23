<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Tests;

use FavoriteCMS\Core\Application;
use FavoriteCMS\Core\Database;
use FavoriteCMS\Core\Request;
use FavoriteCMS\Tools\Controllers\Admin\AdminFrontendDesignController;
use FavoriteCMS\Tools\Repositories\CategoryRepository;
use FavoriteCMS\Tools\Repositories\FrontendDesignRepository;
use FavoriteCMS\Tools\Repositories\PythonServiceRepository;
use FavoriteCMS\Tools\Repositories\ToolRepository;
use FavoriteCMS\Tools\Services\DesignPackageImporter;
use FavoriteCMS\Tools\Support\CsrfGuard;
use PHPUnit\Framework\TestCase;
use ZipArchive;

class FrontendDesignZipImportTest extends TestCase
{
    private Database $db;
    private FrontendDesignRepository $designRepo;
    private ToolRepository $toolRepo;
    private CategoryRepository $catRepo;
    private PythonServiceRepository $pythonRepo;
    private DesignPackageImporter $importer;
    private Application $app;
    private AdminFrontendDesignController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        if (!defined('PHPUNIT_RUNNING')) {
            define('PHPUNIT_RUNNING', true);
        }

        $this->db = new Database([
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        // Run migrations 001 to 005
        require_once dirname(__DIR__) . '/database/migrations/001_create_favorite_web_tool_categories_table.php';
        require_once dirname(__DIR__) . '/database/migrations/002_create_favorite_web_tool_python_services_table.php';
        require_once dirname(__DIR__) . '/database/migrations/003_create_favorite_web_tools_table.php';
        require_once dirname(__DIR__) . '/database/migrations/004_add_fields_to_favorite_web_tool_python_services_table.php';
        require_once dirname(__DIR__) . '/database/migrations/005_create_favorite_web_tool_frontend_designs_table.php';

        (new \CreateFavoriteWebToolCategoriesTable($this->db))->up();
        (new \CreateFavoriteWebToolPythonServicesTable($this->db))->up();
        (new \CreateFavoriteWebToolsTable($this->db))->up();
        (new \AddFieldsToFavoriteWebToolPythonServicesTable($this->db))->up();
        (new \CreateFavoriteWebToolFrontendDesignsTable($this->db))->up();

        $this->catRepo = new CategoryRepository($this->db);
        $this->toolRepo = new ToolRepository($this->db);
        $this->pythonRepo = new PythonServiceRepository($this->db);
        $this->designRepo = new FrontendDesignRepository($this->db);

        $this->designRepo->seedBuiltins();
        $this->importer = new DesignPackageImporter($this->designRepo);
        $this->app = new Application();
        $this->app->instance(FrontendDesignRepository::class, $this->designRepo);
        $this->app->instance(Database::class, $this->db);
        $this->controller = new AdminFrontendDesignController(
            $this->app,
            $this->designRepo,
            $this->toolRepo,
            $this->importer
        );
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['_test_current_user'], $GLOBALS['_test_raw_input']);
        unset($_SESSION['auth_user_id'], $_SESSION['user_id'], $_SESSION['_token'], $_SESSION['flash_error'], $_SESSION['flash_success']);
        unset($_FILES['package']);
        parent::tearDown();
    }

    private function setAdminUser(): void
    {
        $admin = new class {
            public int $id = 1;
            public function can(string $cap): bool { return $cap === 'manage_options'; }
            public function hasPermission(string $cap): bool { return $cap === 'manage_options'; }
            public function isSuperAdmin(): bool { return true; }
            public function hasRole(string $role): bool { return $role === 'admin'; }
        };
        $GLOBALS['_test_current_user'] = $admin;
        $_SESSION['auth_user_id'] = 1;
    }

    private function setNonAdminUser(): void
    {
        $subscriber = new class {
            public int $id = 42;
            public function can(string $cap): bool { return false; }
            public function hasPermission(string $cap): bool { return false; }
            public function isSuperAdmin(): bool { return false; }
            public function hasRole(string $role): bool { return false; }
        };
        $GLOBALS['_test_current_user'] = $subscriber;
        $_SESSION['auth_user_id'] = 42;
    }

    private function createValidZipPackage(string $name, string $slug, string $version = '1.0.0'): string
    {
        $zipPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_pkg_' . bin2hex(random_bytes(4)) . '.zip';
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $manifest = [
            'name'                  => $name,
            'slug'                  => $slug,
            'version'               => $version,
            'category'              => 'Media',
            'description'           => 'Test design package archive.',
            'supported_normalizers' => ['media', 'bulk_media'],
        ];

        $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));
        $zip->addFromString('template.html', '<div class="test-design-root">{{title}}</div>');
        $zip->addFromString('styles.css', '.test-design-root { color: blue; }');
        $zip->close();

        return $zipPath;
    }

    /**
     * Requirement 6A: GET import page -> 200 for authorized admin
     */
    public function testGetImportPageReturns200ForAuthorizedAdmin(): void
    {
        $this->setAdminUser();
        $token = CsrfGuard::token();

        $req = new Request(['action' => 'import'], [], [], [], [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI'    => '/admin/page/favorite-web-tools-frontend-designs?action=import',
        ]);
        $html = (string)$this->controller->handle($req);

        $this->assertStringContainsString('Import Design Package (.ZIP)', $html);
        $this->assertStringContainsString('name="_token"', $html);
        $this->assertStringContainsString('name="csrf_token"', $html);
        $this->assertStringContainsString($token, $html);
        $this->assertStringContainsString('enctype="multipart/form-data"', $html);
    }

    /**
     * Requirement 6B & 6H: POST import with valid current CSRF token -> design imported successfully
     */
    public function testPostImportWithValidCsrfTokenImportsDesign(): void
    {
        $this->setAdminUser();
        $token = CsrfGuard::token();

        $zipPath = $this->createValidZipPackage('Neon Cyber Downloader', 'neon-cyber-downloader', '1.0.0');

        try {
            $_FILES['package'] = [
                'name'     => 'neon-cyber.zip',
                'type'     => 'application/zip',
                'tmp_name' => $zipPath,
                'error'    => UPLOAD_ERR_OK,
                'size'     => filesize($zipPath),
            ];

            $req = Request::create('POST', '/admin/page/favorite-web-tools-frontend-designs', [
                'action'     => 'import',
                '_token'     => $token,
                'csrf_token' => $token,
            ]);

            $resp = $this->controller->handle($req);

            $this->assertEquals(302, $resp->getStatusCode());
            $this->assertEquals('/admin/page/favorite-web-tools-frontend-designs', $resp->getHeader('Location'));
            $this->assertStringContainsString('imported successfully', $_SESSION['flash_success'] ?? '');

            // Verify design in repository
            $design = $this->designRepo->findBySlug('neon-cyber-downloader');
            $this->assertNotNull($design);
            $this->assertEquals('Neon Cyber Downloader', $design->getName());
            $this->assertEquals('1.0.0', $design->getVersion());
        } finally {
            @unlink($zipPath);
        }
    }

    /**
     * Requirement 6C: POST import with missing token -> 403
     */
    public function testPostImportWithMissingTokenReturns403(): void
    {
        $this->setAdminUser();
        CsrfGuard::token(); // ensure session token exists

        $req = Request::create('POST', '/admin/page/favorite-web-tools-frontend-designs', [
            'action' => 'import',
        ]);

        $resp = $this->controller->handle($req);

        $this->assertEquals(403, $resp->getStatusCode());
        $this->assertStringContainsString('Invalid security token', (string)$resp->getContent());
    }

    /**
     * Requirement 6D: POST import with invalid token -> 403
     */
    public function testPostImportWithInvalidTokenReturns403(): void
    {
        $this->setAdminUser();
        CsrfGuard::token();

        $req = Request::create('POST', '/admin/page/favorite-web-tools-frontend-designs', [
            'action'     => 'import',
            '_token'     => 'completely-invalid-csrf-token-12345',
            'csrf_token' => 'completely-invalid-csrf-token-12345',
        ]);

        $resp = $this->controller->handle($req);

        $this->assertEquals(403, $resp->getStatusCode());
        $this->assertStringContainsString('Invalid security token', (string)$resp->getContent());
    }

    /**
     * Requirement 6E: POST import with stale/incorrect token -> 403
     */
    public function testPostImportWithStaleTokenReturns403(): void
    {
        $this->setAdminUser();
        $oldToken = bin2hex(random_bytes(32));
        $_SESSION['_token'] = bin2hex(random_bytes(32)); // current active session token

        $req = Request::create('POST', '/admin/page/favorite-web-tools-frontend-designs', [
            'action' => 'import',
            '_token' => $oldToken,
        ]);

        $resp = $this->controller->handle($req);

        $this->assertEquals(403, $resp->getStatusCode());
        $this->assertStringContainsString('Invalid security token', (string)$resp->getContent());
    }

    /**
     * Requirement 6F: Unauthorized user without manage_options -> 403
     */
    public function testUnauthorizedUserReturns403(): void
    {
        $this->setNonAdminUser();

        $req = new Request(['action' => 'import'], [], [], [], [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI'    => '/admin/page/favorite-web-tools-frontend-designs?action=import',
        ]);
        $resp = $this->controller->handle($req);

        $this->assertEquals(403, $resp->getStatusCode());
        $this->assertStringContainsString('403 Access Denied', $resp->getContent());
    }

    /**
     * Requirement 6G: Unauthenticated guest -> 403
     */
    public function testGuestReturns403(): void
    {
        unset($GLOBALS['_test_current_user']);
        unset($_SESSION['auth_user_id'], $_SESSION['user_id']);

        $req = new Request(['action' => 'import'], [], [], [], [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI'    => '/admin/page/favorite-web-tools-frontend-designs?action=import',
        ]);
        $resp = $this->controller->handle($req);

        $this->assertEquals(403, $resp->getStatusCode());
        $this->assertStringContainsString('403 Access Denied', $resp->getContent());
    }

    /**
     * Requirement 6I: overwrite=true + valid CSRF -> existing design updated
     */
    public function testPostImportWithOverwriteUpdatesExistingDesign(): void
    {
        $this->setAdminUser();
        $token = CsrfGuard::token();

        // 1. Initial import v1.0.0
        $zip1 = $this->createValidZipPackage('Overwrite Test Design', 'overwrite-test-design', '1.0.0');
        $_FILES['package'] = [
            'name'     => 'overwrite-v1.zip',
            'type'     => 'application/zip',
            'tmp_name' => $zip1,
            'error'    => UPLOAD_ERR_OK,
            'size'     => filesize($zip1),
        ];

        $req1 = Request::create('POST', '/admin/page/favorite-web-tools-frontend-designs', [
            'action' => 'import',
            '_token' => $token,
        ]);
        $this->controller->handle($req1);
        @unlink($zip1);

        $design1 = $this->designRepo->findBySlug('overwrite-test-design');
        $this->assertNotNull($design1);
        $this->assertEquals('1.0.0', $design1->getVersion());

        // 2. Second import v2.0.0 without overwrite -> should fail
        $zip2 = $this->createValidZipPackage('Overwrite Test Design', 'overwrite-test-design', '2.0.0');
        $_FILES['package'] = [
            'name'     => 'overwrite-v2.zip',
            'type'     => 'application/zip',
            'tmp_name' => $zip2,
            'error'    => UPLOAD_ERR_OK,
            'size'     => filesize($zip2),
        ];

        $req2 = Request::create('POST', '/admin/page/favorite-web-tools-frontend-designs', [
            'action'    => 'import',
            '_token'    => $token,
            'overwrite' => '0',
        ]);
        $resp2 = $this->controller->handle($req2);
        $this->assertEquals(302, $resp2->getStatusCode());
        $this->assertStringContainsString('already exists', $_SESSION['flash_error'] ?? '');

        // 3. Third import v2.0.0 with overwrite = 1 -> should succeed
        $req3 = Request::create('POST', '/admin/page/favorite-web-tools-frontend-designs', [
            'action'    => 'import',
            '_token'    => $token,
            'overwrite' => '1',
        ]);
        $resp3 = $this->controller->handle($req3);
        $this->assertEquals(302, $resp3->getStatusCode());
        $this->assertStringContainsString('imported successfully', $_SESSION['flash_success'] ?? '');
        @unlink($zip2);

        $design2 = $this->designRepo->findBySlug('overwrite-test-design');
        $this->assertNotNull($design2);
        $this->assertEquals('2.0.0', $design2->getVersion());
    }

    /**
     * Test Zip-Slip attack prevention
     */
    public function testZipSlipPathTraversalIsRejected(): void
    {
        $this->setAdminUser();
        $token = CsrfGuard::token();

        $zipPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_zipslip_' . bin2hex(random_bytes(4)) . '.zip';
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('manifest.json', json_encode(['name' => 'Bad Zip']));
        $zip->addFromString('../../../evil.txt', 'evil payload');
        $zip->close();

        try {
            $_FILES['package'] = [
                'name'     => 'zipslip.zip',
                'type'     => 'application/zip',
                'tmp_name' => $zipPath,
                'error'    => UPLOAD_ERR_OK,
                'size'     => filesize($zipPath),
            ];

            $req = Request::create('POST', '/admin/page/favorite-web-tools-frontend-designs', [
                'action' => 'import',
                '_token' => $token,
            ]);

            $resp = $this->controller->handle($req);
            $this->assertEquals(302, $resp->getStatusCode());
            $this->assertStringContainsString('Suspicious path detected', $_SESSION['flash_error'] ?? '');
        } finally {
            @unlink($zipPath);
        }
    }

    /**
     * Test executable file rejection (.php)
     */
    public function testExecutableFileIsRejected(): void
    {
        $this->setAdminUser();
        $token = CsrfGuard::token();

        $zipPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_php_' . bin2hex(random_bytes(4)) . '.zip';
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('manifest.json', json_encode(['name' => 'Bad Php']));
        $zip->addFromString('backdoor.php', '<?php phpinfo(); ?>');
        $zip->close();

        try {
            $_FILES['package'] = [
                'name'     => 'phpbackdoor.zip',
                'type'     => 'application/zip',
                'tmp_name' => $zipPath,
                'error'    => UPLOAD_ERR_OK,
                'size'     => filesize($zipPath),
            ];

            $req = Request::create('POST', '/admin/page/favorite-web-tools-frontend-designs', [
                'action' => 'import',
                '_token' => $token,
            ]);

            $resp = $this->controller->handle($req);
            $this->assertEquals(302, $resp->getStatusCode());
            $this->assertStringContainsString("Prohibited file type '.php'", $_SESSION['flash_error'] ?? '');
        } finally {
            @unlink($zipPath);
        }
    }

    /**
     * Test actual release package media-downloader-grid.zip imports cleanly
     */
    public function testActualReleasePackageImportsCleanly(): void
    {
        $this->setAdminUser();
        $token = CsrfGuard::token();

        $releasePkg = dirname(__DIR__) . '/release/designs/media-downloader-grid.zip';
        $this->assertFileExists($releasePkg);

        $_FILES['package'] = [
            'name'     => 'media-downloader-grid.zip',
            'type'     => 'application/zip',
            'tmp_name' => $releasePkg,
            'error'    => UPLOAD_ERR_OK,
            'size'     => filesize($releasePkg),
        ];

        $req = Request::create('POST', '/admin/page/favorite-web-tools-frontend-designs', [
            'action'    => 'import',
            '_token'    => $token,
            'overwrite' => '1',
        ]);

        $resp = $this->controller->handle($req);
        $this->assertEquals(302, $resp->getStatusCode());
        $this->assertStringContainsString('imported successfully', $_SESSION['flash_success'] ?? '');

        $grid = $this->designRepo->findBySlug('media-downloader-grid');
        $this->assertNotNull($grid);
        $this->assertEquals('Media Downloader Grid', $grid->getName());
        $this->assertStringContainsString('fwt-grid-downloader', $grid->getTemplateHtml());
        $this->assertStringContainsString('fwt-grid-downloader', $grid->getCssContent());
    }

    /**
     * Requirement 7 & 9: Import media-downloader-grid-v2.zip, activate it on Media Downloader tool,
     * and verify design-owned inputs, batch ribbon, card template, and action bridge attributes.
     */
    public function testMediaDownloaderGridV2ImportsCleanlyAndActivates(): void
    {
        $this->setAdminUser();
        $token = CsrfGuard::token();

        $v2Pkg = dirname(__DIR__) . '/release/designs/media-downloader-grid-v2.zip';
        $this->assertFileExists($v2Pkg, 'release/designs/media-downloader-grid-v2.zip must exist');

        $_FILES['package'] = [
            'name'     => 'media-downloader-grid-v2.zip',
            'type'     => 'application/zip',
            'tmp_name' => $v2Pkg,
            'error'    => UPLOAD_ERR_OK,
            'size'     => filesize($v2Pkg),
        ];

        $req = Request::create('POST', '/admin/page/favorite-web-tools-frontend-designs', [
            'action'    => 'import',
            '_token'    => $token,
            'overwrite' => '1',
        ]);

        $resp = $this->controller->handle($req);
        $this->assertEquals(302, $resp->getStatusCode());
        $this->assertStringContainsString('imported successfully', $_SESSION['flash_success'] ?? '');

        // Verify design imported as v2.0.0
        $imported = $this->designRepo->findBySlug('media-downloader-grid');
        $this->assertNotNull($imported);
        $this->assertEquals('2.0.0', $imported->getVersion());

        // Create tool using media-downloader-grid design
        $tool = $this->toolRepo->create([
            'name'                 => 'Favorite Media Downloader',
            'slug'                 => 'favorite-media-downloader',
            'engine'               => 'PHP',
            'frontend_design_slug' => 'media-downloader-grid',
            'access_mode'          => 'FREE',
            'status'               => 'ACTIVE',
        ]);

        // Render tool page
        $registry = new \FavoriteCMS\Tools\Services\ToolRegistryService($this->toolRepo);
        $accessControl = new \FavoriteCMS\Tools\Services\AccessControlService();
        $toolController = new \FavoriteCMS\Tools\Controllers\Frontend\ToolController(
            $this->app,
            $registry,
            $this->catRepo,
            $accessControl
        );

        $toolReq = new Request([], [], [], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/tools/favorite-media-downloader']);
        $rendered = (string)$toolController->show($toolReq, 'favorite-media-downloader');

        // Verify design-owned elements and generic action bridge hooks
        $this->assertStringContainsString('data-fwt-container="media-downloader"', $rendered);
        $this->assertStringContainsString('data-fwt-input="urls"', $rendered);
        $this->assertStringContainsString('data-fwt-action="get-formats"', $rendered);
        $this->assertStringContainsString('data-fwt-action="clear-all"', $rendered);
        $this->assertStringContainsString('data-fwt-section="batch-bar"', $rendered);
        $this->assertStringContainsString('data-fwt-action="set-global-quality"', $rendered);
        $this->assertStringContainsString('data-fwt-action="fast-download-all"', $rendered);
        $this->assertStringContainsString('data-fwt-action="convert-download-all"', $rendered);
        $this->assertStringContainsString('data-fwt-action="cancel-all"', $rendered);
        $this->assertStringContainsString('<template data-fwt-template="card">', $rendered);
        $this->assertStringContainsString('data-fwt-action="fast-download"', $rendered);
        $this->assertStringContainsString('data-fwt-action="convert-download"', $rendered);
        $this->assertStringContainsString('data-fwt-action="cancel"', $rendered);
        $this->assertStringContainsString('data-fwt-action="retry"', $rendered);
        $this->assertStringContainsString('data-fwt-action="download-file"', $rendered);

        // Verify zero hardcoded API logic in the design
        $this->assertStringNotContainsString('/download/api', $rendered);
        $this->assertStringNotContainsString('/download/start-download', $rendered);
    }

    /**
     * Verify that the rendered HTML of views/admin/frontend-designs/import.php
     * contains the exact _token input required by Favorite CMS Core's Kernel::dispatchAdmin().
     */
    public function testCoreAdminCsrfContractSimulation(): void
    {
        $this->setAdminUser();
        $storedToken = CsrfGuard::token();

        // 1. Render import form
        $req = new Request(['action' => 'import'], [], [], [], [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI'    => '/admin/page/favorite-web-tools-frontend-designs?action=import',
        ]);
        $html = (string)$this->controller->handle($req);

        // 2. Extract _token field value from HTML
        $matched = preg_match('/<input[^>]+name="_token"[^>]+value="([^"]+)"/i', $html, $m);
        $this->assertSame(1, $matched, 'Import form must render an input with name="_token" for Core compatibility.');
        $extractedToken = $m[1];

        // 3. Simulate Core Kernel::dispatchAdmin() check
        $coreRequest = Request::create('POST', '/admin/page/favorite-web-tools-frontend-designs', [
            '_token' => $extractedToken,
            'action' => 'import',
        ]);

        $submitted = $coreRequest->post('_token', '');
        $isCoreValid = is_string($submitted) && is_string($storedToken) && $storedToken !== '' && hash_equals($storedToken, $submitted);
        $this->assertTrue($isCoreValid, 'Core Kernel CSRF validation must succeed when form submitted with rendered _token.');

        // 4. Simulate Core Kernel::dispatchAdmin() with missing _token (legacy failure case)
        $legacyBadReq = Request::create('POST', '/admin/page/favorite-web-tools-frontend-designs', [
            'csrf_token' => $storedToken, // Only csrf_token, missing _token
            'action'     => 'import',
        ]);
        $legacySubmitted = $legacyBadReq->post('_token', '');
        $isLegacyValid = is_string($legacySubmitted) && is_string($storedToken) && $storedToken !== '' && hash_equals($storedToken, $legacySubmitted);
        $this->assertFalse($isLegacyValid, 'Omitting _token fails Core Kernel check, proving reproduction of the original bug.');
    }

    /**
     * Requirement: Verify that a ZIP containing global :root is strictly rejected by the importer.
     */
    public function testImportFailsWhenZipContainsRootGlobalSelector(): void
    {
        $this->setAdminUser();
        $token = CsrfGuard::token();

        // Create a test package containing :root selector
        $badZipPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_root_pkg_' . bin2hex(random_bytes(4)) . '.zip';
        $zip = new ZipArchive();
        $zip->open($badZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $manifest = [
            'name'                  => 'Bad Root Design',
            'slug'                  => 'bad-root-design',
            'version'               => '1.0.0',
            'category'              => 'Media',
            'description'           => 'Design with forbidden :root selector.',
            'supported_normalizers' => ['media'],
        ];
        $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));
        $zip->addFromString('template.html', '<div class="card">{{title}}</div>');
        $zip->addFromString('styles.css', ":root {\n  --bg: #fff;\n  --text: #111;\n}\n.card {\n  background: var(--bg);\n}");
        $zip->close();

        // 1. Controller import POST flow
        $_FILES['package'] = [
            'name'     => 'bad-root-design.zip',
            'type'     => 'application/zip',
            'tmp_name' => $badZipPath,
            'error'    => UPLOAD_ERR_OK,
            'size'     => filesize($badZipPath),
        ];

        $req = Request::create('POST', '/admin/page/favorite-web-tools-frontend-designs', [
            'action'    => 'import',
            '_token'    => $token,
            'overwrite' => '1',
        ]);

        $resp = $this->controller->handle($req);
        $this->assertEquals(302, $resp->getStatusCode());
        $this->assertStringContainsString("Global selector ':root' is prohibited", $_SESSION['flash_error'] ?? '');

        // 2. Direct importer exception check
        $caught = false;
        try {
            $this->importer->importFromZip($badZipPath, true);
        } catch (\InvalidArgumentException $e) {
            $caught = true;
            $this->assertStringContainsString("Global selector ':root' is prohibited in frontend designs.", $e->getMessage());
        }
        $this->assertTrue($caught, 'Importer must throw InvalidArgumentException when :root is encountered.');

        if (file_exists($badZipPath)) {
            unlink($badZipPath);
        }
    }

    /**
     * Requirement: Verify that media-downloader-grid-v3.zip imports cleanly with zero :root errors,
     * contains .fwt-media-downloader-design scoped variables and rules, activates, and renders properly.
     */
    public function testMediaDownloaderGridV3ImportsCleanlyWithoutRootAndActivates(): void
    {
        $this->setAdminUser();
        $token = CsrfGuard::token();

        $v3Pkg = dirname(__DIR__) . '/release/designs/media-downloader-grid-v3.zip';
        $v3Sha = $v3Pkg . '.sha256';

        $this->assertFileExists($v3Pkg, 'release/designs/media-downloader-grid-v3.zip must exist');
        $this->assertFileExists($v3Sha, 'release/designs/media-downloader-grid-v3.zip.sha256 must exist');

        // Verify SHA-256 matches
        $calculatedHash = hash_file('sha256', $v3Pkg);
        $recordedHash = trim(explode(' ', file_get_contents($v3Sha))[0]);
        $this->assertEquals($calculatedHash, $recordedHash, 'SHA-256 file must match actual zip hash');

        $_FILES['package'] = [
            'name'     => 'media-downloader-grid-v3.zip',
            'type'     => 'application/zip',
            'tmp_name' => $v3Pkg,
            'error'    => UPLOAD_ERR_OK,
            'size'     => filesize($v3Pkg),
        ];

        $req = Request::create('POST', '/admin/page/favorite-web-tools-frontend-designs', [
            'action'    => 'import',
            '_token'    => $token,
            'overwrite' => '1',
        ]);

        $resp = $this->controller->handle($req);
        $this->assertEquals(302, $resp->getStatusCode());
        $this->assertStringContainsString('imported successfully', $_SESSION['flash_success'] ?? '');
        $this->assertArrayNotHasKey('flash_error', $_SESSION);

        // Verify design imported as v3.0.0
        $imported = $this->designRepo->findBySlug('media-downloader-grid-v3');
        $this->assertNotNull($imported);
        $this->assertEquals('3.0.0', $imported->getVersion());
        $this->assertEquals('Media Downloader Grid v3', $imported->getName());

        // Verify CSS scoping: NO :root, html, or body; contains wrapper and scoped custom properties
        $css = $imported->getCssContent();
        $this->assertStringNotContainsString(':root', $css, 'Scoped CSS must not contain :root');
        $this->assertDoesNotMatchRegularExpression('/(^|[,\s\}])(html|body)[\s\.\:#\{]/i', $css, 'Scoped CSS must not contain global html or body selector');
        $this->assertStringContainsString('.fwt-media-downloader-design', $css, 'Scoped CSS must target .fwt-media-downloader-design');
        $this->assertStringContainsString('--fwt-primary', $css, 'Scoped CSS must contain custom properties on wrapper');
        $this->assertStringContainsString('.fwt-design-container[data-design="media-downloader-grid-v3"]', $css, 'CSS must be scoped under design container');

        // Verify dark mode does not use html.dark or body.dark
        $this->assertStringNotContainsString('html.dark', $css);
        $this->assertStringNotContainsString('body.dark', $css);

        // Create tool using media-downloader-grid-v3 design
        $tool = $this->toolRepo->create([
            'name'                 => 'Favorite Media Downloader v3',
            'slug'                 => 'favorite-media-downloader-v3',
            'engine'               => 'PHP',
            'frontend_design_slug' => 'media-downloader-grid-v3',
            'access_mode'          => 'FREE',
            'status'               => 'ACTIVE',
        ]);

        // Render tool page
        $registry = new \FavoriteCMS\Tools\Services\ToolRegistryService($this->toolRepo);
        $accessControl = new \FavoriteCMS\Tools\Services\AccessControlService();
        $toolController = new \FavoriteCMS\Tools\Controllers\Frontend\ToolController(
            $this->app,
            $registry,
            $this->catRepo,
            $accessControl
        );

        $toolReq = new Request([], [], [], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/tools/favorite-media-downloader-v3']);
        $rendered = (string)$toolController->show($toolReq, 'favorite-media-downloader-v3');

        // Verify design-owned elements, wrapper, and generic action bridge hooks
        $this->assertStringContainsString('class="fwt-media-downloader-design', $rendered);
        $this->assertStringContainsString('data-fwt-container="media-downloader"', $rendered);
        $this->assertStringContainsString('data-fwt-input="urls"', $rendered);
        $this->assertStringContainsString('data-fwt-action="get-formats"', $rendered);
        $this->assertStringContainsString('data-fwt-action="clear-all"', $rendered);
        $this->assertStringContainsString('data-fwt-section="batch-bar"', $rendered);
        $this->assertStringContainsString('data-fwt-action="set-global-quality"', $rendered);
        $this->assertStringContainsString('data-fwt-action="fast-download-all"', $rendered);
        $this->assertStringContainsString('data-fwt-action="convert-download-all"', $rendered);
        $this->assertStringContainsString('data-fwt-action="cancel-all"', $rendered);
        $this->assertStringContainsString('<template data-fwt-template="card">', $rendered);
        $this->assertStringContainsString('data-fwt-action="fast-download"', $rendered);
        $this->assertStringContainsString('data-fwt-action="convert-download"', $rendered);
        $this->assertStringContainsString('data-fwt-action="cancel"', $rendered);
        $this->assertStringContainsString('data-fwt-action="retry"', $rendered);
        $this->assertStringContainsString('data-fwt-action="download-file"', $rendered);

        // Verify zero hardcoded API logic in the design
        $this->assertStringNotContainsString('/download/api', $rendered);
        $this->assertStringNotContainsString('/download/start-download', $rendered);
    }
}
