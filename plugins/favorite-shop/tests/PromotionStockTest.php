<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/Domain/OfferSchedule.php';
require_once __DIR__ . '/../src/Domain/CouponPolicy.php';
require_once __DIR__ . '/../src/Domain/StockStatus.php';

use FavoriteCMS\Shop\Domain\CouponPolicy;
use FavoriteCMS\Shop\Domain\OfferSchedule;
use FavoriteCMS\Shop\Domain\StockStatus;

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
echo "Promotion and stock domain tests passed.\n";
