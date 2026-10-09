<?php
declare(strict_types=1);

namespace FavoriteCMS\Shop\Domain;

/** Selling units and optional shipping weight are independent concepts. */
final class ProductMeasurement
{
    public const UNITS = ['piece', 'kg', 'g', 'litre', 'ml', 'custom'];

    /** @return array{unit:string,unit_quantity:string,unit_label:?string,weight_grams:?int} */
    public static function normalize(?string $unit, mixed $unitQuantity = 1, ?string $customLabel = null, mixed $shippingWeightGrams = null): array
    {
        $unit = strtolower(trim((string) $unit));
        if ($unit === '') $unit = 'piece';
        if (!in_array($unit, self::UNITS, true)) throw new \InvalidArgumentException('Unsupported product selling unit.');
        $quantity = Quantity::normalize($unitQuantity);
        $label = trim((string) $customLabel);
        if ($unit === 'custom' && $label === '') throw new \InvalidArgumentException('A label is required for a custom unit.');
        if ($unit !== 'custom') $label = '';
        $weight = null;
        if ($shippingWeightGrams !== null && $shippingWeightGrams !== '') {
            if (!is_numeric($shippingWeightGrams) || (float) $shippingWeightGrams < 0 || (float) $shippingWeightGrams > 1000000000) {
                throw new \InvalidArgumentException('Shipping weight must be zero or a positive number of grams.');
            }
            $weight = (int) round((float) $shippingWeightGrams);
        }
        return [
            'unit' => $unit,
            'unit_quantity' => $quantity,
            'unit_label' => $label === '' ? null : (function_exists('mb_substr') ? mb_substr($label, 0, 80) : substr($label, 0, 80)),
            'weight_grams' => $weight,
        ];
    }

    public static function effectiveShippingWeight(?int $variantWeightGrams, ?int $productWeightGrams): ?int
    {
        if ($variantWeightGrams !== null) {
            if ($variantWeightGrams < 0) throw new \InvalidArgumentException('Variant weight cannot be negative.');
            return $variantWeightGrams;
        }
        if ($productWeightGrams !== null && $productWeightGrams < 0) throw new \InvalidArgumentException('Product weight cannot be negative.');
        return $productWeightGrams;
    }

    private function __construct() {}
}
