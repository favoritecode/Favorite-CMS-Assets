<?php
declare(strict_types=1);
namespace FavoriteCMS\Shop;
use FavoriteCMS\Core\Application;
use FavoriteCMS\Core\Database;
/** Additive, idempotent plugin-owned schema; never alters Favorite Digital tables. */
final class Installer
{
    private static bool $ran = false;
    public static function reset(): void { self::$ran = false; }
    public static function register(Application $app): void {
        if (self::$ran || !$app->has(Database::class)) return;
        $db = $app->make(Database::class);
        if (!method_exists($db, 'getConnection')) return;
        $pdo = $db->getConnection();
        if (!$pdo instanceof \PDO) return;
        foreach (self::statements() as $sql) $pdo->exec($sql);
        self::ensureColumn($pdo, 'favorite_shop_products', 'low_stock_threshold', 'DECIMAL(14,3) NOT NULL DEFAULT 0');
        self::ensureColumn($pdo, 'favorite_shop_products', 'allow_backorder', 'TINYINT(1) NOT NULL DEFAULT 0');
        self::ensureColumn($pdo, 'favorite_shop_product_variants', 'low_stock_threshold', 'DECIMAL(14,3) NOT NULL DEFAULT 0');
        self::ensureColumn($pdo, 'favorite_shop_product_variants', 'allow_backorder', 'TINYINT(1) NOT NULL DEFAULT 0');
        self::ensureColumn($pdo, 'favorite_shop_products', 'unit_type', "VARCHAR(20) NOT NULL DEFAULT 'piece'");
        self::ensureColumn($pdo, 'favorite_shop_products', 'unit_quantity', 'DECIMAL(14,6) NOT NULL DEFAULT 1');
        self::ensureColumn($pdo, 'favorite_shop_products', 'unit_label', 'VARCHAR(80) NULL');
        self::ensureColumn($pdo, 'favorite_shop_product_variants', 'unit_type', 'VARCHAR(20) NULL');
        self::ensureColumn($pdo, 'favorite_shop_product_variants', 'unit_quantity', 'DECIMAL(14,6) NULL');
        self::ensureColumn($pdo, 'favorite_shop_product_variants', 'unit_label', 'VARCHAR(80) NULL');
        self::ensureColumn($pdo, 'favorite_shop_order_items', 'unit_snapshot', "VARCHAR(20) NOT NULL DEFAULT 'piece'");
        self::ensureColumn($pdo, 'favorite_shop_order_items', 'unit_quantity_snapshot', 'DECIMAL(14,6) NOT NULL DEFAULT 1');
        self::ensureColumn($pdo, 'favorite_shop_order_items', 'unit_label_snapshot', 'VARCHAR(80) NULL');
        self::ensureColumn($pdo, 'favorite_shop_order_items', 'shipping_weight_grams_snapshot', 'INT NULL');
        self::ensureColumn($pdo, 'favorite_shop_orders', 'shipping_zone_snapshot', 'VARCHAR(190) NULL');
        self::ensureColumn($pdo, 'favorite_shop_orders', 'coupon_code_snapshot', 'VARCHAR(100) NULL');
        self::ensureColumn($pdo, 'favorite_shop_orders', 'discount_details_json', 'JSON NULL');
        // DECIMAL quantities are required for weight-based inventory (e.g. 0.5 kg).
        foreach ([
            'favorite_shop_products' => ['stock_quantity'],
            'favorite_shop_product_variants' => ['stock_quantity'],
            'favorite_shop_inventory_movements' => ['quantity_delta', 'quantity_after'],
            'favorite_shop_cart_items' => ['quantity'],
            'favorite_shop_order_items' => ['quantity'],
        ] as $table => $columns) {
            foreach ($columns as $column) {
                $pdo->exec('ALTER TABLE `' . $table . '` MODIFY COLUMN `' . $column . '` DECIMAL(14,3) NOT NULL' . ($column === 'quantity' && $table === 'favorite_shop_cart_items' ? ' DEFAULT 1' : ($column === 'stock_quantity' ? ' DEFAULT 0' : '')));
            }
        }
        self::$ran = true;
    }
    private static function ensureColumn(\PDO $pdo, string $table, string $column, string $definition): void
    {
        // Identifiers are hard-coded by the plugin; definitions are static literals.
        $stmt = $pdo->prepare('SHOW COLUMNS FROM `' . $table . '` LIKE ?');
        $stmt->execute([$column]);
        if ($stmt->fetch(\PDO::FETCH_ASSOC)) return;
        $pdo->exec('ALTER TABLE `' . $table . '` ADD COLUMN `' . $column . '` ' . $definition);
    }
    public static function statements(): array {
        return [
"CREATE TABLE IF NOT EXISTS favorite_shop_products (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, sku VARCHAR(100) NULL UNIQUE, slug VARCHAR(190) NOT NULL UNIQUE, name VARCHAR(255) NOT NULL, description MEDIUMTEXT NULL, short_description TEXT NULL, product_type VARCHAR(20) NOT NULL DEFAULT 'simple', unit_type VARCHAR(20) NOT NULL DEFAULT 'piece', unit_quantity DECIMAL(14,6) NOT NULL DEFAULT 1, unit_label VARCHAR(80) NULL, status VARCHAR(20) NOT NULL DEFAULT 'draft', price_cents BIGINT NOT NULL DEFAULT 0, sale_price_cents BIGINT NULL, cost_cents BIGINT NULL, stock_quantity DECIMAL(14,3) NOT NULL DEFAULT 0, stock_status VARCHAR(20) NOT NULL DEFAULT 'in_stock', manage_stock TINYINT(1) NOT NULL DEFAULT 1, low_stock_threshold DECIMAL(14,3) NOT NULL DEFAULT 0, allow_backorder TINYINT(1) NOT NULL DEFAULT 0, weight_grams INT NULL, length_mm INT NULL, width_mm INT NULL, height_mm INT NULL, cover_image_url VARCHAR(2048) NULL, gallery_json JSON NULL, metadata_json JSON NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, INDEX idx_shop_products_status (status), INDEX idx_shop_products_name (name)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
"CREATE TABLE IF NOT EXISTS favorite_shop_product_categories (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(190) NOT NULL, slug VARCHAR(190) NOT NULL UNIQUE, description TEXT NULL, parent_id BIGINT UNSIGNED NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX idx_shop_category_parent (parent_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
"CREATE TABLE IF NOT EXISTS favorite_shop_product_category_map (product_id BIGINT UNSIGNED NOT NULL, category_id BIGINT UNSIGNED NOT NULL, PRIMARY KEY(product_id,category_id), INDEX idx_shop_category_map_category(category_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
"CREATE TABLE IF NOT EXISTS favorite_shop_product_variants (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, product_id BIGINT UNSIGNED NOT NULL, sku VARCHAR(100) NULL UNIQUE, option_values_json JSON NOT NULL, unit_type VARCHAR(20) NULL, unit_quantity DECIMAL(14,6) NULL, unit_label VARCHAR(80) NULL, price_cents BIGINT NULL, sale_price_cents BIGINT NULL, stock_quantity DECIMAL(14,3) NOT NULL DEFAULT 0, low_stock_threshold DECIMAL(14,3) NOT NULL DEFAULT 0, allow_backorder TINYINT(1) NOT NULL DEFAULT 0, weight_grams INT NULL, image_url VARCHAR(2048) NULL, status VARCHAR(20) NOT NULL DEFAULT 'active', created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX idx_shop_variant_product(product_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
"CREATE TABLE IF NOT EXISTS favorite_shop_inventory_movements (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, product_id BIGINT UNSIGNED NOT NULL, variant_id BIGINT UNSIGNED NULL, movement_type VARCHAR(30) NOT NULL, quantity_delta DECIMAL(14,3) NOT NULL, quantity_after DECIMAL(14,3) NOT NULL, reference_type VARCHAR(40) NULL, reference_id BIGINT UNSIGNED NULL, note VARCHAR(500) NULL, actor_user_id BIGINT UNSIGNED NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX idx_shop_inventory_product(product_id,created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
"CREATE TABLE IF NOT EXISTS favorite_shop_carts (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NULL, session_key VARCHAR(128) NULL, currency CHAR(3) NOT NULL DEFAULT 'BDT', created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, INDEX idx_shop_cart_user(user_id), INDEX idx_shop_cart_session(session_key)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
"CREATE TABLE IF NOT EXISTS favorite_shop_cart_items (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, cart_id BIGINT UNSIGNED NOT NULL, product_id BIGINT UNSIGNED NOT NULL, variant_id BIGINT UNSIGNED NULL, quantity DECIMAL(14,3) NOT NULL DEFAULT 1, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX idx_shop_cart_item_cart(cart_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
"CREATE TABLE IF NOT EXISTS favorite_shop_orders (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, order_number VARCHAR(40) NOT NULL UNIQUE, user_id BIGINT UNSIGNED NULL, email VARCHAR(254) NULL, phone VARCHAR(40) NOT NULL, status VARCHAR(30) NOT NULL DEFAULT 'pending', payment_method VARCHAR(40) NOT NULL DEFAULT 'cash_on_delivery', payment_status VARCHAR(30) NOT NULL DEFAULT 'unpaid', currency CHAR(3) NOT NULL DEFAULT 'BDT', subtotal_cents BIGINT NOT NULL DEFAULT 0, discount_cents BIGINT NOT NULL DEFAULT 0, shipping_cents BIGINT NOT NULL DEFAULT 0, shipping_zone_snapshot VARCHAR(190) NULL, coupon_code_snapshot VARCHAR(100) NULL, discount_details_json JSON NULL, tax_cents BIGINT NOT NULL DEFAULT 0, total_cents BIGINT NOT NULL DEFAULT 0, customer_note TEXT NULL, placed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, INDEX idx_shop_order_status(status), INDEX idx_shop_order_user(user_id), INDEX idx_shop_order_payment_status(payment_status)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
"CREATE TABLE IF NOT EXISTS favorite_shop_order_items (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, order_id BIGINT UNSIGNED NOT NULL, product_id BIGINT UNSIGNED NULL, variant_id BIGINT UNSIGNED NULL, sku_snapshot VARCHAR(100) NULL, name_snapshot VARCHAR(255) NOT NULL, variant_snapshot_json JSON NULL, unit_price_cents BIGINT NOT NULL, quantity INT NOT NULL, line_total_cents BIGINT NOT NULL, INDEX idx_shop_order_items_order(order_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
"CREATE TABLE IF NOT EXISTS favorite_shop_order_addresses (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, order_id BIGINT UNSIGNED NOT NULL, address_type VARCHAR(20) NOT NULL DEFAULT 'shipping', recipient_name VARCHAR(190) NOT NULL, phone VARCHAR(40) NOT NULL, address_line1 VARCHAR(255) NOT NULL, address_line2 VARCHAR(255) NULL, area VARCHAR(120) NULL, city VARCHAR(120) NOT NULL, district VARCHAR(120) NULL, postal_code VARCHAR(30) NULL, country_code CHAR(2) NOT NULL DEFAULT 'BD', created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX idx_shop_address_order(order_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
"CREATE TABLE IF NOT EXISTS favorite_shop_shipments (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, order_id BIGINT UNSIGNED NOT NULL, carrier VARCHAR(120) NULL, tracking_number VARCHAR(190) NULL, tracking_url VARCHAR(2048) NULL, status VARCHAR(30) NOT NULL DEFAULT 'pending', shipped_at DATETIME NULL, delivered_at DATETIME NULL, cod_collected_cents BIGINT NOT NULL DEFAULT 0, notes TEXT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, INDEX idx_shop_shipment_order(order_id), INDEX idx_shop_shipment_tracking(tracking_number)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
"CREATE TABLE IF NOT EXISTS favorite_shop_order_events (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, order_id BIGINT UNSIGNED NOT NULL, event_key VARCHAR(80) NOT NULL, from_status VARCHAR(30) NULL, to_status VARCHAR(30) NULL, note TEXT NULL, actor_user_id BIGINT UNSIGNED NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX idx_shop_event_order(order_id,created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
"CREATE TABLE IF NOT EXISTS favorite_shop_offers (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, title VARCHAR(190) NOT NULL, description TEXT NULL, discount_type VARCHAR(20) NOT NULL DEFAULT 'sale_price', discount_value BIGINT NOT NULL DEFAULT 0, starts_at DATETIME NOT NULL, ends_at DATETIME NOT NULL, status VARCHAR(20) NOT NULL DEFAULT 'scheduled', product_ids_json JSON NULL, category_ids_json JSON NULL, usage_limit INT UNSIGNED NULL, usage_count INT UNSIGNED NOT NULL DEFAULT 0, priority INT NOT NULL DEFAULT 0, stackable TINYINT(1) NOT NULL DEFAULT 0, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, INDEX idx_shop_offer_schedule(status,starts_at,ends_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
"CREATE TABLE IF NOT EXISTS favorite_shop_coupons (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, code VARCHAR(100) NOT NULL UNIQUE, description TEXT NULL, discount_type VARCHAR(20) NOT NULL, discount_value BIGINT NOT NULL DEFAULT 0, min_subtotal_cents BIGINT NOT NULL DEFAULT 0, max_discount_cents BIGINT NULL, starts_at DATETIME NOT NULL, ends_at DATETIME NOT NULL, status VARCHAR(20) NOT NULL DEFAULT 'active', usage_limit INT UNSIGNED NULL, usage_count INT UNSIGNED NOT NULL DEFAULT 0, per_customer_limit INT UNSIGNED NULL, product_ids_json JSON NULL, category_ids_json JSON NULL, free_shipping TINYINT(1) NOT NULL DEFAULT 0, stackable TINYINT(1) NOT NULL DEFAULT 0, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, INDEX idx_shop_coupon_schedule(status,starts_at,ends_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
"CREATE TABLE IF NOT EXISTS favorite_shop_coupon_redemptions (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, coupon_id BIGINT UNSIGNED NOT NULL, order_id BIGINT UNSIGNED NOT NULL, customer_key VARCHAR(190) NOT NULL, discount_cents BIGINT NOT NULL DEFAULT 0, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uq_shop_coupon_order(coupon_id,order_id), INDEX idx_shop_coupon_customer(coupon_id,customer_key)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
"CREATE TABLE IF NOT EXISTS favorite_shop_settings (setting_key VARCHAR(190) NOT NULL PRIMARY KEY, setting_value MEDIUMTEXT NULL, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        ];
    }
}
