<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Tests;

use FavoriteCMS\Core\Database;
use FavoriteCMS\Tools\Models\Tool;
use FavoriteCMS\Tools\Models\ToolCategory;
use FavoriteCMS\Tools\Repositories\CategoryRepository;
use FavoriteCMS\Tools\Repositories\ToolRepository;
use FavoriteCMS\Tools\Services\ToolCatalogSeeder;
use FavoriteCMS\Tools\Services\ToolRegistryService;
use PHPUnit\Framework\TestCase;

class ToolRegistryTest extends TestCase
{
    private Database $db;
    private CategoryRepository $catRepo;
    private ToolRepository $toolRepo;
    private ToolRegistryService $registry;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db = new Database([
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        $this->db->registerPrefixableTables([
            'favorite_web_tool_categories',
            'favorite_web_tool_python_services',
            'favorite_web_tools',
        ]);

        // Run migrations
        require_once dirname(__DIR__) . '/database/migrations/001_create_favorite_web_tool_categories_table.php';
        require_once dirname(__DIR__) . '/database/migrations/002_create_favorite_web_tool_python_services_table.php';
        require_once dirname(__DIR__) . '/database/migrations/003_create_favorite_web_tools_table.php';

        (new \CreateFavoriteWebToolCategoriesTable($this->db))->up();
        (new \CreateFavoriteWebToolPythonServicesTable($this->db))->up();
        (new \CreateFavoriteWebToolsTable($this->db))->up();

        $this->catRepo = new CategoryRepository($this->db);
        $this->toolRepo = new ToolRepository($this->db);
        $this->registry = new ToolRegistryService($this->toolRepo);
    }

    public function testCategoryCrudAndFiltering(): void
    {
        $cat = $this->catRepo->create([
            'name'          => 'Developer Tools',
            'slug'          => 'developer',
            'description'   => 'Essential developer tools',
            'icon'          => '💻',
            'display_order' => 1,
            'is_active'     => 1,
        ]);

        $this->assertGreaterThan(0, $cat->id);
        $this->assertEquals('Developer Tools', $cat->name);
        $this->assertEquals('developer', $cat->slug);

        $found = $this->catRepo->findBySlug('developer');
        $this->assertNotNull($found);
        $this->assertEquals($cat->id, $found->id);

        $activeCats = $this->catRepo->allActive();
        $this->assertCount(1, $activeCats);

        // Deactivate
        $this->catRepo->update($cat->id, ['is_active' => 0]);
        $this->assertCount(0, $this->catRepo->allActive());
        $this->assertCount(1, $this->catRepo->all());
    }

    public function testToolCreationAndSlugLookup(): void
    {
        $cat = $this->catRepo->create([
            'name'      => 'JSON Tools',
            'slug'      => 'json-tools',
            'is_active' => 1,
        ]);

        $tool = $this->toolRepo->create([
            'name'          => 'JSON Formatter',
            'slug'          => 'json-formatter',
            'description'   => 'Format JSON prettily',
            'category_id'   => $cat->id,
            'engine'        => 'PHP',
            'handler_class' => 'FavoriteCMS\Tools\Handlers\JsonFormatterHandler',
            'access_mode'   => 'FREE',
            'status'        => 'ACTIVE',
            'input_schema'  => [
                'json' => ['type' => 'textarea', 'required' => true]
            ],
            'output_schema' => [
                'formatted' => ['type' => 'TEXT']
            ],
            'ui_schema'     => [
                'submit_label' => 'Format JSON'
            ],
        ]);

        $this->assertGreaterThan(0, $tool->id);
        $this->assertEquals('json-formatter', $tool->slug);
        $this->assertTrue($tool->isActive());
        $this->assertFalse($tool->isDraft());
        $this->assertFalse($tool->isDisabled());
        $this->assertEquals('Format JSON', $tool->ui_schema['submit_label']);

        // Registry lookup
        $found = $this->registry->findBySlug('json-formatter');
        $this->assertNotNull($found);
        $this->assertEquals($tool->id, $found->id);
    }

    public function testStatusTransitions(): void
    {
        $tool = $this->toolRepo->create([
            'name'        => 'Draft Tool',
            'slug'        => 'draft-tool',
            'engine'      => 'PHP',
            'access_mode' => 'FREE',
            'status'      => 'DRAFT',
        ]);

        $this->assertTrue($tool->isDraft());
        $this->assertFalse($tool->isActive());

        // Update to ACTIVE
        $this->toolRepo->updateStatus($tool->id, 'ACTIVE');
        $updated = $this->toolRepo->find($tool->id);
        $this->assertTrue($updated->isActive());

        // Update to DISABLED
        $this->toolRepo->updateStatus($tool->id, 'DISABLED');
        $disabled = $this->toolRepo->find($tool->id);
        $this->assertTrue($disabled->isDisabled());
    }

    public function testSearchAndFiltering(): void
    {
        $cat1 = $this->catRepo->create(['name' => 'HTML', 'slug' => 'html', 'is_active' => 1]);
        $cat2 = $this->catRepo->create(['name' => 'CSS', 'slug' => 'css', 'is_active' => 1]);

        $this->toolRepo->create([
            'name'        => 'HTML Minifier',
            'slug'        => 'html-minifier',
            'category_id' => $cat1->id,
            'engine'      => 'HTML',
            'access_mode' => 'FREE',
            'status'      => 'ACTIVE',
        ]);

        $this->toolRepo->create([
            'name'        => 'CSS Minifier',
            'slug'        => 'css-minifier',
            'category_id' => $cat2->id,
            'engine'      => 'CSS',
            'access_mode' => 'LOGIN_REQUIRED',
            'status'      => 'ACTIVE',
        ]);

        $this->toolRepo->create([
            'name'        => 'CSS Formatter',
            'slug'        => 'css-formatter',
            'category_id' => $cat2->id,
            'engine'      => 'CSS',
            'access_mode' => 'FREE',
            'status'      => 'DRAFT',
        ]);

        // Search for 'Minifier' in ACTIVE only
        $res = $this->registry->searchTools(['search' => 'Minifier'], 1, 10);
        $this->assertEquals(2, $res['total']);

        // Filter by category HTML
        $resCat = $this->registry->searchTools(['category_id' => $cat1->id], 1, 10);
        $this->assertEquals(1, $resCat['total']);
        $this->assertEquals('html-minifier', $resCat['items'][0]->slug);

        // Filter by access mode FREE
        $resFree = $this->registry->searchTools(['access_mode' => 'FREE'], 1, 10);
        $this->assertEquals(1, $resFree['total']); // only active free tool (html-minifier), css-formatter is draft
    }

    public function testCatalogSeederPopulatesDefaultCatalog(): void
    {
        $seeder = new ToolCatalogSeeder($this->db, $this->catRepo, $this->toolRepo);
        $seeder->seedIfEmpty();

        $allCats = $this->catRepo->all();
        $this->assertGreaterThanOrEqual(7, count($allCats));

        $allTools = $this->toolRepo->all();
        $this->assertGreaterThanOrEqual(35, count($allTools));

        // Verify JSON Formatter exists and is seeded
        $jsonFormatter = $this->toolRepo->findBySlug('json-formatter');
        $this->assertNotNull($jsonFormatter);
        $this->assertEquals('JSON Formatter', $jsonFormatter->name);
        $this->assertEquals('ACTIVE', $jsonFormatter->status);

        // Seeder idempotency test: running again does not duplicate tools
        $countBefore = count($allTools);
        $seeder->seedIfEmpty();
        $this->assertEquals($countBefore, count($this->toolRepo->all()));
    }
}
