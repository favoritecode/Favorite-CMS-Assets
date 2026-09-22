<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Tests;

use FavoriteCMS\Core\Application;
use FavoriteCMS\Core\Database;
use FavoriteCMS\Core\Request;
use FavoriteCMS\Tools\Controllers\Admin\AdminToolController;
use FavoriteCMS\Tools\Engines\EngineResolver;
use FavoriteCMS\Tools\FavoriteWebToolsPlugin;
use FavoriteCMS\Tools\Models\Tool;
use FavoriteCMS\Tools\Models\ToolCategory;
use FavoriteCMS\Tools\Repositories\CategoryRepository;
use FavoriteCMS\Tools\Repositories\ToolRepository;
use FavoriteCMS\Tools\Services\AccessControlService;
use FavoriteCMS\Tools\Services\ToolExecutionService;
use FavoriteCMS\Tools\Services\ToolRegistryService;
use FavoriteCMS\Tools\Support\AccessMode;
use FavoriteCMS\Tools\Support\ToolStatus;
use PHPUnit\Framework\TestCase;

class CatalogAndRoutingTest extends TestCase
{
    private ToolRepository $toolRepo;
    private CategoryRepository $catRepo;
    private ToolRegistryService $registry;

    /**
     * @var ToolCategory[]
     */
    private array $categories = [];

    /**
     * @var Tool[]
     */
    private array $tools = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Build categories
        $this->categories = [
            1 => new ToolCategory([
                'id'            => 1,
                'name'          => 'Developer Tools',
                'slug'          => 'developer',
                'description'   => 'Formatters and converters for developers',
                'display_order' => 1,
                'status'        => 'active',
            ]),
            2 => new ToolCategory([
                'id'            => 2,
                'name'          => 'Text & Content',
                'slug'          => 'text-content',
                'description'   => 'Text utilities and counters',
                'display_order' => 2,
                'status'        => 'active',
            ]),
            3 => new ToolCategory([
                'id'            => 3,
                'name'          => 'Internal Utilities',
                'slug'          => 'internal',
                'description'   => 'Internal tools',
                'display_order' => 3,
                'status'        => 'disabled',
            ]),
        ];

        // Build tools
        $this->tools = [
            1 => new Tool([
                'id'            => 1,
                'category_id'   => 1,
                'name'          => 'JSON Formatter',
                'slug'          => 'json-formatter',
                'description'   => 'Beautify JSON data',
                'engine'        => 'PHP',
                'access_mode'   => AccessMode::FREE,
                'status'        => ToolStatus::ACTIVE,
                'display_order' => 1,
            ]),
            2 => new Tool([
                'id'            => 2,
                'category_id'   => 1,
                'name'          => 'HTML Formatter',
                'slug'          => 'html-formatter',
                'description'   => 'Beautify HTML markup',
                'engine'        => 'HTML',
                'access_mode'   => AccessMode::FREE,
                'status'        => ToolStatus::ACTIVE,
                'display_order' => 2,
            ]),
            3 => new Tool([
                'id'            => 3,
                'category_id'   => 2,
                'name'          => 'Text Counter',
                'slug'          => 'text-counter',
                'description'   => 'Count words and sentences',
                'engine'        => 'PHP',
                'access_mode'   => AccessMode::LOGIN_REQUIRED,
                'status'        => ToolStatus::ACTIVE,
                'display_order' => 1,
            ]),
            4 => new Tool([
                'id'            => 4,
                'category_id'   => 2,
                'name'          => 'Draft Tool',
                'slug'          => 'draft-tool',
                'description'   => 'Under development',
                'engine'        => 'JAVASCRIPT',
                'access_mode'   => AccessMode::FREE,
                'status'        => ToolStatus::DRAFT,
                'display_order' => 4,
            ]),
            5 => new Tool([
                'id'            => 5,
                'category_id'   => 1,
                'name'          => 'Disabled Tool',
                'slug'          => 'disabled-tool',
                'description'   => 'Disabled for maintenance',
                'engine'        => 'CSS',
                'access_mode'   => AccessMode::FREE,
                'status'        => ToolStatus::DISABLED,
                'display_order' => 5,
            ]),
        ];

