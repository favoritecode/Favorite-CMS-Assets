<?php
declare(strict_types=1);

namespace FavoriteCMS\Shop\Domain;

/**
 * Deterministic cart-level promotion and coupon calculator.
 * All monetary values are integer minor units (for BDT, poisha).
 * This class does not persist usage counters or trust client-submitted prices.
 */
final class PromotionEngine
{
    /**
     * @param array<int,array{product_id?:int,quantity:int|float,unit_price_cents:int,category_ids?:array,labels?:array}> $items
     * @return array{items:array,discount_cents:int,shipping_discount_cents:int,applied:array}
     */
    public static function applyOffers(array $items, array $offers, int $shippingCents, ?\DateTimeImmutable $now = null): array
    {
        if ($shippingCents < 0) throw new \InvalidArgumentException('Shipping cannot be negative.');
        $now = $now ?? new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $result = array_values($items);
        $discountTotal = 0;
        $shippingDiscount = 0;
        $applied = [];
        foreach ($result as $index => &$item) {
            $qty = (float)($item['quantity'] ?? 0);
            $unit = (int)($item['unit_price_cents'] ?? -1);
            if ($qty <= 0 || $unit < 0) throw new \InvalidArgumentException('Cart line quantity and price must be valid.');
            $categories = (array)($item['category_ids'] ?? []);
            $labels = (array)($item['labels'] ?? []);
            $best = ['discount'=>0,'id'=>null,'priority'=>PHP_INT_MIN,'type'=>null];
            foreach ($offers as $offer) {
                if (OfferSchedule::state((string)($offer['status'] ?? 'scheduled'), (string)($offer['starts_at'] ?? ''), (string)($offer['ends_at'] ?? ''), $now) !== 'active') continue;
                if (isset($offer['usage_limit']) && $offer['usage_limit'] !== null && (int)($offer['usage_count'] ?? 0) >= (int)$offer['usage_limit']) continue;
                $catIds = json_decode((string)($offer['category_ids_json'] ?? '[]'), true) ?: [];
                $scopeLabels = json_decode((string)($offer['labels_json'] ?? '[]'), true) ?: [];
                if (!OfferPricing::matchesScope($categories, $labels, $catIds, $scopeLabels)) continue;
                $type = (string)($offer['discount_type'] ?? '');
                $value = (int)($offer['discount_value'] ?? 0);
                $lineSubtotal = (int)floor($qty * $unit);
                $discount = 0;
                if ($type === 'percent' && $value >= 1 && $value <= 100) $discount = (int)floor($lineSubtotal * $value / 100);
                elseif ($type === 'fixed' && $value > 0) $discount = min($lineSubtotal, $value);
                elseif ($type === 'sale_price' && $value >= 0) $discount = max(0, $lineSubtotal - (int)floor($qty * min($unit, $value)));
                elseif ($type === 'buy_x_get_y') {
                    $buy = max(1, (int)($offer['buy_quantity'] ?? 1));
                    $get = max(1, (int)($offer['get_quantity'] ?? 1));
                    $freeUnits = floor($qty / ($buy + $get)) * $get;
                    $discount = min($lineSubtotal, (int)floor($freeUnits * $unit));
                } elseif ($type === 'bundle_price') {
                    $bundleQty = max(1, (int)($offer['bundle_quantity'] ?? 2));
                    $bundles = floor($qty / $bundleQty);
                    $discount = min($lineSubtotal, max(0, (int)floor($bundles * ($bundleQty * $unit - max(0, $value)))));
                } elseif ($type === 'free_shipping') {
                    $shippingDiscount = $shippingCents;
                    $applied[] = ['offer_id'=>(int)($offer['id'] ?? 0),'type'=>$type,'discount_cents'=>$shippingCents];
                    continue;
                } else continue;
                if (isset($offer['max_discount_cents']) && $offer['max_discount_cents'] !== null) $discount = min($discount, max(0, (int)$offer['max_discount_cents']));
                $priority = (int)($offer['priority'] ?? 0);
                if ($discount > $best['discount'] || ($discount === $best['discount'] && $discount > 0 && $priority > $best['priority'])) {
                    $best = ['discount'=>$discount,'id'=>(int)($offer['id'] ?? 0),'priority'=>$priority,'type'=>$type];
                }
            }
            $item['offer_discount_cents'] = $best['discount'];
            $item['offer_id'] = $best['id'];
            $discountTotal += $best['discount'];
            if ($best['discount'] > 0) $applied[] = ['offer_id'=>$best['id'],'type'=>$best['type'],'discount_cents'=>$best['discount'],'product_id'=>(int)($item['product_id'] ?? 0)];
        }
        unset($item);
        return ['items'=>$result,'discount_cents'=>$discountTotal,'shipping_discount_cents'=>$shippingDiscount,'applied'=>$applied];
    }

    /**
     * Calculate a coupon discount only against eligible products; free shipping affects shipping only.
     * Caller must check CouponPolicy::state(), minimum subtotal, and redemption limits first.
     * @param array<int,array{quantity:int|float,unit_price_cents:int,category_ids?:array,labels?:array}> $items
     */
    public static function couponDiscount(array $coupon, array $items, int $shippingCents): int
    {
        if ($shippingCents < 0) throw new \InvalidArgumentException('Shipping cannot be negative.');
        $categoryIds = json_decode((string)($coupon['category_ids_json'] ?? '[]'), true) ?: [];
        $scopeLabels = json_decode((string)($coupon['labels_json'] ?? '[]'), true) ?: [];
        if (($coupon['discount_type'] ?? '') === 'free_shipping') return $shippingCents;
        $eligibleSubtotal = 0;
        foreach ($items as $item) {
            if (!OfferPricing::matchesScope((array)($item['category_ids'] ?? []), (array)($item['labels'] ?? []), $categoryIds, $scopeLabels)) continue;
            $qty = (float)($item['quantity'] ?? 0);
            $price = (int)($item['unit_price_cents'] ?? -1);
            if ($qty <= 0 || $price < 0) throw new \InvalidArgumentException('Cart line quantity and price must be valid.');
            $eligibleSubtotal += (int)floor($qty * $price);
        }
        return CouponPolicy::discountCents($coupon, $eligibleSubtotal, $shippingCents);
    }

    private function __construct() {}
}
