<?php

declare(strict_types=1);

use FavoriteCMS\Core\Database;

/**
 * Favorite Digital — Migration 018: Order Deliverables Table
 *
 * Stores customer-accessible service deliverables/documents (reports, certificates,
 * invoices, project files) associated with service orders, supporting direct uploads
 * and external URLs via existing download/resource architecture.
 */
class CreateFavoriteDigitalOrderDeliverablesTable
{
    protected Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
        if (method_exists($this->db, 'registerPrefixableTables')) {
            $this->db->registerPrefixableTables('favorite_digital_order_deliverables');
        }
    }

    public function up(): void
    {
        if (method_exists($this->db, 'registerPrefixableTables')) {
            $this->db->registerPrefixableTables('favorite_digital_order_deliverables');
        }

        $isSqlite = $this->isSqlite();
        $engine = $isSqlite ? '' : ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
        $pkBigint = $isSqlite ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY';
        $bigintCol = $isSqlite ? 'INTEGER' : 'BIGINT UNSIGNED';
        $bigintColNullable = $isSqlite ? 'INTEGER NULL' : 'BIGINT UNSIGNED NULL';
        $updatedAt = $isSqlite
            ? 'DATETIME DEFAULT CURRENT_TIMESTAMP'
            : 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP';
        $createdAt = $isSqlite
            ? 'DATETIME DEFAULT CURRENT_TIMESTAMP'
            : 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP';

        $this->db->execute("
            CREATE TABLE IF NOT EXISTS `favorite_digital_order_deliverables` (
                `id`             {$pkBigint},
                `order_id`       {$bigintCol}  NOT NULL,
                `order_item_id`  {$bigintColNullable},
                `title`          VARCHAR(255)  NOT NULL,
                `description`    TEXT          NULL,
                `resource_type`  VARCHAR(32)   NOT NULL DEFAULT 'file',
                `file_path`      VARCHAR(500)  NULL,
                `file_name`      VARCHAR(255)  NULL,
                `file_hash`      VARCHAR(64)   NULL,
                `file_size`      {$bigintCol}  NOT NULL DEFAULT 0,
                `mime_type`      VARCHAR(128)  NULL,
                `resource_url`   VARCHAR(1000) NULL,
                `download_token` VARCHAR(64)   NOT NULL,
                `is_released`    TINYINT(1)    NOT NULL DEFAULT 1,
                `created_at`     {$createdAt},
                `updated_at`     {$updatedAt}
            ){$engine};
        ");

        if (!$isSqlite) {
            $this->migrateSignedBigintColumnsIfNecessary();
        }

        $this->createIndexIfNotExists('favorite_digital_order_deliverables', 'idx_fd_deliv_order', '`order_id`');
        $this->createIndexIfNotExists('favorite_digital_order_deliverables', 'idx_fd_deliv_token', '`download_token`', true);
        $this->createIndexIfNotExists('favorite_digital_order_deliverables', 'idx_fd_deliv_released', '`is_released`');
    }

    public function down(): void
    {
        if (method_exists($this->db, 'registerPrefixableTables')) {
            $this->db->registerPrefixableTables('favorite_digital_order_deliverables');
        }
        $this->db->execute("DROP TABLE IF EXISTS `favorite_digital_order_deliverables`");
    }

    public function migrateSignedBigintColumnsIfNecessary(): void
    {
        if ($this->isSqlite()) {
            return;
        }

        try {
            $tableName = method_exists($this->db, 'table') ? $this->db->table('favorite_digital_order_deliverables') : 'favorite_digital_order_deliverables';

            $cols = $this->db->select("
                SELECT COLUMN_NAME, COLUMN_TYPE 
                FROM information_schema.COLUMNS 
                WHERE TABLE_SCHEMA = DATABASE() 
                  AND TABLE_NAME = ? 
                  AND COLUMN_NAME IN ('id', 'order_id', 'order_item_id', 'file_size')
            ", [$tableName]);

            $needsAlter = false;
            foreach ($cols as $col) {
                if (!str_contains(strtolower((string)$col->COLUMN_TYPE), 'unsigned')) {
                    $needsAlter = true;
                    break;
                }
            }

            if (!$needsAlter) {
                return;
            }

            $negCheck = $this->db->selectOne("
                SELECT 
                    SUM(CASE WHEN `id` < 0 THEN 1 ELSE 0 END) as neg_id,
                    SUM(CASE WHEN `order_id` < 0 THEN 1 ELSE 0 END) as neg_order,
                    SUM(CASE WHEN `order_item_id` IS NOT NULL AND `order_item_id` < 0 THEN 1 ELSE 0 END) as neg_item,
                    SUM(CASE WHEN `file_size` < 0 THEN 1 ELSE 0 END) as neg_size
                FROM `favorite_digital_order_deliverables`
            ");

            if ($negCheck && ($negCheck->neg_id > 0 || $negCheck->neg_order > 0 || $negCheck->neg_item > 0 || $negCheck->neg_size > 0)) {
                return;
            }

            $this->db->execute("
                ALTER TABLE `favorite_digital_order_deliverables`
                MODIFY `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                MODIFY `order_id` BIGINT UNSIGNED NOT NULL,
                MODIFY `order_item_id` BIGINT UNSIGNED NULL,
                MODIFY `file_size` BIGINT UNSIGNED NOT NULL DEFAULT 0
            ");
        } catch (\Throwable) {
        }
    }

    protected function isSqlite(): bool
    {
        try {
            $driver = $this->db->getConnection()->getAttribute(\PDO::ATTR_DRIVER_NAME);
            return strtolower((string)$driver) === 'sqlite';
        } catch (\Throwable) {
            return false;
        }
    }

    protected function createIndexIfNotExists(string $table, string $indexName, string $columns, bool $unique = false): void
    {
        if ($this->isSqlite()) {
            $uniqSql = $unique ? 'UNIQUE ' : '';
            $this->db->execute("CREATE {$uniqSql}INDEX IF NOT EXISTS `{$indexName}` ON `{$table}` ({$columns})");
            return;
        }

        try {
            $existing = $this->db->select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
            if (empty($existing)) {
                $uniqueClause = $unique ? 'UNIQUE ' : '';
                $this->db->execute("ALTER TABLE `{$table}` ADD {$uniqueClause}INDEX `{$indexName}` ({$columns})");
            }
        } catch (\Throwable) {
            try {
                $uniqueClause = $unique ? 'UNIQUE ' : '';
                $this->db->execute("ALTER TABLE `{$table}` ADD {$uniqueClause}INDEX `{$indexName}` ({$columns})");
            } catch (\Throwable) {
            }
        }
    }
}
