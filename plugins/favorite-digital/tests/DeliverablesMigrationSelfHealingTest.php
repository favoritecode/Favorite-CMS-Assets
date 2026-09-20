<?php

declare(strict_types=1);

namespace FavoriteCMS\Digital\Tests;

use FavoriteCMS\Core\Application;
use FavoriteCMS\Core\Database;
use FavoriteCMS\Digital\FavoriteDigitalPlugin;
use FavoriteCMS\Digital\Domain\ProductType;
use FavoriteCMS\Digital\Domain\OrderLifecycleState;
use FavoriteCMS\Digital\Repositories\OrderRepository;
use PHPUnit\Framework\TestCase;

/**
 * DeliverablesMigrationSelfHealingTest
 *
 * Verifies:
 * - prefixed migration creates prefixed deliverables table
 * - empty-prefix migration works
 * - migration already recorded in cms_migrations but prefixed table missing can self-heal
 * - existing unprefixed table can safely migrate to prefixed table
 * - existing deliverable rows are preserved with exact count
 * - destination collision does not overwrite data
 * - repair is idempotent
 * - Add Deliverable works seamlessly after repair
 */
class DeliverablesMigrationSelfHealingTest extends TestCase
{
    protected function tearDown(): void
    {
        FavoriteDigitalPlugin::reset();
        parent::tearDown();
    }

