<?php
declare(strict_types=1);

namespace FavoriteCMS\Shop\Domain;

/** Decimal inventory/line quantities; piece products must be whole numbers. */
final class Quantity
{
    public static function normalize(mixed $value, int $scale = 3): string
    {
        if (!is_numeric($value) || !is_finite((float) $value) || (float) $value <= 0 || (float) $value > 100000000000) {
            throw new \InvalidArgumentException('Quantity must be a positive finite number.');
        }
        $normalized = number_format((float) $value, $scale, '.', '');
        $normalized = rtrim(rtrim($normalized, '0'), '.');
        return $normalized === '' ? '0' : $normalized;
    }

    public static function forUnit(mixed $value, string $unit, mixed $step = null): string
    {
        $quantity = self::normalize($value);
        if ($unit === 'piece' && floor((float) $quantity) !== (float) $quantity) {
            throw new \InvalidArgumentException('Piece-based products must use whole-number quantities.');
        }
        if ($step !== null && $step !== '' && is_numeric($step)) {
            $stepValue = (float) $step;
            if ($stepValue <= 0) throw new \InvalidArgumentException('Quantity step must be positive.');
            $ratio = (float) $quantity / $stepValue;
            if (abs($ratio - round($ratio)) > 0.000001) {
                throw new \InvalidArgumentException('Quantity does not match the allowed quantity step.');
            }
        }
        return $quantity;
    }

    private function __construct() {}
}
