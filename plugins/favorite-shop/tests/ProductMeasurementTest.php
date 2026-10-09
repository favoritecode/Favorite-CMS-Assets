<?php
declare(strict_types=1);

require_once __DIR__ . '/../autoload.php';

use FavoriteCMS\\Shop\\Domain\\ProductMeasurement;

$assert = static function (bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException($message);
};

$piece = ProductMeasurement::normalize(null);
$assert($piece['unit'] === 'piece', 'Piece is the default selling unit.');
$assert($piece['unit_quantity'] === '1', 'Default selling quantity is one.');
$assert($piece['weight_grams'] === null, 'Shipping weight is optional.');

$rice = ProductMeasurement::normalize('kg', 1, null, 1050);
$assert($rice['unit'] === 'kg' && $rice['unit_quantity'] === '1', 'Rice can be sold by kg.');
$assert($rice['weight_grams'] === 1050, 'Shipping weight can differ from selling quantity.');

$honey = ProductMeasurement::normalize('g', 500);
$assert($honey['unit_quantity'] === '500' && $honey['weight_grams'] === null, 'Honey can be sold by grams without shipping weight.');

$custom = ProductMeasurement::normalize('custom', 1, 'pack');
$assert($custom['unit_label'] === 'pack', 'Custom units require and preserve their label.');

$assert(ProductMeasurement::effectiveShippingWeight(null, 900) === 900, 'Variant weight falls back to product weight.');
$assert(ProductMeasurement::effectiveShippingWeight(450, 900) === 450, 'Explicit variant weight overrides product weight.');
$assert(ProductMeasurement::effectiveShippingWeight(null, null) === null, 'Unknown weight stays unknown.');

foreach ([
    static fn() => ProductMeasurement::normalize('box'),
    static fn() => ProductMeasurement::normalize('custom', 1, ''),
    static fn() => ProductMeasurement::normalize('kg', 0),
    static fn() => ProductMeasurement::normalize('piece', 1, null, -1),
] as $invalid) {
    try { $invalid(); throw new RuntimeException('Invalid measurement should have been rejected.'); }
    catch (InvalidArgumentException) {}
}

echo "Favorite Shop product measurement tests passed.\n";
