<?php

declare(strict_types=1);

use FavoriteCMS\Core\Database;

/**
 * Favorite Web Tools — Migration 002: Python API Services Table
 */
class CreateFavoriteWebToolPythonServicesTable
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
        $engine = $isSqlite ? '' : ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
        $pkBigint = $isSqlite ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'BIGINT AUTO_INCREMENT PRIMARY KEY';
        $updatedAt = $isSqlite
            ? 'DATETIME DEFAULT CURRENT_TIMESTAMP'
            : 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP';

        $this->db->execute("
            CREATE TABLE IF NOT EXISTS `favorite_web_tool_python_services` (
                `id`         {$pkBigint},
                `name`       VARCHAR(100) NOT NULL,
                `base_url`   VARCHAR(255) NOT NULL,
                `auth_type`  VARCHAR(32)  NOT NULL DEFAULT 'none',
                `api_key`    VARCHAR(255) NULL,
                `timeout`    INT          NOT NULL DEFAULT 30,
                `status`     VARCHAR(32)  NOT NULL DEFAULT 'active',
                `created_at` TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
                `updated_at` {$updatedAt}
            ){$engine};
        ");

        $this->createIndexIfNotExists('favorite_web_tool_python_services', 'idx_fwt_ps_status', '`status`');
    }

    public function down(): void
    {
        $this->db->execute("DROP TABLE IF EXISTS `favorite_web_tool_python_services`");
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

