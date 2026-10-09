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

    /** Scope matches if category OR label matches; an empty scope means all products. */
    public static function matchesScope(array $productCategoryIds, array $productLabels, array $offerCategoryIds, array $offerLabels): bool
    {
        if ($offerCategoryIds === [] && $offerLabels === []) return true;
        $categories = array_map('intval', $productCategoryIds);
        foreach ($offerCategoryIds as $id) if (in_array((int)$id, $categories, true)) return true;
        $labels = array_map(static fn($v) => function_exists('mb_strtolower') ? mb_strtolower(trim((string)$v), 'UTF-8') : strtolower(trim((string)$v)), $productLabels);
        foreach ($offerLabels as $label) {
            $needle = function_exists('mb_strtolower') ? mb_strtolower(trim((string)$label), 'UTF-8') : strtolower(trim((string)$label));
            if ($needle !== '' && in_array($needle, $labels, true)) return true;
        }
        return false;
    }

    private function __construct() {}
}
