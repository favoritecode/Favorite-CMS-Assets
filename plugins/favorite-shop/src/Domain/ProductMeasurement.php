<?php
declare(strict_types=1);

namespace FavoriteCMS\\Shop\\Domain;

/**
 * Product selling units and optional shipping weight.
 *
 * Selling quantity/unit is descriptive (e.g. 1 kg rice or 1 piece shirt).
 * Shipping weight is independent and optional; it must never be inferred from
 * the selling unit because packaging and actual shipped weight may differ.
 */
final class ProductMeasurement
{
    public const UNITS = ['piece', 'kg', 'g', 'litre', 'ml', 'custom'];

    /**
     * @return array{unit:string,unit_quantity:string,unit_label:?string,weight_grams:?int}
     */
    public static function normalize(
        ?string $unit,
        mixed $unitQuantity = 1,
        ?string $customLabel = null,
        mixed $shippingWeightGrams = null
    ): array {
        $unit = strtolower(trim((string) $unit));
        if ($unit === '') $unit = 'piece';
        if (!in_array($unit, self::UNITS, true)) {
            throw new InvalidArgumentException('Unsupported product selling unit.');
        }

        if (!is_numeric($unitQuantity) || (float) $unitQuantity <= 0 || (float) $unitQuantity > 100000000) {
            throw new InvalidArgumentException('Unit quantity must be a positive number.');
        }
        $quantity = rtrim(rtrim(number_format((float) $unitQuantity, 6, '.', ''), '0'), '.');
        if ($quantity === '') $quantity = '0';

        $label = trim((string) $customLabel);
        if ($unit === 'custom' && $label === '') {
            throw new InvalidArgumentException('A label is required for a custom unit.');
        }
        if ($unit !== 'custom') $label = '';

        $weight = null;
        if ($shippingWeightGrams !== null && $shippingWeightGrams !== '') {
            if (!is_numeric($shippingWeightGrams) || (float) $shippingWeightGrams < 0 || (float) $shippingWeightGrams > 1000000000) {
                throw new InvalidArgumentException('Shipping weight must be zero or a positive number of grams.');
            }
            $weight = (int) round((float) $shippingWeightGrams);
        }

        return [
            'unit' => $unit,
            'unit_quantity' => $quantity,
            'unit_label' => $label === '' ? null : mb_substr($label, 0, 80),
            'weight_grams' => $weight,
        ];
    }

    /**
     * Return shipping weight in grams, preferring an explicitly set variant
     * weight and falling back to the product default. Null means unknown.
     */
    public static function effectiveShippingWeight(?int $variantWeightGrams, ?int $productWeightGrams): ?int
    {
        if ($variantWeightGrams !== null) {
            if ($variantWeightGrams < 0) throw new InvalidArgumentException('Variant weight cannot be negative.');
            return $variantWeightGrams;
        }
        if ($productWeightGrams !== null && $productWeightGrams < 0) {
            throw new InvalidArgumentException('Product weight cannot be negative.');
        }
        return $productWeightGrams;
    }

    private function __construct() {}
}
