<?php

declare(strict_types=1);

use FavoriteCMS\Core\Database;

/**
 * Favorite Web Tools — Migration 005: Universal Frontend Designs Table
 */
class CreateFavoriteWebToolFrontendDesignsTable
{
    protected Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
        if (method_exists($this->db, 'registerPrefixableTables')) {
            $this->db->registerPrefixableTables([
                'favorite_web_tool_frontend_designs',
                'favorite_web_tools',
            ]);
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

        // 1. Create frontend designs table
        $this->db->execute("
            CREATE TABLE IF NOT EXISTS `favorite_web_tool_frontend_designs` (
                `id`               {$pkBigint},
                `name`             VARCHAR(150) NOT NULL,
                `slug`             VARCHAR(150) NOT NULL UNIQUE,
                `description`      TEXT         NULL,
                `version`          VARCHAR(32)  NOT NULL DEFAULT '1.0.0',
                `status`           VARCHAR(32)  NOT NULL DEFAULT 'active',
                `template_markup`  {$longtextType} NOT NULL,
                `css_content`      {$longtextType} NULL,
                `js_content`       {$longtextType} NULL,
                `bindings_schema`  {$longtextType} NULL,
                `preview_data`     {$longtextType} NULL,
                `normalizer_key`   VARCHAR(64)  NOT NULL DEFAULT 'default',
                `is_builtin`       TINYINT      NOT NULL DEFAULT 0,
                `created_at`       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
                `updated_at`       {$updatedAt}
            ){$engine};
        ");

        $this->createIndexIfNotExists('favorite_web_tool_frontend_designs', 'idx_fwtd_slug', '`slug`', true);
        $this->createIndexIfNotExists('favorite_web_tool_frontend_designs', 'idx_fwtd_status', '`status`');
        $this->createIndexIfNotExists('favorite_web_tool_frontend_designs', 'idx_fwtd_builtin', '`is_builtin`');

        // 2. Add frontend_design_slug column to favorite_web_tools if not present
        $this->addColumnIfNotExists('favorite_web_tools', 'frontend_design_slug', 'VARCHAR(150) NULL AFTER `configuration`');
        $this->createIndexIfNotExists('favorite_web_tools', 'idx_fwt_design_slug', '`frontend_design_slug`');
    }

    public function down(): void
    {
        $this->db->execute("DROP TABLE IF EXISTS `favorite_web_tool_frontend_designs`");
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

    protected function addColumnIfNotExists(string $table, string $column, string $definition): void
    {
        if ($this->isSqlite()) {
            $cols = $this->db->select("PRAGMA table_info(`{$table}`)");
            foreach ($cols as $col) {
                $colName = is_object($col) ? ($col->name ?? '') : ($col['name'] ?? '');
                if (strtolower((string)$colName) === strtolower($column)) {
                    return;
                }
            }
            // Strip MySQL specific modifiers for SQLite
            $cleanDef = preg_replace('/\s+AFTER\s+`?[a-zA-Z0-9_]+`?/i', '', $definition);
            $this->db->execute("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$cleanDef}");
            return;
        }

        try {
            $existing = $this->db->select("SHOW COLUMNS FROM `{$table}` LIKE ?", [$column]);
            if (empty($existing)) {
                $this->db->execute("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
            }
        } catch (\Throwable) {
            try {
                $this->db->execute("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
            } catch (\Throwable) {
            }
        }
    }
}
