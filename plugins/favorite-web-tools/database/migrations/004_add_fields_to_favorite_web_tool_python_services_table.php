<?php

declare(strict_types=1);

use FavoriteCMS\Core\Database;

/**
 * Favorite Web Tools — Migration 004: Add Extended Fields to Python Services Table
 *
 * Adds slug, description, default_endpoint_path, and http_method to favorite_web_tool_python_services.
 * Supports table prefixes, fresh installations, existing installations, and upgrades.
 */
class AddFieldsToFavoriteWebToolPythonServicesTable
{
    protected Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
        if (method_exists($this->db, 'registerPrefixableTables')) {
            $this->db->registerPrefixableTables(['favorite_web_tool_python_services']);
        }
    }

    public function up(): void
    {
        $isSqlite = $this->isSqlite();

        // 1. Add slug column
        if (!$this->columnExists('favorite_web_tool_python_services', 'slug')) {
            $this->db->execute("ALTER TABLE `favorite_web_tool_python_services` ADD COLUMN `slug` VARCHAR(100) NULL");
        }

        // 2. Add description column
        if (!$this->columnExists('favorite_web_tool_python_services', 'description')) {
            $this->db->execute("ALTER TABLE `favorite_web_tool_python_services` ADD COLUMN `description` TEXT NULL");
        }

        // 3. Add default_endpoint_path column
        if (!$this->columnExists('favorite_web_tool_python_services', 'default_endpoint_path')) {
            $this->db->execute("ALTER TABLE `favorite_web_tool_python_services` ADD COLUMN `default_endpoint_path` VARCHAR(255) NULL");
        }

        // 4. Add http_method column
        if (!$this->columnExists('favorite_web_tool_python_services', 'http_method')) {
            $this->db->execute("ALTER TABLE `favorite_web_tool_python_services` ADD COLUMN `http_method` VARCHAR(16) NOT NULL DEFAULT 'GET'");
        }

        // 5. Index on slug
        $this->createIndexIfNotExists('favorite_web_tool_python_services', 'idx_fwt_ps_slug', '`slug`');

        // 6. Backfill slugs for existing rows that lack one
        $this->backfillSlugs();
    }

    public function down(): void
    {
        if ($this->isSqlite()) {
            return; // SQLite does not cleanly support dropping multiple columns in older versions
        }

        foreach (['http_method', 'default_endpoint_path', 'description', 'slug'] as $col) {
            if ($this->columnExists('favorite_web_tool_python_services', $col)) {
                try {
                    $this->db->execute("ALTER TABLE `favorite_web_tool_python_services` DROP COLUMN `{$col}`");
                } catch (\Throwable) {
                }
            }
        }
    }

    protected function backfillSlugs(): void
    {
        try {
            $rows = $this->db->select("SELECT `id`, `name`, `slug` FROM `favorite_web_tool_python_services` WHERE `slug` IS NULL OR `slug` = ''");
            foreach ($rows as $row) {
                $id = is_object($row) ? (int)$row->id : (int)$row['id'];
                $name = is_object($row) ? (string)$row->name : (string)$row['name'];
                $slug = strtolower(trim((string)preg_replace('/[^a-zA-Z0-9]+/', '-', $name), '-'));
                if ($slug === '') {
                    $slug = 'service-' . $id;
                }
                $this->db->execute("UPDATE `favorite_web_tool_python_services` SET `slug` = ? WHERE `id` = ?", [$slug, $id]);
            }
        } catch (\Throwable) {
        }
    }

    protected function columnExists(string $table, string $column): bool
    {
        if ($this->isSqlite()) {
            try {
                $cols = $this->db->select("PRAGMA table_info(`{$table}`)");
                foreach ($cols as $col) {
                    $colName = is_object($col) ? ($col->name ?? '') : ($col['name'] ?? '');
                    if (strcasecmp((string)$colName, $column) === 0) {
                        return true;
                    }
                }
            } catch (\Throwable) {
            }
            return false;
        }

        try {
            $existing = $this->db->select("SHOW COLUMNS FROM `{$table}` LIKE ?", [$column]);
            return !empty($existing);
        } catch (\Throwable) {
            return false;
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
            $uniqueClause = $unique ? 'UNIQUE' : '';
            try {
                $this->db->execute("CREATE {$uniqueClause} INDEX IF NOT EXISTS `{$indexName}` ON `{$table}` ({$columns})");
            } catch (\Throwable) {
            }
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

