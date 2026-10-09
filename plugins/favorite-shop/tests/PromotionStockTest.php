<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/Domain/OfferSchedule.php';
require_once __DIR__ . '/../src/Domain/CouponPolicy.php';
require_once __DIR__ . '/../src/Domain/StockStatus.php';
require_once __DIR__ . '/../src/Domain/OfferPricing.php';

use FavoriteCMS\Shop\Domain\CouponPolicy;
use FavoriteCMS\Shop\Domain\OfferSchedule;
use FavoriteCMS\Shop\Domain\StockStatus;
use FavoriteCMS\Shop\Domain\OfferPricing;

function expectSame(mixed $expected, mixed $actual, string $message): void {
    if ($expected !== $actual) throw new RuntimeException($message . ': expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
}
function expectInvalid(callable $callback, string $message): void {
    try { $callback(); } catch (InvalidArgumentException) { return; }
    throw new RuntimeException($message);
}
$now = new DateTimeImmutable('2026-10-09 12:00:00', new DateTimeZone('UTC'));
expectSame('scheduled', OfferSchedule::state('scheduled','2026-10-10 00:00:00','2026-10-11 00:00:00',$now), 'Future offer');
expectSame('active', OfferSchedule::state('scheduled','2026-10-08 00:00:00','2026-10-10 00:00:00',$now), 'Active offer');
expectSame('expired', OfferSchedule::state('scheduled','2026-10-07 00:00:00','2026-10-08 00:00:00',$now), 'Expired offer');
expectInvalid(fn()=>OfferSchedule::normalize('2026-10-11','2026-10-10'), 'Reject reversed offer dates');
$coupon = CouponPolicy::normalize(['code'=>'SAVE10','discount_type'=>'percent','discount_value'=>'10','starts_at'=>'2026-10-01','ends_at'=>'2026-11-01','max_discount_cents'=>'5000','min_subtotal_cents'=>'1000']);
expectSame('SAVE10', $coupon['code'], 'Normalize coupon code');
expectSame(1000, CouponPolicy::discountCents($coupon, 10000), 'Percent discount');
expectSame(5000, CouponPolicy::discountCents(['discount_type'=>'fixed','discount_value'=>9000,'max_discount_cents'=>5000], 10000), 'Cap fixed discount');
expectSame(2500, CouponPolicy::discountCents(['discount_type'=>'free_shipping'], 10000, 2500), 'Free shipping discount');
expectSame('scheduled', CouponPolicy::state(['starts_at'=>'2026-10-10','ends_at'=>'2026-10-11'],0,0,$now), 'Future coupon');
expectSame('expired', CouponPolicy::state(['starts_at'=>'2026-10-01','ends_at'=>'2026-10-08'],0,0,$now), 'Expired coupon');
expectSame('out_of_stock', StockStatus::resolve(0), 'Zero stock');
expectSame('low_stock', StockStatus::resolve(2,true,false,3), 'Low stock');
expectSame('on_backorder', StockStatus::resolve(0,true,true), 'Explicit backorder');
expectSame(false, StockStatus::canFulfil(2,3), 'Prevent overselling');
expectSame(true, StockStatus::canFulfil(2,3,false), 'Unmanaged stock');
expectSame(9000, OfferPricing::discountedPriceCents(10000,10), 'Auto percentage price calculation');
expectSame(8500, OfferPricing::discountedPriceCents(10000,20,1500), 'Offer maximum discount cap');
$scope = OfferPricing::normalizeScope(['2','4'], 'Summer, clearance; Featured');
expectSame([2,4], $scope['category_ids'], 'Normalize offer category scope');
expectSame(['Summer','clearance','Featured'], $scope['labels'], 'Normalize offer label scope');
expectSame(false, OfferPricing::matchesScope([4], ['Other'], [4], ['Summer']), 'Require label when category and label scopes are both set');
expectSame(true, OfferPricing::matchesScope([4], ['Summer'], [4], ['Summer']), 'Match combined category and label scope');
expectSame(true, OfferPricing::matchesScope([9], ['summer'], [4], ['Summer']), 'Match label case-insensitively');
expectSame(false, OfferPricing::matchesScope([9], ['winter'], [4], ['Summer']), 'Reject unmatched scope');
$pricing = OfferPricing::bestPriceForProduct(10000, [
 ['id'=>1,'status'=>'scheduled','starts_at'=>'2026-10-08 00:00:00','ends_at'=>'2026-10-10 00:00:00','discount_type'=>'percent','discount_value'=>15,'category_ids_json'=>'[4]','labels_json'=>'[]','priority'=>1],
 ['id'=>2,'status'=>'scheduled','starts_at'=>'2026-10-08 00:00:00','ends_at'=>'2026-10-10 00:00:00','discount_type'=>'percent','discount_value'=>10,'category_ids_json'=>'[]','labels_json'=>'["summer"]','priority'=>2]
], [4], ['Summer'], $now);
expectSame(8500, $pricing['price_cents'], 'Automatically calculate scoped percentage offer');
expectSame(1500, $pricing['discount_cents'], 'Calculate discount amount from product price');
expectSame(1, $pricing['offer_id'], 'Choose best product offer');

echo "Promotion and stock domain tests passed.\n";