        // Mock Database for Repositories
        $db = $this->createMock(Database::class);

        // Tool Repo Mock
        $this->toolRepo = $this->createMock(ToolRepository::class);
        $this->toolRepo->method('findById')->willReturnCallback(function (int $id): ?Tool {
            return $this->tools[$id] ?? null;
        });
        $this->toolRepo->method('findBySlug')->willReturnCallback(function (string $slug): ?Tool {
            foreach ($this->tools as $tool) {
                if ($tool->slug === $slug) {
                    return $tool;
                }
            }
            return null;
        });
        $this->toolRepo->method('getPublicTools')->willReturnCallback(function (array $filters = [], int $page = 1, int $perPage = 20): array {
            $catId = $filters['category'] ?? $filters['category_id'] ?? null;
            $search = strtolower(trim((string)($filters['search'] ?? '')));
            $filtered = array_filter($this->tools, function (Tool $t) use ($catId, $search) {
                if (!$t->isActive()) return false;
                if ($catId !== null && (int)$t->category_id !== (int)$catId) return false;
                if ($search !== '' && !str_contains(strtolower($t->name), $search) && !str_contains(strtolower($t->slug), $search)) {
                    return false;
                }
                return true;
            });
            return array_values($filtered);
        });
        $this->toolRepo->method('getAllAdminTools')->willReturnCallback(function (array $filters = []): array {
            $catId = $filters['category'] ?? $filters['category_id'] ?? null;
            $status = $filters['status'] ?? null;
            $filtered = array_filter($this->tools, function (Tool $t) use ($catId, $status) {
                if ($status !== null && strtoupper($t->status) !== strtoupper($status)) return false;
                if ($catId !== null && (int)$t->category_id !== (int)$catId) return false;
                return true;
            });
            return array_values($filtered);
        });
        $this->toolRepo->method('all')->willReturnCallback(function (): array {
            return array_values($this->tools);
        });
        $this->toolRepo->method('countByStatus')->willReturnCallback(function (): array {
            $active = 0; $draft = 0; $disabled = 0;
            foreach ($this->tools as $t) {
                if ($t->isActive()) $active++;
                elseif ($t->isDraft()) $draft++;
                elseif ($t->isDisabled()) $disabled++;
            }
            return [
                'active'   => $active,
                'draft'    => $draft,
                'disabled' => $disabled,
                'total'    => count($this->tools),
            ];
        });

        // Category Repo Mock
        $this->catRepo = $this->createMock(CategoryRepository::class);
        $this->catRepo->method('findById')->willReturnCallback(function (int $id): ?ToolCategory {
            return $this->categories[$id] ?? null;
        });
        $this->catRepo->method('findBySlug')->willReturnCallback(function (string $slug): ?ToolCategory {
            foreach ($this->categories as $c) {
                if ($c->slug === $slug) {
                    return $c;
                }
            }
            return null;
        });
        $this->catRepo->method('allActive')->willReturnCallback(function (): array {
            return array_values(array_filter($this->categories, fn($c) => $c->isActive()));
        });
        $this->catRepo->method('all')->willReturnCallback(function (): array {
            return array_values($this->categories);
        });