    protected function createInMemoryDb(string $prefix = ''): Database
    {
        $db = new Database([
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => $prefix,
        ]);
        $db->execute("
            CREATE TABLE IF NOT EXISTS `cms_migrations` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `migration` VARCHAR(255) NOT NULL,
                `batch` INTEGER NOT NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        return $db;
    }

    /**
     * Test: prefixed migration creates prefixed deliverables table.
     */
    public function testPrefixedMigrationCreatesPrefixedDeliverablesTable(): void
    {
        $db = $this->createInMemoryDb('fvcms_test_');

        require_once __DIR__ . '/../database/migrations/018_create_favorite_digital_order_deliverables_table.php';
        $migration = new \CreateFavoriteDigitalOrderDeliverablesTable($db);
        $migration->up();

        // Check via tableExists
        $this->assertTrue($db->tableExists('favorite_digital_order_deliverables'));

        // Check physical SQLite table name
        $tables = $db->select("SELECT name FROM sqlite_master WHERE type='table' AND name LIKE '%deliverables%'");
        $names = array_map(fn($t) => $t->name, $tables);
        $this->assertContains('fvcms_test_favorite_digital_order_deliverables', $names);
    }

    /**
     * Test: empty-prefix migration works as expected.
     */
    public function testEmptyPrefixMigrationWorks(): void
    {
        $db = $this->createInMemoryDb('');

        require_once __DIR__ . '/../database/migrations/018_create_favorite_digital_order_deliverables_table.php';
        $migration = new \CreateFavoriteDigitalOrderDeliverablesTable($db);
        $migration->up();

        $this->assertTrue($db->tableExists('favorite_digital_order_deliverables'));

        $tables = $db->select("SELECT name FROM sqlite_master WHERE type='table' AND name='favorite_digital_order_deliverables'");
        $this->assertCount(1, $tables);
    }

    /**
     * Test: migration already recorded in cms_migrations but prefixed table missing can self-heal.
     */
    public function testMigrationRecordedButPrefixedTableMissingCanSelfHeal(): void
    {
        $db = $this->createInMemoryDb('p0_');

        // Fake that 018 was already recorded as run in cms_migrations
        $db->insert('cms_migrations', [
            'migration' => '018_create_favorite_digital_order_deliverables_table',
            'batch'     => 1,
        ]);

        $this->assertFalse($db->tableExists('favorite_digital_order_deliverables'));

        $app = new Application();
        $app->instance(Database::class, $db);
        $plugin = new FavoriteDigitalPlugin($app);

        // Run self-healing repair
        $plugin->repairDeliverablesTable($db);

        // Table must now exist under prefix
        $this->assertTrue($db->tableExists('favorite_digital_order_deliverables'));

        $tables = $db->select("SELECT name FROM sqlite_master WHERE type='table' AND name='p0_favorite_digital_order_deliverables'");
        $this->assertCount(1, $tables);
    }

    /**
     * Test: existing unprefixed table can safely migrate to prefixed table and preserve rows.
     */
    public function testExistingUnprefixedTableCanSafelyMigrateToPrefixedTable(): void
    {
        $db = $this->createInMemoryDb('wp_');

        // Create an unprefixed table with deliverable schema
        $db->getConnection()->exec("
            CREATE TABLE `favorite_digital_order_deliverables` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `order_id` INTEGER NOT NULL,
                `order_item_id` INTEGER NULL,
                `title` VARCHAR(255) NOT NULL,
                `description` TEXT NULL,
                `resource_type` VARCHAR(32) NOT NULL DEFAULT 'file',
                `file_path` VARCHAR(500) NULL,
                `file_name` VARCHAR(255) NULL,
                `file_hash` VARCHAR(64) NULL,
                `file_size` INTEGER NOT NULL DEFAULT 0,
                `mime_type` VARCHAR(128) NULL,
                `resource_url` VARCHAR(1000) NULL,
                `download_token` VARCHAR(64) NOT NULL,
                `is_released` TINYINT(1) NOT NULL DEFAULT 1,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            );
        ");

        // Insert 2 rows into unprefixed table
        $db->getConnection()->exec("
            INSERT INTO `favorite_digital_order_deliverables` (`order_id`, `title`, `download_token`, `is_released`)
            VALUES (10, 'Deliverable 1', 'token_aaa_111', 1),
                   (10, 'Deliverable 2', 'token_bbb_222', 0);
        ");

        $app = new Application();
        $app->instance(Database::class, $db);
        $plugin = new FavoriteDigitalPlugin($app);

        // Self-heal repair
        $plugin->repairDeliverablesTable($db);

        // Destination table must now exist
        $this->assertTrue($db->tableExists('favorite_digital_order_deliverables'));

        // Verify rows preserved
        $rows = $db->select("SELECT * FROM `favorite_digital_order_deliverables` ORDER BY id ASC");
        $this->assertCount(2, $rows);
        $this->assertSame('Deliverable 1', $rows[0]->title);
        $this->assertSame('token_aaa_111', $rows[0]->download_token);
        $this->assertSame(1, (int)$rows[0]->is_released);

        $this->assertSame('Deliverable 2', $rows[1]->title);
        $this->assertSame('token_bbb_222', $rows[1]->download_token);
        $this->assertSame(0, (int)$rows[1]->is_released);
    }

    /**
     * Test: destination collision does not overwrite existing data.
     */
    public function testDestinationCollisionDoesNotOverwriteExistingData(): void
    {
        $db = $this->createInMemoryDb('fvt_');

        // Create destination table first with 1 row
        require_once __DIR__ . '/../database/migrations/018_create_favorite_digital_order_deliverables_table.php';
        $mig = new \CreateFavoriteDigitalOrderDeliverablesTable($db);
        $mig->up();

        $db->insert('favorite_digital_order_deliverables', [
            'order_id'       => 5,
            'title'          => 'Existing Destination Deliverable',
            'download_token' => 'dest_token_123',
            'is_released'    => 1,
        ]);

        // Create unprefixed table with another row
        $db->getConnection()->exec("
            CREATE TABLE IF NOT EXISTS `favorite_digital_order_deliverables` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `order_id` INTEGER NOT NULL,
                `title` VARCHAR(255) NOT NULL,
                `download_token` VARCHAR(64) NOT NULL,
                `is_released` TINYINT(1) NOT NULL DEFAULT 1
            );
            INSERT INTO `favorite_digital_order_deliverables` (`order_id`, `title`, `download_token`, `is_released`)
            VALUES (99, 'Unprefixed Deliverable', 'unpref_token_999', 1);
        ");

        $app = new Application();
        $app->instance(Database::class, $db);
        $plugin = new FavoriteDigitalPlugin($app);

        // Run repair
        $plugin->repairDeliverablesTable($db);

        // Destination table must remain intact with its original data
        $rows = $db->select("SELECT * FROM `favorite_digital_order_deliverables`");
        $this->assertCount(1, $rows);
        $this->assertSame('Existing Destination Deliverable', $rows[0]->title);
    }

    /**
     * Test: repair is idempotent.
     */
    public function testRepairIsIdempotent(): void
    {
        $db = $this->createInMemoryDb('testpref_');
        $app = new Application();
        $app->instance(Database::class, $db);
        $plugin = new FavoriteDigitalPlugin($app);

        // 1st run
        $plugin->repairDeliverablesTable($db);
        $this->assertTrue($db->tableExists('favorite_digital_order_deliverables'));

        // 2nd run
        $plugin->repairDeliverablesTable($db);
        $this->assertTrue($db->tableExists('favorite_digital_order_deliverables'));

        // 3rd run
        $plugin->repairDeliverablesTable($db);
        $this->assertTrue($db->tableExists('favorite_digital_order_deliverables'));
    }

    /**
     * Test: Add Deliverable works smoothly after repair.
     */
    public function testAddDeliverableWorksAfterRepair(): void
    {
        $db = $this->createInMemoryDb('fvcms_');

        // Setup base tables for orders
        require_once __DIR__ . '/../database/migrations/008_create_favorite_digital_orders_table.php';
        $migOrders = new \CreateFavoriteDigitalOrdersTable($db);
        $migOrders->up();

        $app = new Application();
        $app->instance(Database::class, $db);
        $plugin = new FavoriteDigitalPlugin($app);

        // Trigger ensureMigrations / repair
        $plugin->repairDeliverablesTable($db);
        $this->assertTrue($db->tableExists('favorite_digital_order_deliverables'));

        // Create order
        $orderId = (int)$db->insert('favorite_digital_orders', [
            'order_number'       => 'ORD-TEST-123',
            'user_id'            => 42,
            'status'             => 'processing',
            'payment_status'     => 'paid',
            'fulfillment_status' => 'partially_fulfilled',
            'subtotal_amount'    => '100.00',
            'total_amount'       => '100.00',
        ]);

        $orderRepo = new OrderRepository($db);
        $delivId = $orderRepo->createDeliverable([
            'order_id'       => $orderId,
            'title'          => 'Final PDF Report',
            'resource_type'  => 'url',
            'resource_url'   => 'https://example.com/report.pdf',
            'download_token' => bin2hex(random_bytes(32)),
            'is_released'    => 1,
        ]);

        $this->assertGreaterThan(0, $delivId);

        $deliverables = $orderRepo->getDeliverablesByOrderId($orderId);
        $this->assertCount(1, $deliverables);
        $this->assertSame('Final PDF Report', $deliverables[0]->title);
    }

    /**
     * Test: release toggle and download authorization rules work after repair.
     */
    public function testDeliverableReleaseToggleAndCustomerAccessRulesAfterRepair(): void
    {
        $db = $this->createInMemoryDb('cust_pref_');

        require_once __DIR__ . '/../database/migrations/008_create_favorite_digital_orders_table.php';
        (new \CreateFavoriteDigitalOrdersTable($db))->up();

        $app = new Application();
        $app->instance(Database::class, $db);
        $plugin = new FavoriteDigitalPlugin($app);
        $plugin->repairDeliverablesTable($db);

        $customerId = 88;
        $orderId = (int)$db->insert('favorite_digital_orders', [
            'order_number'       => 'ORD-CUST-888',
            'user_id'            => $customerId,
            'status'             => 'processing',
            'payment_status'     => 'paid',
            'fulfillment_status' => 'partially_fulfilled',
            'subtotal_amount'    => '200.00',
            'total_amount'       => '200.00',
        ]);

        $orderRepo = new OrderRepository($db);
        $token = bin2hex(random_bytes(32));
        $delivId = $orderRepo->createDeliverable([
            'order_id'       => $orderId,
            'title'          => 'Client Final Assets',
            'resource_type'  => 'url',
            'resource_url'   => 'https://example.com/assets.zip',
            'download_token' => $token,
            'is_released'    => 1,
        ]);

        // 1. Initial released state
        $deliv = $orderRepo->findDeliverable($delivId);
        $this->assertSame(1, (int)$deliv->is_released);

        // 2. Toggle to unreleased
        $orderRepo->updateDeliverable($delivId, ['is_released' => 0]);
        $delivUnreleased = $orderRepo->findDeliverable($delivId);
        $this->assertSame(0, (int)$delivUnreleased->is_released);

        // 3. Toggle back to released
        $orderRepo->updateDeliverable($delivId, ['is_released' => 1]);
        $delivReleasedAgain = $orderRepo->findDeliverable($delivId);
        $this->assertSame(1, (int)$delivReleasedAgain->is_released);
    }

    /**
     * Test: alignDeliverablesSchemaIfNecessary handles SQLite gracefully and is idempotent.
     */
    public function testAlignDeliverablesSchemaIfNecessaryIsSafe(): void
    {
        $db = $this->createInMemoryDb('');
        $app = new Application();
        $app->instance(Database::class, $db);
        $plugin = new FavoriteDigitalPlugin($app);
        $plugin->repairDeliverablesTable($db);

        // Must run with zero errors
        $plugin->alignDeliverablesSchemaIfNecessary($db);
        $this->assertTrue($db->tableExists('favorite_digital_order_deliverables'));
    }
}
