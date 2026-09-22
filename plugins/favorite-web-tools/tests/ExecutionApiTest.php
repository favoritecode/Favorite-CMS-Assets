<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Tests;

use FavoriteCMS\Tools\Contracts\MembershipCheckerInterface;
use FavoriteCMS\Tools\Engines\EngineResolver;
use FavoriteCMS\Tools\Models\Tool;
use FavoriteCMS\Tools\Repositories\ToolRepository;
use FavoriteCMS\Tools\Services\AccessControlService;
use FavoriteCMS\Tools\Services\DownloadManagerService;
use FavoriteCMS\Tools\Services\ToolExecutionService;
use FavoriteCMS\Tools\Support\AccessMode;
use FavoriteCMS\Tools\Support\ToolStatus;
use PHPUnit\Framework\TestCase;

class ExecutionApiTest extends TestCase
{
    private ToolExecutionService $executionService;
    private DownloadManagerService $downloadManager;
    private AccessControlService $accessControl;
    private EngineResolver $engineResolver;

    /**
     * @var array<string, Tool>
     */
    private array $tools = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->downloadManager = new DownloadManagerService();
        $this->engineResolver = new EngineResolver();

        // Build mock membership checker
        $membershipChecker = new class implements MembershipCheckerInterface {
            public function hasActiveMembership(?int $userId): bool
            {
                return $userId === 999;
            }
        };

        $this->accessControl = new AccessControlService($membershipChecker);

        // Pre-create in-memory tools
        $this->tools['free-html-formatter'] = new Tool([
            'id'            => 1,
            'name'          => 'HTML Formatter',
            'slug'          => 'html-formatter',
            'engine'        => 'HTML',
            'access_mode'   => AccessMode::FREE,
            'status'        => ToolStatus::ACTIVE,
            'configuration' => ['operation' => 'format'],
        ]);

        $this->tools['login-base64'] = new Tool([
            'id'            => 2,
            'name'          => 'Base64 Encoder',
            'slug'          => 'base64-encoder',
            'engine'        => 'PHP',
            'access_mode'   => AccessMode::LOGIN_REQUIRED,
            'status'        => ToolStatus::ACTIVE,
            'configuration' => ['php_handler' => 'base64_encoder'],
        ]);

        $this->tools['member-css-minifier'] = new Tool([
            'id'            => 3,
            'name'          => 'CSS Minifier',
            'slug'          => 'css-minifier',
            'engine'        => 'CSS',
            'access_mode'   => AccessMode::MEMBERSHIP_REQUIRED,
            'status'        => ToolStatus::ACTIVE,
            'configuration' => ['operation' => 'minify'],
        ]);

        $this->tools['disabled-tool'] = new Tool([
            'id'            => 4,
            'name'          => 'Disabled Tool',
            'slug'          => 'disabled-tool',
            'engine'        => 'HTML',
            'access_mode'   => AccessMode::FREE,
            'status'        => ToolStatus::DISABLED,
            'configuration' => ['operation' => 'format'],
        ]);

        // Mock ToolRepository
        $toolRepo = $this->createMock(ToolRepository::class);
        $toolRepo->method('findById')->willReturnCallback(function (int $id): ?Tool {
            foreach ($this->tools as $tool) {
                if ($tool->id === $id) {
                    return $tool;
                }
            }
            return null;
        });
        $toolRepo->method('findBySlug')->willReturnCallback(function (string $slug): ?Tool {
            foreach ($this->tools as $tool) {
                if ($tool->slug === $slug) {
                    return $tool;
                }
            }
            return null;
        });

