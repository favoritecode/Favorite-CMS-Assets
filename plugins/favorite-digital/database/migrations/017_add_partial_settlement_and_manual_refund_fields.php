<?php

declare(strict_types=1);

use FavoriteCMS\Core\Database;

/**
 * Favorite Digital — Migration 017: Partial Settlement and Manual Refund Fields
 *
 * Adds retained and refunded amounts to orders, and manual refund tracking fields to refunds.
 */
class AddPartialSettlementAndManualRefundFields
{
    protected Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function up(): void
    {
        // 1. Add retained_amount and refunded_amount to favorite_digital_orders
        if (!$this->columnExists('favorite_digital_orders', 'retained_amount')) {
            $this->db->execute("ALTER TABLE `favorite_digital_orders` ADD COLUMN `retained_amount` DECIMAL(14, 2) NULL DEFAULT NULL");
        }

        if (!$this->columnExists('favorite_digital_orders', 'refunded_amount')) {
            $this->db->execute("ALTER TABLE `favorite_digital_orders` ADD COLUMN `refunded_amount` DECIMAL(14, 2) NOT NULL DEFAULT 0.00");
        }

        // 2. Add manual refund tracking fields to favorite_digital_refunds
        if (!$this->columnExists('favorite_digital_refunds', 'refund_method')) {
            $this->db->execute("ALTER TABLE `favorite_digital_refunds` ADD COLUMN `refund_method` VARCHAR(32) NOT NULL DEFAULT 'wallet'");
        }

        if (!$this->columnExists('favorite_digital_refunds', 'reference')) {
            $this->db->execute("ALTER TABLE `favorite_digital_refunds` ADD COLUMN `reference` VARCHAR(128) NULL DEFAULT NULL");
        }

        if (!$this->columnExists('favorite_digital_refunds', 'processed_by')) {
            $this->db->execute("ALTER TABLE `favorite_digital_refunds` ADD COLUMN `processed_by` BIGINT NULL DEFAULT NULL");
        }

        if (!$this->columnExists('favorite_digital_refunds', 'refund_type')) {
            $this->db->execute("ALTER TABLE `favorite_digital_refunds` ADD COLUMN `refund_type` VARCHAR(32) NOT NULL DEFAULT 'full'");
        }
    }

    public function down(): void
    {
        if ($this->isSqlite()) {
            try {
                if ($this->columnExists('favorite_digital_orders', 'retained_amount')) {
                    $this->db->execute("ALTER TABLE `favorite_digital_orders` DROP COLUMN `retained_amount`");
                }
                if ($this->columnExists('favorite_digital_orders', 'refunded_amount')) {
                    $this->db->execute("ALTER TABLE `favorite_digital_orders` DROP COLUMN `refunded_amount`");
                }
                if ($this->columnExists('favorite_digital_refunds', 'refund_method')) {
                    $this->db->execute("ALTER TABLE `favorite_digital_refunds` DROP COLUMN `refund_method`");
                }
                if ($this->columnExists('favorite_digital_refunds', 'reference')) {
                    $this->db->execute("ALTER TABLE `favorite_digital_refunds` DROP COLUMN `reference`");
                }
                if ($this->columnExists('favorite_digital_refunds', 'processed_by')) {
                    $this->db->execute("ALTER TABLE `favorite_digital_refunds` DROP COLUMN `processed_by`");
                }
                if ($this->columnExists('favorite_digital_refunds', 'refund_type')) {
                    $this->db->execute("ALTER TABLE `favorite_digital_refunds` DROP COLUMN `refund_type`");
                }
            } catch (\Throwable) {
            }
            return;
        }

        try {
            if ($this->columnExists('favorite_digital_orders', 'retained_amount')) {
                $this->db->execute("ALTER TABLE `favorite_digital_orders` DROP COLUMN `retained_amount`");
            }
            if ($this->columnExists('favorite_digital_orders', 'refunded_amount')) {
                $this->db->execute("ALTER TABLE `favorite_digital_orders` DROP COLUMN `refunded_amount`");
            }
            if ($this->columnExists('favorite_digital_refunds', 'refund_method')) {
                $this->db->execute("ALTER TABLE `favorite_digital_refunds` DROP COLUMN `refund_method`");
            }
            if ($this->columnExists('favorite_digital_refunds', 'reference')) {
                $this->db->execute("ALTER TABLE `favorite_digital_refunds` DROP COLUMN `reference`");
            }
            if ($this->columnExists('favorite_digital_refunds', 'processed_by')) {
                $this->db->execute("ALTER TABLE `favorite_digital_refunds` DROP COLUMN `processed_by`");
            }
            if ($this->columnExists('favorite_digital_refunds', 'refund_type')) {
                $this->db->execute("ALTER TABLE `favorite_digital_refunds` DROP COLUMN `refund_type`");
            }
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

    protected function columnExists(string $table, string $column): bool
    {
        try {
            $this->db->select("SELECT `{$column}` FROM `{$table}` LIMIT 0");
            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}

