<?php
declare(strict_types=1);

namespace FavoriteCMS\Shop\Domain;

/** Inventory status rules; backorders are explicit and never inferred from zero stock. */
final class StockStatus
{
    public static function resolve(mixed $quantity, bool $manageStock = true, bool $allowBackorder = false, mixed $lowStockThreshold = 0): string
    {
        if (!is_numeric($quantity) || !is_finite((float)$quantity) || (float)$quantity < 0) {
            throw new \InvalidArgumentException('Stock quantity must be a finite non-negative number.');
        }
        if (!is_numeric($lowStockThreshold) || (float)$lowStockThreshold < 0) {
            throw new \InvalidArgumentException('Low-stock threshold must be non-negative.');
        }
        if (!$manageStock) return 'in_stock';
        if ((float)$quantity <= 0) return $allowBackorder ? 'on_backorder' : 'out_of_stock';
        if ((float)$quantity <= (float)$lowStockThreshold) return 'low_stock';
        return 'in_stock';
    }

    public static function canFulfil(mixed $available, mixed $requested, bool $manageStock = true, bool $allowBackorder = false): bool
    {
        foreach ([$available, $requested] as $value) {
            if (!is_numeric($value) || !is_finite((float)$value) || (float)$value < 0) return false;
        }
        if ((float)$requested <= 0) return false;
        return !$manageStock || $allowBackorder || (float)$available >= (float)$requested;
    }

    private function __construct() {}
}