        $this->executionService = new ToolExecutionService(
            $toolRepo,
            $this->accessControl,
            $this->engineResolver,
            $this->downloadManager
        );
    }

    public function testToolNotFound(): void
    {
        $res = $this->executionService->execute('non-existent-tool', ['input' => 'test']);
        $this->assertFalse($res['success']);
        $this->assertEquals('TOOL_NOT_FOUND', $res['error']['code']);
    }

    public function testDisabledToolRejection(): void
    {
        $res = $this->executionService->execute('disabled-tool', ['input' => '<p>test</p>']);
        $this->assertFalse($res['success']);
        $this->assertEquals('TOOL_DISABLED', $res['error']['code']);
    }

    public function testFreeToolExecutesForGuest(): void
    {
        $res = $this->executionService->execute('html-formatter', ['html' => '<p>Hello CMS</p>']);
        $this->assertTrue($res['success']);
        $this->assertEquals('HTML Formatter', $res['tool']['name']);
        $this->assertEquals('HTML', $res['data']['type']);
        $this->assertStringContainsString('<p>Hello CMS</p>', (string)$res['data']['value']);
    }

    public function testLoginRequiredRejectionForGuest(): void
    {
        $res = $this->executionService->execute('base64-encoder', ['text' => 'Hello']);
        $this->assertFalse($res['success']);
        $this->assertEquals('AUTH_REQUIRED', $res['error']['code']);
    }

    public function testLoginRequiredAllowsLoggedInUser(): void
    {
        $res = $this->executionService->execute('base64-encoder', ['text' => 'Hello World'], 123);
        $this->assertTrue($res['success']);
        $this->assertEquals(base64_encode('Hello World'), $res['data']['value']);
    }

    public function testMembershipRequiredRejectionForRegularUser(): void
    {
        $res = $this->executionService->execute('css-minifier', ['css' => 'body { color: red; }'], 123);
        $this->assertFalse($res['success']);
        $this->assertEquals('MEMBERSHIP_REQUIRED', $res['error']['code']);
    }

    public function testMembershipRequiredAllowsMember(): void
    {
        // User 999 has active membership according to mock
        $res = $this->executionService->execute('css-minifier', ['css' => "body {\n  color: red;\n}"], 999);
        $this->assertTrue($res['success']);
        $this->assertEquals('body{color:red;}', trim((string)$res['data']['value']));
    }

    public function testMembershipRequiredAllowsAdmin(): void
    {
        // Admin bypasses membership restriction
        $res = $this->executionService->execute('css-minifier', ['css' => 'body { color: blue; }'], 456, true);
        $this->assertTrue($res['success']);
        $this->assertStringContainsString('color:blue', (string)$res['data']['value']);
    }

    public function testValidationFailureWhenRequiredInputMissing(): void
    {
        // Empty input
        $res = $this->executionService->execute('html-formatter', ['html' => '']);
        $this->assertFalse($res['success']);
        $this->assertEquals('VALIDATION_FAILED', $res['error']['code']);
        $this->assertNotEmpty($res['error']['details']);
    }

    public function testDownloadManagerGeneratesAndRetrievesDownload(): void
    {
        $content = 'Sample exported file content for test.';
        $filename = 'export-test.txt';

        $downloadInfo = $this->downloadManager->createDownload($content, $filename);
        $this->assertArrayHasKey('reference', $downloadInfo);
        $this->assertArrayHasKey('filename', $downloadInfo);
        $this->assertEquals($filename, $downloadInfo['filename']);

        // Retrieve download
        $retrieved = $this->downloadManager->getDownload($downloadInfo['reference']);
        $this->assertNotNull($retrieved);
        $this->assertEquals($filename, $retrieved['filename']);
        $this->assertEquals($content, $retrieved['content']);
        $this->assertEquals(strlen($content), $retrieved['size']);

        // Clean up
        $this->downloadManager->deleteDownload($downloadInfo['reference']);
        $this->assertNull($this->downloadManager->getDownload($downloadInfo['reference']));
    }

    public function testExecutionWithDownloadRequest(): void
    {
        $res = $this->executionService->execute('html-formatter', [
            'html'             => '<div>Download me!</div>',
            'request_download' => true,
        ]);

        $this->assertTrue($res['success']);
        $this->assertArrayHasKey('download', $res['data']['meta']);
        $download = $res['data']['meta']['download'];
        $this->assertArrayHasKey('reference', $download);
        $this->assertEquals('html-formatter-result.html', $download['filename']);

        // Verify file was written and can be read
        $retrieved = $this->downloadManager->getDownload($download['reference']);
        $this->assertNotNull($retrieved);
        $this->assertStringContainsString('Download me!', $retrieved['content']);

        // Clean up
        $this->downloadManager->deleteDownload($download['reference']);
    }

    public function testDownloadApiControllerResponses(): void
    {
        $app = $this->createMock(\FavoriteCMS\Core\Application::class);
        $controller = new \FavoriteCMS\Tools\Controllers\Api\DownloadApiController($app, $this->downloadManager);

        $request = new \FavoriteCMS\Core\Request();

        // 1. Invalid reference should return 404
        $res404 = $controller->download($request, 'non-existent-token');
        $this->assertEquals(404, $res404->getStatusCode());

        // 2. Valid download reference should return 200 with content
        $downloadInfo = $this->downloadManager->createDownload('API download test content', 'api-test.txt', 'text/plain');
        $res200 = $controller->download($request, $downloadInfo['reference']);
        $this->assertEquals(200, $res200->getStatusCode());
        $this->assertEquals('API download test content', $res200->getContent());
        $this->assertStringContainsString('api-test.txt', (string)($res200->getHeaders()['Content-Disposition'] ?? ''));

        // Clean up
        $this->downloadManager->deleteDownload($downloadInfo['reference']);
    }
}
