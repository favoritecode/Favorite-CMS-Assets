<?php
declare(strict_types=1);
namespace FavoriteCMS\Shop\Domain;

/** Resolves product-level percentage offers without overwriting the stored regular price. */
final class OfferPricing
{
    public static function discountedPriceCents(int $basePriceCents, int $percent, ?int $maxDiscountCents = null): int
    {
        if ($basePriceCents < 0) throw new \InvalidArgumentException('Base price cannot be negative.');
        if ($percent < 1 || $percent > 100) throw new \InvalidArgumentException('Offer percentage must be between 1 and 100.');
        $discount = (int) floor($basePriceCents * $percent / 100);
        if ($maxDiscountCents !== null) {
            if ($maxDiscountCents < 0) throw new \InvalidArgumentException('Maximum discount cannot be negative.');
            $discount = min($discount, $maxDiscountCents);
        }
        return max(0, $basePriceCents - min($basePriceCents, $discount));
    }

    /** Normalize category IDs and case-insensitive product labels into a stable scope. */
    public static function normalizeScope(array $categoryIds, string $labelsCsv): array
    {
        $ids = [];
        foreach ($categoryIds as $id) {
            $n = filter_var($id, FILTER_VALIDATE_INT);
            if ($n === false || $n < 1) throw new \InvalidArgumentException('Category selection contains an invalid ID.');
            $ids[] = (int)$n;
        }
        $labels = [];
        foreach (preg_split('/[,;\r\n]+/', $labelsCsv) ?: [] as $label) {
            $label = trim(preg_replace('/\s+/', ' ', (string)$label) ?? '');
            if ($label === '') continue;
            if (strlen($label) > 80) throw new \InvalidArgumentException('Each product label must be 80 characters or fewer.');
            $key = function_exists('mb_strtolower') ? mb_strtolower($label, 'UTF-8') : strtolower($label);
            $labels[$key] = $label;
        }
        return ['category_ids'=>array_values(array_unique($ids)), 'labels'=>array_values($labels)];
    }

    /**
     * Calculate the best eligible scheduled offer for one product. Offers are evaluated per product price,
     * so a percentage automatically produces a different discount for each category/label-matched item.
     * @return array{price_cents:int,discount_cents:int,offer_id:?int}
     */
    public static function bestPriceForProduct(int $regularPriceCents, array $offers, array $productCategoryIds, array $productLabels, ?\DateTimeImmutable $now = null): array
    {
        if ($regularPriceCents < 0) throw new \InvalidArgumentException('Regular price cannot be negative.');
        $now = $now ?? new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $best = ['price_cents'=>$regularPriceCents, 'discount_cents'=>0, 'offer_id'=>null, 'priority'=>PHP_INT_MIN];
        foreach ($offers as $offer) {
            $status = OfferSchedule::state((string)($offer['status'] ?? 'scheduled'), (string)($offer['starts_at'] ?? ''), (string)($offer['ends_at'] ?? ''), $now);
            if ($status !== 'active') continue;
            if (isset($offer['usage_limit']) && $offer['usage_limit'] !== null && (int)($offer['usage_count'] ?? 0) >= (int)$offer['usage_limit']) continue;
            $categoryIds = json_decode((string)($offer['category_ids_json'] ?? '[]'), true);
            $labels = json_decode((string)($offer['labels_json'] ?? '[]'), true);
            if (!is_array($categoryIds)) $categoryIds = [];
            if (!is_array($labels)) $labels = [];
            if (!self::matchesScope($productCategoryIds, $productLabels, $categoryIds, $labels)) continue;
            $type = (string)($offer['discount_type'] ?? 'percent');
            $value = (int)($offer['discount_value'] ?? 0);
            if ($type === 'percent') {
                if ($value < 1 || $value > 100) continue;
                $price = self::discountedPriceCents($regularPriceCents, $value);
            } elseif ($type === 'fixed') {
                if ($value < 0) continue;
                $price = max(0, $regularPriceCents - $value);
            } elseif ($type === 'sale_price') {
                if ($value < 0) continue;
                $price = min($regularPriceCents, $value);
            } else continue;
            $priority = (int)($offer['priority'] ?? 0);
            // Lowest price wins; priority breaks ties. Stored regular prices are never mutated.
            if ($price < $best['price_cents'] || ($price === $best['price_cents'] && $price < $regularPriceCents && $priority > $best['priority'])) {
                $best = ['price_cents'=>$price, 'discount_cents'=>$regularPriceCents-$price, 'offer_id'=>isset($offer['id'])?(int)$offer['id']:null, 'priority'=>$priority];
            }
        }
        unset($best['priority']);
        return $best;
    }

    /** Category and label scopes combine with AND when both are configured; either scope works alone. */
    public static function matchesScope(array $productCategoryIds, array $productLabels, array $offerCategoryIds, array $offerLabels): bool
    {
        if ($offerCategoryIds === [] && $offerLabels === []) return true;
        $categories = array_map('intval', $productCategoryIds);
        $categoryMatch = false;
        foreach ($offerCategoryIds as $id) if (in_array((int)$id, $categories, true)) { $categoryMatch = true; break; }
        $labels = array_map(static fn($v) => function_exists('mb_strtolower') ? mb_strtolower(trim((string)$v), 'UTF-8') : strtolower(trim((string)$v)), $productLabels);
        $labelMatch = false;
        foreach ($offerLabels as $label) {
            $needle = function_exists('mb_strtolower') ? mb_strtolower(trim((string)$label), 'UTF-8') : strtolower(trim((string)$label));
            if ($needle !== '' && in_array($needle, $labels, true)) { $labelMatch = true; break; }
        }
        // When both scopes are set, require both. A single selected scope works independently.
        if ($offerCategoryIds !== [] && $offerLabels !== []) return $categoryMatch && $labelMatch;
        return $offerCategoryIds !== [] ? $categoryMatch : $labelMatch;
    }

    private function __construct() {}
}
