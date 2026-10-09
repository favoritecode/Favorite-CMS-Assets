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
    MODIFY COLUMN quantity DECIMAL(14,3) NOT NULL;
-- Snapshot columns are added idempotently by the plugin Installer.