        $this->registry = new ToolRegistryService($this->toolRepo);
    }

    public function testCategoryRepositoryActiveCategories(): void
    {
        $activeCats = $this->catRepo->allActive();
        $this->assertCount(2, $activeCats);
        $this->assertEquals('Developer Tools', $activeCats[0]->name);
        $this->assertEquals('Text & Content', $activeCats[1]->name);

        $dev = $this->catRepo->findBySlug('developer');
        $this->assertNotNull($dev);
        $this->assertEquals(1, $dev->id);
    }

    public function testPublicToolListingHidesDraftAndDisabled(): void
    {
        $publicTools = $this->registry->getActiveTools();
        $this->assertCount(3, $publicTools);

        $slugs = array_map(fn($t) => $t->slug, $publicTools);
        $this->assertContains('json-formatter', $slugs);
        $this->assertContains('html-formatter', $slugs);
        $this->assertContains('text-counter', $slugs);
        $this->assertNotContains('draft-tool', $slugs);
        $this->assertNotContains('disabled-tool', $slugs);
    }

    public function testCategoryFiltering(): void
    {
        $devTools = $this->registry->getToolsByCategory(1);
        $this->assertCount(2, $devTools);
        foreach ($devTools as $tool) {
            $this->assertEquals(1, $tool->category_id);
            $this->assertTrue($tool->isActive());
        }

        $textTools = $this->registry->getToolsByCategory(2);
        $this->assertCount(1, $textTools);
        $this->assertEquals('text-counter', $textTools[0]->slug);
    }

    public function testSearchTools(): void
    {
        $res = $this->registry->searchTools('json');
        $this->assertCount(1, $res);
        $this->assertEquals('json-formatter', $res[0]->slug);

        $resFormat = $this->registry->searchTools('formatter');
        $this->assertCount(2, $resFormat);

        $resEmpty = $this->registry->searchTools('nonexistent');
        $this->assertCount(0, $resEmpty);
    }

    public function testPublicSlugResolutionSecurity(): void
    {
        // 1. Active tool resolves for public
        $tool = $this->registry->getPublicTool('json-formatter');
        $this->assertNotNull($tool);
        $this->assertEquals('JSON Formatter', $tool->name);

        // 2. Draft tool returns null for public
        $draft = $this->registry->getPublicTool('draft-tool');
        $this->assertNull($draft);

        // 3. Disabled tool returns null for public
        $disabled = $this->registry->getPublicTool('disabled-tool');
        $this->assertNull($disabled);
    }

    public function testToolStatusCounts(): void
    {
        $counts = $this->toolRepo->countByStatus();
        $this->assertEquals(3, $counts['active']);
        $this->assertEquals(1, $counts['draft']);
        $this->assertEquals(1, $counts['disabled']);
        $this->assertEquals(5, $counts['total']);
    }

    public function testAdminControllerResolutionViaDiContainer(): void
    {
        FavoriteWebToolsPlugin::reset();
        $app = new Application();
        $dummyDb = new class extends Database {
            public function __construct() {}
            public function registerPrefixableTables(array|string ...$tables): void {}
            public function query(string $sql, array $bindings = []): \PDOStatement {
                return new class extends \PDOStatement {};
            }
        };
        $app->instance(Database::class, $dummyDb);

        $plugin = FavoriteWebToolsPlugin::getInstance($app);
        $plugin->register();

        $engineResolver = $app->make(EngineResolver::class);
        $this->assertInstanceOf(EngineResolver::class, $engineResolver);

        $executionService = $app->make(ToolExecutionService::class);
        $this->assertInstanceOf(ToolExecutionService::class, $executionService);

        $adminToolController = $app->make(AdminToolController::class);
        $this->assertInstanceOf(AdminToolController::class, $adminToolController);
    }

    public function testAdminPageFavoriteWebToolsRendersSuccessfully(): void
    {
        FavoriteWebToolsPlugin::reset();
        $app = new Application();
        $dummyDb = new class extends Database {
            public function __construct() {}
            public function registerPrefixableTables(array|string ...$tables): void {}
        };
        $app->instance(Database::class, $dummyDb);

        $plugin = FavoriteWebToolsPlugin::getInstance($app);
        $plugin->register();

        $app->instance(ToolRepository::class, $this->toolRepo);
        $app->instance(CategoryRepository::class, $this->catRepo);

        $adminToolController = $app->make(AdminToolController::class);

        $GLOBALS['_test_current_user'] = new class {
            public function can(string $capability): bool {
                return true;
            }
        };

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $request = new Request([], [], [], [], ['REQUEST_URI' => '/admin/page/favorite-web-tools', 'REQUEST_METHOD' => 'GET']);

        $response = $adminToolController->handle($request);
        $html = (string)$response;

        $this->assertNotEmpty($html);
        $this->assertStringContainsString('Web Tools', $html);
        $this->assertStringContainsString('JSON Formatter', $html);
        $this->assertStringContainsString('+ Add New Tool', $html);
    }
}
