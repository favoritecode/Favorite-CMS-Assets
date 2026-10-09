-- Favorite Shop fractional inventory and immutable order snapshots.
-- Run once after migration 003. Back up the database before applying.
ALTER TABLE favorite_shop_products
    MODIFY COLUMN stock_quantity DECIMAL(14,3) NOT NULL DEFAULT 0;
ALTER TABLE favorite_shop_product_variants
    MODIFY COLUMN stock_quantity DECIMAL(14,3) NOT NULL DEFAULT 0;
ALTER TABLE favorite_shop_inventory_movements
    MODIFY COLUMN quantity_delta DECIMAL(14,3) NOT NULL,
    MODIFY COLUMN quantity_after DECIMAL(14,3) NOT NULL;
ALTER TABLE favorite_shop_cart_items
    MODIFY COLUMN quantity DECIMAL(14,3) NOT NULL DEFAULT 1;
ALTER TABLE favorite_shop_order_items
    MODIFY COLUMN quantity DECIMAL(14,3) NOT NULL,
    ADD COLUMN unit_snapshot VARCHAR(20) NOT NULL DEFAULT 'piece' AFTER variant_snapshot_json,
    ADD COLUMN unit_quantity_snapshot DECIMAL(14,6) NOT NULL DEFAULT 1 AFTER unit_snapshot,
    ADD COLUMN unit_label_snapshot VARCHAR(80) NULL AFTER unit_quantity_snapshot,
    ADD COLUMN shipping_weight_grams_snapshot INT NULL AFTER unit_label_snapshot;
ALTER TABLE favorite_shop_orders
    ADD COLUMN shipping_zone_snapshot VARCHAR(190) NULL AFTER shipping_cents,
    ADD COLUMN coupon_code_snapshot VARCHAR(100) NULL AFTER shipping_zone_snapshot,
    ADD COLUMN discount_details_json JSON NULL AFTER coupon_code_snapshot;
