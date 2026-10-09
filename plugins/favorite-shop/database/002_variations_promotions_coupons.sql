-- Favorite Shop v1.0.0 additive extension: variation pricing, offers and coupons.
-- Keep these tables plugin-owned and register names with the CMS prefix system.
-- Product variation prices are explicit; bulk price changes are handled by admin tooling.
CREATE TABLE IF NOT EXISTS favorite_shop_product_variants (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  product_id BIGINT UNSIGNED NOT NULL,
  sku VARCHAR(100) NULL UNIQUE,
  color_name VARCHAR(100) NULL,
  color_code VARCHAR(20) NULL,
  size_name VARCHAR(100) NULL,
  option_values_json JSON NULL,
  price_cents BIGINT NOT NULL DEFAULT 0,
  sale_price_cents BIGINT NULL,
  stock_quantity INT NOT NULL DEFAULT 0,
  image_url VARCHAR(2048) NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_shop_variant_product (product_id),
  INDEX idx_shop_variant_options (color_name, size_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS favorite_shop_offers (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(190) NOT NULL,
  offer_type VARCHAR(30) NOT NULL,
  minimum_subtotal_cents BIGINT NOT NULL DEFAULT 0,
  discount_cents BIGINT NULL,
  discount_percent DECIMAL(6,3) NULL,
  maximum_discount_cents BIGINT NULL,
  starts_at DATETIME NULL,
  ends_at DATETIME NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'draft',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_shop_offer_status_period (status, starts_at, ends_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS favorite_shop_coupons (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(80) NOT NULL UNIQUE,
  discount_type VARCHAR(20) NOT NULL,
  discount_value DECIMAL(12,2) NOT NULL DEFAULT 0,
  minimum_subtotal_cents BIGINT NOT NULL DEFAULT 0,
  maximum_discount_cents BIGINT NULL,
  free_shipping TINYINT(1) NOT NULL DEFAULT 0,
  usage_limit INT NULL,
  per_customer_limit INT NULL,
  starts_at DATETIME NULL,
  ends_at DATETIME NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'draft',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_shop_coupon_status_period (status, starts_at, ends_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS favorite_shop_coupon_redemptions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  coupon_id BIGINT UNSIGNED NOT NULL,
  order_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NULL,
  coupon_code_snapshot VARCHAR(80) NOT NULL,
  discount_cents BIGINT NOT NULL DEFAULT 0,
  shipping_discount_cents BIGINT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_shop_coupon_order (coupon_id, order_id),
  INDEX idx_shop_coupon_redemption_customer (coupon_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
