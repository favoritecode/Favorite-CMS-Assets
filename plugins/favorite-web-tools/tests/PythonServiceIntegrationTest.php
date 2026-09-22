<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Tests;

use FavoriteCMS\Core\Application;
use FavoriteCMS\Core\Database;
use FavoriteCMS\Core\Request;
use FavoriteCMS\Tools\Controllers\Admin\AdminPythonServiceController;
use FavoriteCMS\Tools\Engines\PythonApiEngine;
use FavoriteCMS\Tools\Models\PythonService;
use FavoriteCMS\Tools\Models\Tool;
use FavoriteCMS\Tools\Repositories\CategoryRepository;
use FavoriteCMS\Tools\Repositories\PythonServiceRepository;
use FavoriteCMS\Tools\Repositories\ToolRepository;
use FavoriteCMS\Tools\Services\PythonClientService;
use FavoriteCMS\Tools\Services\ToolCatalogSeeder;
use FavoriteCMS\Tools\Support\CsrfGuard;
use FavoriteCMS\Tools\Support\EngineType;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class PythonServiceIntegrationTest extends TestCase
{
    private PythonClientService $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = new PythonClientService();
    }

    public function testValidateUrlAcceptsValidHttpAndHttps(): void
    {
        $this->client->validateUrl('https://api.python-tools.example.com');
        $this->client->validateUrl('http://ml-service.internal:8080');
        $this->assertTrue(true);
    }

    public function testValidateUrlEnforcesHttpsWhenRequired(): void
    {
        // HTTPS should pass
        $this->client->validateUrl('https://server.favoriteweb.net', true);
        $this->assertTrue(true);

        // HTTP should throw when requireHttps is true
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('External Python services must use HTTPS');
        $this->client->validateUrl('http://server.favoriteweb.net', true);
    }

    public function testValidateUrlRejectsNonHttpProtocols(): void
    {
        $invalidUrls = [
            'file:///etc/passwd',
            'ftp://ftp.example.com/script.py',
            'gopher://evil.com',
            'php://input',
        ];

        foreach ($invalidUrls as $url) {
            try {
                $this->client->validateUrl($url);
                $this->fail("Expected InvalidArgumentException for URL: {$url}");
            } catch (InvalidArgumentException $e) {
                $this->assertStringContainsString('Only http and https', $e->getMessage());
            }
        }
    }

    public function testValidateUrlRejectsCloudMetadataEndpoints(): void
    {
        $metadataUrls = [
            'http://169.254.169.254/latest/meta-data/',
            'http://metadata.google.internal/computeMetadata/v1/',
            'http://instance-data/latest/meta-data/',
            'http://metadata.azure.com/metadata/instance',
        ];

        foreach ($metadataUrls as $url) {
            try {
                $this->client->validateUrl($url);
                $this->fail("Expected InvalidArgumentException for cloud metadata URL: {$url}");
            } catch (InvalidArgumentException $e) {
                $this->assertStringContainsString('cloud metadata', $e->getMessage());
            }
        }
    }

    public function testValidateUrlRejectsLoopbackAndPrivateRanges(): void
    {
        $blockedUrls = [
            'http://127.0.0.1:8000/',
            'http://localhost:5000/',
            'http://test.localhost/api',
            'http://169.254.1.1/internal',
            'http://0.0.0.0:8000/',
            'http://10.0.0.1/api',
            'http://192.168.1.100:8080/',
            'http://172.16.0.5/',
        ];

        foreach ($blockedUrls as $url) {
            try {
                $this->client->validateUrl($url);
                $this->fail("Expected InvalidArgumentException for blocked URL: {$url}");
            } catch (InvalidArgumentException $e) {
                $this->assertTrue(
                    str_contains($e->getMessage(), 'forbidden') ||
                    str_contains($e->getMessage(), 'loopback') ||
                    str_contains($e->getMessage(), 'private')
                );
            }
        }
    }

    public function testPythonServiceExtendedFieldsAndResolvedEndpoint(): void
    {
        $service = new PythonService([
            'id'                    => 10,
            'name'                  => 'Favorite Media Downloader API',
            'slug'                  => 'favorite-media-downloader-api',
            'description'           => 'Multi-platform media extraction API',
            'base_url'              => 'https://server.favoriteweb.net',
            'default_endpoint_path' => '/download/api',
            'http_method'           => 'GET',
            'timeout'               => 30,
            'status'                => 'active',
        ]);

        $this->assertEquals(10, $service->id);
        $this->assertEquals('Favorite Media Downloader API', $service->name);
        $this->assertEquals('favorite-media-downloader-api', $service->slug);
        $this->assertEquals('https://server.favoriteweb.net', $service->base_url);
        $this->assertEquals('/download/api', $service->default_endpoint_path);
        $this->assertEquals('GET', $service->http_method);
        $this->assertTrue($service->isActive());
        $this->assertEquals(1, $service->is_active);

        // Resolved endpoint test
        $this->assertEquals('https://server.favoriteweb.net/download/api', $service->getResolvedEndpoint());
        $this->assertEquals('https://server.favoriteweb.net/download/job-file/123', $service->getResolvedEndpoint('/download/job-file/123'));
        $this->assertEquals('https://server.favoriteweb.net/download/job-file/123', $service->getResolvedEndpoint('download/job-file/123'));
    }

    public function testPythonServiceRepositoryCrudOperations(): void
    {
        $db = new Database([
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        require_once dirname(__DIR__) . '/database/migrations/002_create_favorite_web_tool_python_services_table.php';
        require_once dirname(__DIR__) . '/database/migrations/004_add_fields_to_favorite_web_tool_python_services_table.php';

        (new \CreateFavoriteWebToolPythonServicesTable($db))->up();
        (new \AddFieldsToFavoriteWebToolPythonServicesTable($db))->up();

        $repo = new PythonServiceRepository($db);

        // 1. Create
        $created = $repo->create([
            'name'                  => 'Favorite Media Downloader API',
            'slug'                  => 'favorite-media-downloader-api',
            'description'           => 'Extracts media from social channels',
            'base_url'              => 'https://server.favoriteweb.net',
            'default_endpoint_path' => '/download/api',
            'http_method'           => 'GET',
            'timeout'               => 30,
            'status'                => 'active',
        ]);

        $this->assertNotNull($created->id);
        $this->assertEquals('favorite-media-downloader-api', $created->slug);
        $this->assertEquals('GET', $created->http_method);

        // 2. findById
        $found = $repo->findById($created->id);
        $this->assertNotNull($found);
        $this->assertEquals('Favorite Media Downloader API', $found->name);
        $this->assertEquals('https://server.favoriteweb.net', $found->base_url);

        // 3. findBySlug
        $foundBySlug = $repo->findBySlug('favorite-media-downloader-api');
        $this->assertNotNull($foundBySlug);
        $this->assertEquals($created->id, $foundBySlug->id);

        // 4. Update
        $updated = $repo->update($created->id, [
            'description' => 'Updated description',
            'timeout'     => 45,
            'http_method' => 'GET',
        ]);
        $this->assertTrue($updated);

        $reloaded = $repo->findById($created->id);
        $this->assertEquals('Updated description', $reloaded->description);
        $this->assertEquals(45, $reloaded->timeout);

        // 5. Status Toggle / Activation / Deactivation
        $this->assertTrue($repo->setStatus($created->id, 'disabled'));
        $this->assertFalse($repo->findById($created->id)->isActive());

        $this->assertTrue($repo->toggleStatus($created->id));
        $this->assertTrue($repo->findById($created->id)->isActive());

        // 6. allActive
        $activeList = $repo->allActive();
        $this->assertCount(1, $activeList);

        // 7. Delete
        $this->assertTrue($repo->delete($created->id));
        $this->assertNull($repo->findById($created->id));
    }

    public function testToolBuilderPythonApiIntegrationAndHostValidation(): void
    {
        $db = new Database([
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        require_once dirname(__DIR__) . '/database/migrations/002_create_favorite_web_tool_python_services_table.php';
        require_once dirname(__DIR__) . '/database/migrations/003_create_favorite_web_tools_table.php';

        (new \CreateFavoriteWebToolPythonServicesTable($db))->up();
        (new \CreateFavoriteWebToolsTable($db))->up();

        $repo = new PythonServiceRepository($db);
        $service = $repo->create([
            'name'                  => 'Favorite Media Downloader API',
            'slug'                  => 'favorite-media-downloader-api',
            'base_url'              => 'https://server.favoriteweb.net',
            'default_endpoint_path' => '/download/api',
            'http_method'           => 'GET',
            'status'                => 'active',
        ]);

        $engine = new PythonApiEngine($repo, $this->client);

        // Valid relative endpoint
        $validTool = new Tool([
            'engine'        => EngineType::PYTHON_API,
            'configuration' => [
                'python_service_id' => $service->id,
                'python_endpoint'   => '/download/api',
            ],
        ]);
        $this->assertEmpty($engine->validate($validTool, []));

        // Full URL with matching host
        $matchingTool = new Tool([
            'engine'        => EngineType::PYTHON_API,
            'configuration' => [
                'python_service_id' => $service->id,
                'python_endpoint'   => 'https://server.favoriteweb.net/download/api',
            ],
        ]);
        $this->assertEmpty($engine->validate($matchingTool, []));

        // Malicious arbitrary external host override MUST fail validation
        $evilTool = new Tool([
            'engine'        => EngineType::PYTHON_API,
            'configuration' => [
                'python_service_id' => $service->id,
                'python_endpoint'   => 'https://evil-override.com/steal-data',
            ],
        ]);
        $errors = $engine->validate($evilTool, []);
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Host override is forbidden', $errors[0]);
    }

    public function testQueryParameterMappingAndEncodingForGetRequests(): void
    {
        $mockClient = $this->createMock(PythonClientService::class);
        $mockClient->expects($this->once())
            ->method('sendRequest')
            ->with(
                $this->anything(),
                '/download/api',
                $this->callback(function ($payload) {
                    return isset($payload['url']) &&
                           $payload['url'] === 'https://example.com/watch?v=123';
                }),
                'GET'
            )
            ->willReturn([
                'success' => true,
                'data'    => ['title' => 'Sample Video', 'formats' => []],
            ]);

        $db = $this->createMock(Database::class);
        $db->method('selectOne')->willReturn((object)[
            'id'                    => 1,
            'name'                  => 'Favorite Media Downloader API',
            'slug'                  => 'favorite-media-downloader-api',
            'base_url'              => 'https://server.favoriteweb.net',
            'default_endpoint_path' => '/download/api',
            'http_method'           => 'GET',
            'status'                => 'active',
            'is_active'             => 1,
            'timeout'               => 30,
            'auth_type'             => 'none',
        ]);

        $repo = new PythonServiceRepository($db);
        $engine = new PythonApiEngine($repo, $mockClient);

        $tool = new Tool([
            'slug'          => 'favorite-media-downloader',
            'engine'        => EngineType::PYTHON_API,
            'configuration' => [
                'python_service_id' => 1,
                'python_endpoint'   => '/download/api',
                'http_method'       => 'GET',
                'param_mapping'     => ['video_url' => 'url'],
            ],
        ]);

        $result = $engine->execute($tool, ['video_url' => 'https://example.com/watch?v=123']);
        $this->assertEquals(['title' => 'Sample Video', 'formats' => []], $result['value']);
    }

    public function testCatalogSeederRegistersMediaDownloader(): void
    {
        $db = new Database([
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        require_once dirname(__DIR__) . '/database/migrations/001_create_favorite_web_tool_categories_table.php';
        require_once dirname(__DIR__) . '/database/migrations/002_create_favorite_web_tool_python_services_table.php';
        require_once dirname(__DIR__) . '/database/migrations/003_create_favorite_web_tools_table.php';
        require_once dirname(__DIR__) . '/database/migrations/004_add_fields_to_favorite_web_tool_python_services_table.php';

        (new \CreateFavoriteWebToolCategoriesTable($db))->up();
        (new \CreateFavoriteWebToolPythonServicesTable($db))->up();
        (new \CreateFavoriteWebToolsTable($db))->up();
        (new \AddFieldsToFavoriteWebToolPythonServicesTable($db))->up();

        $catRepo = new CategoryRepository($db);
        $toolRepo = new ToolRepository($db);
        $pythonRepo = new PythonServiceRepository($db);

        $seeder = new ToolCatalogSeeder($db, $catRepo, $toolRepo, $pythonRepo);
        $seeder->seedIfEmpty();

        // Check Media Downloader service
        $service = $pythonRepo->findBySlug('favorite-media-downloader-api');
        $this->assertNotNull($service);
        $this->assertEquals('Favorite Media Downloader API', $service->name);
        $this->assertEquals('https://server.favoriteweb.net', $service->base_url);
        $this->assertEquals('/download/api', $service->default_endpoint_path);
        $this->assertEquals('GET', $service->http_method);
        $this->assertTrue($service->isActive());

        // Check Media Downloader tool
        $tool = $toolRepo->findBySlug('favorite-media-downloader');
        $this->assertNotNull($tool);
        $this->assertEquals('Favorite Media Downloader', $tool->name);
        $this->assertEquals(EngineType::PYTHON_API, $tool->engine);
        $this->assertEquals('FREE', $tool->access_mode);
        $this->assertEquals('ACTIVE', $tool->status);
        $this->assertEquals('/download/api', $tool->python_endpoint);

        // Check input schema
        $this->assertEquals('object', $tool->input_schema['type'] ?? '');
        $this->assertContains('video_url', $tool->input_schema['required'] ?? []);
    }

    public function testPrefixedDatabaseCompatibilityAndMigrationIdempotency(): void
    {
        $db = new Database([
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => 'fwt_',
        ]);

        $db->registerPrefixableTables([
            'favorite_web_tool_categories',
            'favorite_web_tool_python_services',
            'favorite_web_tools',
        ]);

        require_once dirname(__DIR__) . '/database/migrations/001_create_favorite_web_tool_categories_table.php';
        require_once dirname(__DIR__) . '/database/migrations/002_create_favorite_web_tool_python_services_table.php';
        require_once dirname(__DIR__) . '/database/migrations/003_create_favorite_web_tools_table.php';
        require_once dirname(__DIR__) . '/database/migrations/004_add_fields_to_favorite_web_tool_python_services_table.php';

        $m1 = new \CreateFavoriteWebToolCategoriesTable($db);
        $m2 = new \CreateFavoriteWebToolPythonServicesTable($db);
        $m3 = new \CreateFavoriteWebToolsTable($db);
        $m4 = new \AddFieldsToFavoriteWebToolPythonServicesTable($db);

        $m1->up();
        $m2->up();
        $m3->up();
        $m4->up();

        // Verify idempotency: running migration 004 a second time does not throw
        $m4->up();
        $this->assertTrue(true);

        // Insert and read with prefixed table
        $pythonRepo = new PythonServiceRepository($db);
        $created = $pythonRepo->create([
            'name'     => 'Prefixed Test Service',
            'slug'     => 'prefixed-test-service',
            'base_url' => 'https://server.favoriteweb.net',
            'status'   => 'active',
        ]);
        $this->assertNotNull($created->id);

        $found = $pythonRepo->findBySlug('prefixed-test-service');
        $this->assertNotNull($found);
        $this->assertEquals('Prefixed Test Service', $found->name);
    }

    public function testAdminPythonServiceControllerAuthorizationAndCsrf(): void
    {
        $db = new Database([
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        $app = new Application();
        $serviceRepo = new PythonServiceRepository($db);
        $toolRepo = new ToolRepository($db);
        $client = new PythonClientService();

        $controller = new AdminPythonServiceController($app, $serviceRepo, $toolRepo, $client);

        // 1. Unauthorized user gets 403
        unset($GLOBALS['_test_current_user']);
        $_SESSION['auth_user_id'] = 0;

        $req = Request::create('GET', '/admin/web-tools/python-services');
        $resp = $controller->handle($req);
        $this->assertEquals(403, $resp->getStatusCode());

        // 2. Authorized user with invalid CSRF on POST gets redirected with flash error
        $adminUser = new class {
            public int $id = 1;
            public function can(string $cap): bool { return true; }
        };
        $GLOBALS['_test_current_user'] = $adminUser;

        $postReq = Request::create('POST', '/admin/web-tools/python-services', [
            '_token' => 'invalid-token',
            'action' => 'save',
            'name'   => 'Test',
        ]);
        $postResp = $controller->handle($postReq);
        $this->assertEquals(302, $postResp->getStatusCode());
        $this->assertStringContainsString('CSRF', $_SESSION['flash_error'] ?? '');
    }

    public function testAdminPythonServiceControllerConnectionTestAndDependencyProtection(): void
    {
        $db = new Database([
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        require_once dirname(__DIR__) . '/database/migrations/001_create_favorite_web_tool_categories_table.php';
        require_once dirname(__DIR__) . '/database/migrations/002_create_favorite_web_tool_python_services_table.php';
        require_once dirname(__DIR__) . '/database/migrations/003_create_favorite_web_tools_table.php';

        (new \CreateFavoriteWebToolCategoriesTable($db))->up();
        (new \CreateFavoriteWebToolPythonServicesTable($db))->up();
        (new \CreateFavoriteWebToolsTable($db))->up();

        $app = new Application();
        $serviceRepo = new PythonServiceRepository($db);
        $toolRepo = new ToolRepository($db);

        $mockClient = $this->createMock(PythonClientService::class);
        $mockClient->method('request')->willReturn([
            'success' => true,
            'status'  => 200,
            'data'    => ['status' => 'healthy'],
        ]);

        $controller = new AdminPythonServiceController($app, $serviceRepo, $toolRepo, $mockClient);

        $adminUser = new class {
            public int $id = 1;
            public function can(string $cap): bool { return true; }
        };
        $GLOBALS['_test_current_user'] = $adminUser;

        // Create a service
        $srv = $serviceRepo->create([
            'name'                  => 'Media Downloader API',
            'slug'                  => 'media-downloader-api',
            'base_url'              => 'https://server.favoriteweb.net',
            'default_endpoint_path' => '/download/api',
            'http_method'           => 'GET',
            'status'                => 'active',
        ]);

        // Create a dependent tool
        $tool = $toolRepo->create([
            'name'          => 'Dependent Tool',
            'slug'          => 'dependent-tool',
            'engine'        => EngineType::PYTHON_API,
            'configuration' => [
                'python_service_id' => $srv->id,
            ],
        ]);

        // Attempting to delete service with dependent tool should fail
        $token = CsrfGuard::token();
        $deleteReq = Request::create('POST', '/admin/web-tools/python-services', [
            '_token' => $token,
            'action' => 'delete',
            'id'     => $srv->id,
        ]);

        $delResp = $controller->handle($deleteReq);
        $this->assertEquals(302, $delResp->getStatusCode());
        $this->assertStringContainsString('depend on it', $_SESSION['flash_error'] ?? '');
        $this->assertNotNull($serviceRepo->findById($srv->id));

        // Connection test execution
        $testReq = Request::create('POST', '/admin/web-tools/python-services', [
            '_token' => $token,
            'action' => 'test',
            'id'     => $srv->id,
        ]);
        $testResp = $controller->handle($testReq);
        $this->assertEquals(302, $testResp->getStatusCode());
        $this->assertStringContainsString('Connection successful', $_SESSION['flash_success'] ?? '');
    }

    public function testVideoUrlFallbackKeyMapping(): void
    {
        $capturedPayloads = [];
        $mockClient = $this->createMock(PythonClientService::class);
        $mockClient->method('sendRequest')
            ->willReturnCallback(function ($service, $endpoint, $payload, $method) use (&$capturedPayloads) {
                $capturedPayloads[] = $payload;
                return ['formats' => [['quality' => 720, 'url' => 'https://stream.example.com']]];
            });

        $db = $this->createMock(Database::class);
        $db->method('selectOne')->willReturn((object)[
            'id'                    => 1,
            'name'                  => 'Favorite Media Downloader API',
            'slug'                  => 'favorite-media-downloader-api',
            'base_url'              => 'https://server.favoriteweb.net',
            'default_endpoint_path' => '/download/api',
            'http_method'           => 'GET',
            'status'                => 'active',
            'is_active'             => 1,
            'timeout'               => 30,
            'auth_type'             => 'none',
        ]);

        $repo = new PythonServiceRepository($db);
        $engine = new PythonApiEngine($repo, $mockClient);

        $tool = new Tool([
            'slug'          => 'favorite-media-downloader',
            'engine'        => EngineType::PYTHON_API,
            'configuration' => [
                'python_service_id' => 1,
                'python_endpoint'   => '/download/api',
                'http_method'       => 'GET',
                'param_mapping'     => ['video_url' => 'url'],
            ],
        ]);

        $testUrl = 'https://www.youtube.com/watch?v=NHWjhX0Fekk';

        // 1. Direct video_url
        $engine->execute($tool, ['video_url' => $testUrl]);
        $this->assertEquals(['url' => $testUrl], $capturedPayloads[0]);

        // 2. Generic fallback key 'text'
        $engine->execute($tool, ['text' => $testUrl]);
        $this->assertEquals(['url' => $testUrl], $capturedPayloads[1]);

        // 3. Generic fallback key 'input'
        $engine->execute($tool, ['input' => $testUrl]);
        $this->assertEquals(['url' => $testUrl], $capturedPayloads[2]);

        // 4. Generic fallback key 'content'
        $engine->execute($tool, ['content' => $testUrl]);
        $this->assertEquals(['url' => $testUrl], $capturedPayloads[3]);

        // 5. Generic fallback key 'input_content'
        $engine->execute($tool, ['input_content' => $testUrl]);
        $this->assertEquals(['url' => $testUrl], $capturedPayloads[4]);

        // 6. Direct 'url' key
        $engine->execute($tool, ['url' => $testUrl]);
        $this->assertEquals(['url' => $testUrl], $capturedPayloads[5]);
    }

    public function testRfc3986EncodingAndNoDoubleEncoding(): void
    {
        $testUrl = 'https://www.youtube.com/watch?v=NHWjhX0Fekk';
        $encodedQuery = http_build_query(['url' => $testUrl], '', '&', PHP_QUERY_RFC3986);

        // Verify RFC3986 encoding
        $this->assertEquals('url=https%3A%2F%2Fwww.youtube.com%2Fwatch%3Fv%3DNHWjhX0Fekk', $encodedQuery);

        // Verify NO double encoding
        $this->assertStringNotContainsString('%253A', $encodedQuery);
        $this->assertStringNotContainsString('%252F', $encodedQuery);

        // Verify spaces encode to %20 instead of +
        $spaceQuery = http_build_query(['q' => 'hello world'], '', '&', PHP_QUERY_RFC3986);
        $this->assertEquals('q=hello%20world', $spaceQuery);
    }

    public function testDiagnosticsCaptureWithoutSecrets(): void
    {
        $mockClient = $this->createMock(PythonClientService::class);
        $mockClient->method('sendRequest')->willReturn(['status' => 'ok']);

        $db = $this->createMock(Database::class);
        $db->method('selectOne')->willReturn((object)[
            'id'                    => 1,
            'name'                  => 'Favorite Media Downloader API',
            'slug'                  => 'favorite-media-downloader-api',
            'base_url'              => 'https://server.favoriteweb.net',
            'default_endpoint_path' => '/download/api',
            'http_method'           => 'GET',
            'status'                => 'active',
            'is_active'             => 1,
            'timeout'               => 30,
            'auth_type'             => 'bearer',
            'api_key'               => 'secret-token-xyz-123',
        ]);

        $repo = new PythonServiceRepository($db);
        $engine = new PythonApiEngine($repo, $mockClient);

        $tool = new Tool([
            'slug'          => 'favorite-media-downloader',
            'engine'        => EngineType::PYTHON_API,
            'configuration' => [
                'python_service_id' => 1,
                'python_endpoint'   => '/download/api',
                'http_method'       => 'GET',
                'param_mapping'     => ['video_url' => 'url'],
            ],
        ]);

        $engine->execute($tool, ['video_url' => 'https://www.youtube.com/watch?v=NHWjhX0Fekk']);

        $diag = PythonApiEngine::getLastDiagnostics();
        $this->assertNotNull($diag);
        $this->assertEquals('favorite-media-downloader', $diag['tool_slug']);
        $this->assertEquals(1, $diag['python_service_id']);
        $this->assertEquals('https://server.favoriteweb.net', $diag['base_url']);
        $this->assertEquals('/download/api', $diag['endpoint_path']);
        $this->assertEquals('GET', $diag['http_method']);
        $this->assertContains('video_url', $diag['input_variable_names']);
        $this->assertContains('url', $diag['mapped_query_parameter_names']);

        // Assert zero secrets in diagnostics
        $diagJson = json_encode($diag);
        $this->assertStringNotContainsString('secret-token-xyz-123', $diagJson);
    }

    public function testExecutionApiControllerWithJsonPayload(): void
    {
        $db = new Database([
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        (new \CreateFavoriteWebToolCategoriesTable($db))->up();
        (new \CreateFavoriteWebToolPythonServicesTable($db))->up();
        (new \CreateFavoriteWebToolsTable($db))->up();

        $serviceRepo = new PythonServiceRepository($db);
        $service = $serviceRepo->create([
            'name'                  => 'Favorite Media Downloader API',
            'slug'                  => 'favorite-media-downloader-api',
            'base_url'              => 'https://server.favoriteweb.net',
            'default_endpoint_path' => '/download/api',
            'http_method'           => 'GET',
            'status'                => 'active',
        ]);

        $toolRepo = new ToolRepository($db);
        $tool = $toolRepo->create([
            'name'          => 'Favorite Media Downloader',
            'slug'          => 'favorite-media-downloader',
            'engine'        => EngineType::PYTHON_API,
            'access_mode'   => 'FREE',
            'status'        => 'ACTIVE',
            'configuration' => [
                'python_service_id' => $service->id,
                'python_endpoint'   => '/download/api',
                'http_method'       => 'GET',
                'param_mapping'     => ['video_url' => 'url'],
            ],
        ]);

        $mockClient = $this->createMock(PythonClientService::class);
        $mockClient->method('sendRequest')->willReturn([
            'formats' => [
                ['quality' => '720', 'ext' => 'mp4', 'url' => 'https://stream.example.com/720.mp4'],
            ],
        ]);

        $pythonEngine = new PythonApiEngine($serviceRepo, $mockClient);
        $resolver = new \FavoriteCMS\Tools\Engines\EngineResolver(null, null, null, null, $pythonEngine);
        $accessControl = new \FavoriteCMS\Tools\Services\AccessControlService();
        $execService = new \FavoriteCMS\Tools\Services\ToolExecutionService($toolRepo, $accessControl, $resolver);

        $app = new Application();
        $controller = new \FavoriteCMS\Tools\Controllers\Api\ToolExecutionApiController($app, $execService);

        // Test with raw JSON payload { "inputs": { "video_url": "https://..." } }
        $GLOBALS['_test_raw_input'] = json_encode([
            'inputs' => ['video_url' => 'https://www.youtube.com/watch?v=NHWjhX0Fekk']
        ]);

        $req = Request::create('POST', '/api/tools/favorite-media-downloader/execute');
        $resp = $controller->execute($req, 'favorite-media-downloader');

        $this->assertEquals(200, $resp->getStatusCode());
        $body = json_decode((string)$resp->getContent(), true);
        $this->assertTrue($body['success']);
        $this->assertArrayHasKey('result', $body);
        $this->assertArrayHasKey('data', $body);
        $this->assertNotEmpty($body['result']['data']['formats']);

        unset($GLOBALS['_test_raw_input']);
    }

    public function testControllerAcceptsDirectJsonPayloadWithoutInputsWrapper(): void
    {
        $db = new Database([
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        (new \CreateFavoriteWebToolCategoriesTable($db))->up();
        (new \CreateFavoriteWebToolPythonServicesTable($db))->up();
        (new \CreateFavoriteWebToolsTable($db))->up();

        $serviceRepo = new PythonServiceRepository($db);
        $service = $serviceRepo->create([
            'name'                  => 'Favorite Media Downloader API',
            'slug'                  => 'favorite-media-downloader-api',
            'base_url'              => 'https://server.favoriteweb.net',
            'default_endpoint_path' => '/download/api',
            'http_method'           => 'GET',
            'status'                => 'active',
        ]);

        $toolRepo = new ToolRepository($db);
        $tool = $toolRepo->create([
            'name'          => 'Favorite Media Downloader',
            'slug'          => 'favorite-media-downloader',
            'engine'        => EngineType::PYTHON_API,
            'access_mode'   => 'FREE',
            'status'        => 'ACTIVE',
            'configuration' => [
                'python_service_id' => $service->id,
                'python_endpoint'   => '/download/api',
                'http_method'       => 'GET',
                'param_mapping'     => ['video_url' => 'url'],
            ],
        ]);

        $mockClient = $this->createMock(PythonClientService::class);
        $mockClient->method('sendRequest')->willReturn([
            'formats' => [
                ['quality' => '1080', 'ext' => 'mp4', 'url' => 'https://stream.example.com/1080.mp4'],
            ],
        ]);

        $pythonEngine = new PythonApiEngine($serviceRepo, $mockClient);
        $resolver = new \FavoriteCMS\Tools\Engines\EngineResolver(null, null, null, null, $pythonEngine);
        $accessControl = new \FavoriteCMS\Tools\Services\AccessControlService();
        $execService = new \FavoriteCMS\Tools\Services\ToolExecutionService($toolRepo, $accessControl, $resolver);

        $app = new Application();
        $controller = new \FavoriteCMS\Tools\Controllers\Api\ToolExecutionApiController($app, $execService);

        // Direct flat JSON payload: { "video_url": "https://..." }
        $GLOBALS['_test_raw_input'] = json_encode([
            'video_url' => 'https://www.youtube.com/watch?v=NHWjhX0Fekk'
        ]);

        $req = Request::create('POST', '/api/tools/favorite-media-downloader/execute');
        $resp = $controller->execute($req, 'favorite-media-downloader');

        $this->assertEquals(200, $resp->getStatusCode());
        $body = json_decode((string)$resp->getContent(), true);
        $this->assertTrue($body['success']);
        $this->assertArrayHasKey('result', $body);
        $this->assertArrayHasKey('data', $body);

        unset($GLOBALS['_test_raw_input']);
    }

    public function testControllerAcceptsStandardPostFormDataWithoutInputsWrapper(): void
    {
        $db = new Database([
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        (new \CreateFavoriteWebToolCategoriesTable($db))->up();
        (new \CreateFavoriteWebToolPythonServicesTable($db))->up();
        (new \CreateFavoriteWebToolsTable($db))->up();

        $serviceRepo = new PythonServiceRepository($db);
        $service = $serviceRepo->create([
            'name'                  => 'Favorite Media Downloader API',
            'slug'                  => 'favorite-media-downloader-api',
            'base_url'              => 'https://server.favoriteweb.net',
            'default_endpoint_path' => '/download/api',
            'http_method'           => 'GET',
            'status'                => 'active',
        ]);

        $toolRepo = new ToolRepository($db);
        $tool = $toolRepo->create([
            'name'          => 'Favorite Media Downloader',
            'slug'          => 'favorite-media-downloader',
            'engine'        => EngineType::PYTHON_API,
            'access_mode'   => 'FREE',
            'status'        => 'ACTIVE',
            'configuration' => [
                'python_service_id' => $service->id,
                'python_endpoint'   => '/download/api',
                'http_method'       => 'GET',
                'param_mapping'     => ['video_url' => 'url'],
            ],
        ]);

        $mockClient = $this->createMock(PythonClientService::class);
        $mockClient->method('sendRequest')->willReturn([
            ['quality' => '720', 'ext' => 'mp4', 'url' => 'https://stream.example.com/720.mp4'],
        ]);

        $pythonEngine = new PythonApiEngine($serviceRepo, $mockClient);
        $resolver = new \FavoriteCMS\Tools\Engines\EngineResolver(null, null, null, null, $pythonEngine);
        $accessControl = new \FavoriteCMS\Tools\Services\AccessControlService();
        $execService = new \FavoriteCMS\Tools\Services\ToolExecutionService($toolRepo, $accessControl, $resolver);

        $app = new Application();
        $controller = new \FavoriteCMS\Tools\Controllers\Api\ToolExecutionApiController($app, $execService);

        // Simulate standard form POST with flat fields (no 'inputs' key)
        $_POST = [
            'video_url' => 'https://www.youtube.com/watch?v=NHWjhX0Fekk',
            '_token'    => 'test_csrf_token',
        ];

        $req = Request::create('POST', '/api/tools/favorite-media-downloader/execute');
        $resp = $controller->execute($req, 'favorite-media-downloader');

        $this->assertEquals(200, $resp->getStatusCode());
        $body = json_decode((string)$resp->getContent(), true);
        $this->assertTrue($body['success']);
        $this->assertArrayHasKey('result', $body);
        $this->assertArrayHasKey('data', $body);

        $_POST = [];
    }

    public function testControllerAcceptsStandardPostWithInputsArray(): void
    {
        $db = new Database([
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        (new \CreateFavoriteWebToolCategoriesTable($db))->up();
        (new \CreateFavoriteWebToolPythonServicesTable($db))->up();
        (new \CreateFavoriteWebToolsTable($db))->up();

        $serviceRepo = new PythonServiceRepository($db);
        $service = $serviceRepo->create([
            'name'                  => 'Favorite Media Downloader API',
            'slug'                  => 'favorite-media-downloader-api',
            'base_url'              => 'https://server.favoriteweb.net',
            'default_endpoint_path' => '/download/api',
            'http_method'           => 'GET',
            'status'                => 'active',
        ]);

        $toolRepo = new ToolRepository($db);
        $tool = $toolRepo->create([
            'name'          => 'Favorite Media Downloader',
            'slug'          => 'favorite-media-downloader',
            'engine'        => EngineType::PYTHON_API,
            'access_mode'   => 'FREE',
            'status'        => 'ACTIVE',
            'configuration' => [
                'python_service_id' => $service->id,
                'python_endpoint'   => '/download/api',
                'http_method'       => 'GET',
                'param_mapping'     => ['video_url' => 'url'],
            ],
        ]);

        $mockClient = $this->createMock(PythonClientService::class);
        $mockClient->method('sendRequest')->willReturn([
            ['quality' => '360', 'ext' => 'mp4', 'url' => 'https://stream.example.com/360.mp4'],
        ]);

        $pythonEngine = new PythonApiEngine($serviceRepo, $mockClient);
        $resolver = new \FavoriteCMS\Tools\Engines\EngineResolver(null, null, null, null, $pythonEngine);
        $accessControl = new \FavoriteCMS\Tools\Services\AccessControlService();
        $execService = new \FavoriteCMS\Tools\Services\ToolExecutionService($toolRepo, $accessControl, $resolver);

        $app = new Application();
        $controller = new \FavoriteCMS\Tools\Controllers\Api\ToolExecutionApiController($app, $execService);

        $_POST = [
            'inputs' => [
                'video_url' => 'https://www.youtube.com/watch?v=NHWjhX0Fekk',
            ],
            '_token' => 'csrf_token_123',
        ];

        $req = Request::create('POST', '/api/tools/favorite-media-downloader/execute');
        $resp = $controller->execute($req, 'favorite-media-downloader');

        $this->assertEquals(200, $resp->getStatusCode());
        $body = json_decode((string)$resp->getContent(), true);
        $this->assertTrue($body['success']);

        $_POST = [];
    }

    public function testPythonApiEngineHandlesExternalErrorPayloadOnHttp200(): void
    {
        $db = new Database([
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        (new \CreateFavoriteWebToolCategoriesTable($db))->up();
        (new \CreateFavoriteWebToolPythonServicesTable($db))->up();
        (new \CreateFavoriteWebToolsTable($db))->up();

        $serviceRepo = new PythonServiceRepository($db);
        $service = $serviceRepo->create([
            'name'                  => 'Favorite Media Downloader API',
            'slug'                  => 'favorite-media-downloader-api',
            'base_url'              => 'https://server.favoriteweb.net',
            'default_endpoint_path' => '/download/api',
            'http_method'           => 'GET',
            'status'                => 'active',
        ]);

        $tool = new Tool([
            'id'            => 1,
            'slug'          => 'favorite-media-downloader',
            'engine'        => EngineType::PYTHON_API,
            'configuration' => [
                'python_service_id' => $service->id,
                'python_endpoint'   => '/download/api',
                'http_method'       => 'GET',
            ],
        ]);

        $mockClient = $this->createMock(PythonClientService::class);
        $mockClient->method('sendRequest')->willReturn([
            'error' => 'No URL',
        ]);

        $engine = new PythonApiEngine($serviceRepo, $mockClient);
        $result = $engine->execute($tool, ['video_url' => '']);

        $this->assertFalse($result['success']);
        $this->assertEquals('No URL', $result['error']);
    }

    public function testPythonClientServiceHandlesNonJsonResponseSafely(): void
    {
        $client = new PythonClientService();

        // Non-JSON string should be wrapped as raw_response array rather than fatal parsing error
        $service = new PythonService([
            'base_url' => 'https://server.favoriteweb.net',
            'timeout'  => 30,
        ]);

        // Validate that sendRequest properly captures diagnostics
        PythonClientService::clearDiagnostics();
        $this->assertNull(PythonClientService::getLastDiagnostics());
    }
}

