<?php
declare(strict_types=1);
require_once __DIR__ . '/../autoload.php';
use FavoriteCMS\Shop\Domain\ProductMeasurement;
use FavoriteCMS\Shop\Domain\Quantity;
$assert = static function (bool $ok, string $message): void { if (!$ok) throw new RuntimeException($message); };
$piece = ProductMeasurement::normalize(null);
$assert($piece['unit'] === 'piece' && $piece['unit_quantity'] === '1', 'Piece is the default selling unit.');
$assert($piece['weight_grams'] === null, 'Shipping weight is optional.');
$rice = ProductMeasurement::normalize('kg', 1, null, 1050);
$assert($rice['unit'] === 'kg' && $rice['weight_grams'] === 1050, 'Selling quantity and shipping weight are separate.');
$honey = ProductMeasurement::normalize('g', 500);
$assert($honey['unit_quantity'] === '500' && $honey['weight_grams'] === null, 'Weight-based selling does not require shipping weight.');
$custom = ProductMeasurement::normalize('custom', 1, 'pack');
$assert($custom['unit_label'] === 'pack', 'Custom unit label is retained.');
$assert(ProductMeasurement::effectiveShippingWeight(null, 900) === 900, 'Variant weight falls back to product weight.');
$assert(ProductMeasurement::effectiveShippingWeight(450, 900) === 450, 'Variant weight overrides product weight.');
$assert(ProductMeasurement::effectiveShippingWeight(null, null) === null, 'Unknown weight remains unknown.');
$assert(Quantity::forUnit('0.5', 'kg') === '0.5', 'Weight-based quantities allow fractional amounts.');
$assert(Quantity::forUnit('500', 'g', '50') === '500', 'Quantity steps are supported.');
$assert(Quantity::forUnit('2', 'piece') === '2', 'Piece quantity accepts whole numbers.');
foreach ([
    static fn() => ProductMeasurement::normalize('box'),
    static fn() => ProductMeasurement::normalize('custom', 1, ''),
    static fn() => ProductMeasurement::normalize('kg', 0),
    static fn() => ProductMeasurement::normalize('piece', 1, null, -1),
    static fn() => Quantity::forUnit('0.5', 'piece'),
    static fn() => Quantity::forUnit('0.3', 'kg', '0.2'),
    static fn() => Quantity::normalize('INF'),
] as $invalid) {
    try { $invalid(); throw new RuntimeException('Invalid measurement/quantity should have been rejected.'); }
    catch (InvalidArgumentException) {}
}
echo "Favorite Shop product measurement and quantity tests passed.\n";
