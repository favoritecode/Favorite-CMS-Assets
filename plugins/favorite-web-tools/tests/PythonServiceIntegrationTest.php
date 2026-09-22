<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Tests;

use FavoriteCMS\Core\Database;
use FavoriteCMS\Tools\Engines\PythonApiEngine;
use FavoriteCMS\Tools\Models\PythonService;
use FavoriteCMS\Tools\Models\Tool;
use FavoriteCMS\Tools\Repositories\PythonServiceRepository;
use FavoriteCMS\Tools\Services\PythonClientService;
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
        // Should not throw exception
        $this->client->validateUrl('https://api.python-tools.example.com');
        $this->client->validateUrl('http://ml-service.internal:8080');
        $this->assertTrue(true);
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

    public function testValidateUrlRejectsLinkLocalAndResolvedSsrf(): void
    {
        $blockedUrls = [
            'http://169.254.1.1/internal',
            'http://169.254.255.254:8080/test',
            'http://0.0.0.0:8000/',
        ];

        foreach ($blockedUrls as $url) {
            try {
                $this->client->validateUrl($url);
                $this->fail("Expected InvalidArgumentException for link-local URL: {$url}");
            } catch (InvalidArgumentException $e) {
                $this->assertStringContainsString('forbidden', $e->getMessage());
            }
        }
    }

    public function testPythonServiceRepositoryOperations(): void
    {
        $db = $this->createMock(Database::class);

        // Mock selectOne for findById
        $db->method('selectOne')->willReturnCallback(function ($query, $params) {
            if ($params[0] === 1) {
                $row = new \stdClass();
                $row->id = 1;
                $row->name = 'Production ML Worker';
                $row->base_url = 'https://ml.example.com';
                $row->auth_type = 'bearer';
                $row->api_key = 'secret_key_123';
                $row->timeout = 30;
                $row->is_active = 1;
                return $row;
            }
            return null;
        });

        // Mock select for all
        $db->method('select')->willReturnCallback(function ($query, $params) {
            $row = new \stdClass();
            $row->id = 1;
            $row->name = 'Production ML Worker';
            $row->base_url = 'https://ml.example.com';
            $row->auth_type = 'bearer';
            $row->api_key = 'secret_key_123';
            $row->timeout = 30;
            $row->is_active = 1;
            return [$row];
        });

        $db->method('insert')->willReturn(1);
        $db->method('lastInsertId')->willReturn('1');
        $db->method('update')->willReturn(1);
        $db->method('delete')->willReturn(1);
        $db->method('execute')->willReturn(true);

        $repo = new PythonServiceRepository($db);

        // 1. findById
        $service = $repo->findById(1);
        $this->assertNotNull($service);
        $this->assertEquals(1, $service->id);
        $this->assertEquals('Production ML Worker', $service->name);
        $this->assertTrue($service->isActive());
        $this->assertEquals('bearer', $service->auth_type);

        // 2. allActive
        $all = $repo->allActive();
        $this->assertCount(1, $all);

        // 3. create
        $created = $repo->create([
            'name'      => 'New Python Service',
            'base_url'  => 'https://new-ml.example.com',
            'auth_type' => 'api_key',
            'api_key'   => 'test_key',
            'timeout'   => 15,
            'is_active' => 1,
        ]);
        $this->assertInstanceOf(PythonService::class, $created);
        $this->assertEquals(1, $created->id);

        // 4. delete
        $deleted = $repo->delete(1);
        $this->assertTrue($deleted);
    }

    public function testPythonApiEngineCanHandle(): void
    {
        $db = $this->createMock(Database::class);
        $repo = new PythonServiceRepository($db);
        $engine = new PythonApiEngine($repo, $this->client);

        $pythonTool = new Tool(['engine' => EngineType::PYTHON_API]);
        $htmlTool = new Tool(['engine' => EngineType::HTML]);
        $phpTool = new Tool(['engine' => EngineType::PHP]);

        $this->assertTrue($engine->canHandle($pythonTool));
        $this->assertFalse($engine->canHandle($htmlTool));
        $this->assertFalse($engine->canHandle($phpTool));
    }

    public function testPythonApiEngineValidation(): void
    {
        $db = $this->createMock(Database::class);
        $db->method('selectOne')->willReturnCallback(function ($query, $params) {
            if ($params[0] === 1) {
                // Active service
                $row = new \stdClass();
                $row->id = 1;
                $row->name = 'Active Worker';
                $row->base_url = 'https://active.example.com';
                $row->is_active = 1;
                return $row;
            } elseif ($params[0] === 2) {
                // Disabled service
                $row = new \stdClass();
                $row->id = 2;
                $row->name = 'Disabled Worker';
                $row->base_url = 'https://disabled.example.com';
                $row->is_active = 0;
                return $row;
            }
            return null;
        });

        $repo = new PythonServiceRepository($db);
        $engine = new PythonApiEngine($repo, $this->client);

        // 1. Tool referencing active service passes
        $validTool = new Tool([
            'engine'        => EngineType::PYTHON_API,
            'configuration' => ['python_service_id' => 1],
        ]);
        $this->assertEmpty($engine->validate($validTool, []));

        // 2. Tool referencing disabled service fails
        $disabledTool = new Tool([
            'engine'        => EngineType::PYTHON_API,
            'configuration' => ['python_service_id' => 2],
        ]);
        $errors = $engine->validate($disabledTool, []);
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('unavailable or disabled', $errors[0]);

        // 3. Tool referencing non-existent service fails
        $nonExistentTool = new Tool([
            'engine'        => EngineType::PYTHON_API,
            'configuration' => ['python_service_id' => 999],
        ]);
        $errorsNotFound = $engine->validate($nonExistentTool, []);
        $this->assertNotEmpty($errorsNotFound);
    }

    public function testRequestHandlesInvalidUrlGracefully(): void
    {
        $invalidService = new PythonService([
            'id'        => 3,
            'name'      => 'Invalid URL Service',
            'base_url'  => 'ftp://ftp.example.com',
            'is_active' => 1,
        ]);

        $res = $this->client->request($invalidService, 'GET', '/health');
        $this->assertFalse($res['success']);
        $this->assertEquals(500, $res['status']);
        $this->assertStringContainsString('Only http and https', $res['error']);
    }
}
