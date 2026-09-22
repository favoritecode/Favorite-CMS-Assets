<?php

declare(strict_types=1);

use FavoriteCMS\Core\Database;

/**
 * Favorite Web Tools — Migration 003: Tools Registry Table
 */
class CreateFavoriteWebToolsTable
{
    protected Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
        if (method_exists($this->db, 'registerPrefixableTables')) {
            $this->db->registerPrefixableTables(['favorite_web_tools']);
        }
    }

    public function up(): void
    {
        $isSqlite = $this->isSqlite();
        $engine = $isSqlite ? '' : ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
        $pkBigint = $isSqlite ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'BIGINT AUTO_INCREMENT PRIMARY KEY';
        $updatedAt = $isSqlite
            ? 'DATETIME DEFAULT CURRENT_TIMESTAMP'
            : 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP';
        $longtextType = $isSqlite ? 'TEXT' : 'LONGTEXT';

        $this->db->execute("
            CREATE TABLE IF NOT EXISTS `favorite_web_tools` (
                `id`               {$pkBigint},
                `name`             VARCHAR(150) NOT NULL,
                `slug`             VARCHAR(150) NOT NULL UNIQUE,
                `description`      TEXT         NULL,
                `category_id`      BIGINT       NULL,
                `engine`           VARCHAR(32)  NOT NULL,
                `access_mode`      VARCHAR(32)  NOT NULL DEFAULT 'FREE',
                `status`           VARCHAR(32)  NOT NULL DEFAULT 'DRAFT',
                `input_schema`     {$longtextType} NULL,
                `output_schema`    {$longtextType} NULL,
                `configuration`    {$longtextType} NULL,
                `display_order`    INT          NOT NULL DEFAULT 0,
                `meta_title`       VARCHAR(255) NULL,
                `meta_description` TEXT         NULL,
                `icon`             VARCHAR(255) NULL,
                `created_at`       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
                `updated_at`       {$updatedAt}
            ){$engine};
        ");

        $this->createIndexIfNotExists('favorite_web_tools', 'idx_fwt_tool_slug', '`slug`', true);
        $this->createIndexIfNotExists('favorite_web_tools', 'idx_fwt_tool_category', '`category_id`');
        $this->createIndexIfNotExists('favorite_web_tools', 'idx_fwt_tool_engine', '`engine`');
        $this->createIndexIfNotExists('favorite_web_tools', 'idx_fwt_tool_access', '`access_mode`');
        $this->createIndexIfNotExists('favorite_web_tools', 'idx_fwt_tool_status', '`status`');
        $this->createIndexIfNotExists('favorite_web_tools', 'idx_fwt_tool_order', '`display_order`');
    }

    public function down(): void
    {
        $this->db->execute("DROP TABLE IF EXISTS `favorite_web_tools`");
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
            $uniqueClause = $unique ? 'UNIQUE' : '';
            $this->db->execute("CREATE {$uniqueClause} INDEX IF NOT EXISTS `{$indexName}` ON `{$table}` ({$columns})");
            return;
        }

        try {
            $existing = $this->db->select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
            if (empty($existing)) {
                $uniqueClause = $unique ? 'UNIQUE' : '';
                $this->db->execute("ALTER TABLE `{$table}` ADD {$uniqueClause} INDEX `{$indexName}` ({$columns})");
            }
        } catch (\Throwable) {
            try {
                $uniqueClause = $unique ? 'UNIQUE' : '';
                $this->db->execute("ALTER TABLE `{$table}` ADD {$uniqueClause} INDEX `{$indexName}` ({$columns})");
            } catch (\Throwable) {
            }
        }
    }
}

