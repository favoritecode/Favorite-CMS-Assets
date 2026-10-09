-- Favorite Shop product units and optional shipping weight.
-- Money remains stored in integer minor units; weight is stored as grams.
-- This migration only alters Favorite Shop-owned tables.
ALTER TABLE favorite_shop_products
    ADD COLUMN unit_type VARCHAR(20) NOT NULL DEFAULT 'piece' AFTER product_type,
    ADD COLUMN unit_quantity DECIMAL(14,6) NOT NULL DEFAULT 1 AFTER unit_type,
    ADD COLUMN unit_label VARCHAR(80) NULL AFTER unit_quantity;

ALTER TABLE favorite_shop_product_variants
    ADD COLUMN unit_type VARCHAR(20) NULL AFTER option_values_json,
    ADD COLUMN unit_quantity DECIMAL(14,6) NULL AFTER unit_type,
    ADD COLUMN unit_label VARCHAR(80) NULL AFTER unit_quantity;
