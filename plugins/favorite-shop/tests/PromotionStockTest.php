<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/Domain/OfferSchedule.php';
require_once __DIR__ . '/../src/Domain/CouponPolicy.php';
require_once __DIR__ . '/../src/Domain/StockStatus.php';
require_once __DIR__ . '/../src/Domain/OfferPricing.php';
require_once __DIR__ . '/../src/Domain/PromotionEngine.php';

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
expectSame(true, OfferPricing::matchesScope([4], ['summer'], [4], ['Summer']), 'Match label case-insensitively');
expectSame(false, OfferPricing::matchesScope([9], ['winter'], [4], ['Summer']), 'Reject unmatched scope');
$pricing = OfferPricing::bestPriceForProduct(10000, [
 ['id'=>1,'status'=>'scheduled','starts_at'=>'2026-10-08 00:00:00','ends_at'=>'2026-10-10 00:00:00','discount_type'=>'percent','discount_value'=>15,'category_ids_json'=>'[4]','labels_json'=>'[]','priority'=>1],
 ['id'=>2,'status'=>'scheduled','starts_at'=>'2026-10-08 00:00:00','ends_at'=>'2026-10-10 00:00:00','discount_type'=>'percent','discount_value'=>10,'category_ids_json'=>'[]','labels_json'=>'["summer"]','priority'=>2]
], [4], ['Summer'], $now);
expectSame(8500, $pricing['price_cents'], 'Automatically calculate scoped percentage offer');
expectSame(1500, $pricing['discount_cents'], 'Calculate discount amount from product price');
expectSame(1, $pricing['offer_id'], 'Choose best product offer');

$cart = PromotionEngine::applyOffers([
 ['product_id'=>22,'quantity'=>2,'unit_price_cents'=>10000,'category_ids'=>[4],'labels'=>['Summer']],
], [
 ['id'=>10,'status'=>'scheduled','starts_at'=>'2026-10-08 00:00:00','ends_at'=>'2026-10-10 00:00:00','discount_type'=>'buy_x_get_y','discount_value'=>0,'buy_quantity'=>1,'get_quantity'=>1,'category_ids_json'=>'[4]','labels_json'=>'[]'],
], 2500, $now);
expectSame(10000, $cart['discount_cents'], 'Buy X Get Y calculates free unit value');
expectSame(0, $cart['shipping_discount_cents'], 'Product promotion does not alter shipping');
$bundle = PromotionEngine::applyOffers([
 ['product_id'=>23,'quantity'=>2,'unit_price_cents'=>10000,'category_ids'=>[4],'labels'=>['Summer']],
], [
 ['id'=>11,'status'=>'scheduled','starts_at'=>'2026-10-08 00:00:00','ends_at'=>'2026-10-10 00:00:00','discount_type'=>'bundle_price','discount_value'=>15000,'bundle_quantity'=>2,'category_ids_json'=>'[]','labels_json'=>'["summer"]'],
], 2500, $now);
expectSame(5000, $bundle['discount_cents'], 'Bundle price discounts a matching label-scoped pair');
$freeShip = PromotionEngine::applyOffers([
 ['product_id'=>24,'quantity'=>1,'unit_price_cents'=>10000,'category_ids'=>[4],'labels'=>['Summer']],
], [
 ['id'=>12,'status'=>'scheduled','starts_at'=>'2026-10-08 00:00:00','ends_at'=>'2026-10-10 00:00:00','discount_type'=>'free_shipping','discount_value'=>0,'category_ids_json'=>'[]','labels_json'=>'[]'],
], 2500, $now);
expectSame(2500, $freeShip['shipping_discount_cents'], 'Free shipping offer applies shipping discount');
$targetedCoupon = ['discount_type'=>'percent','discount_value'=>10,'max_discount_cents'=>null,'category_ids_json'=>'[4]','labels_json'=>'["summer"]'];
expectSame(2000, PromotionEngine::couponDiscount($targetedCoupon, [
 ['quantity'=>2,'unit_price_cents'=>10000,'category_ids'=>[4],'labels'=>['Summer']],
 ['quantity'=>1,'unit_price_cents'=>5000,'category_ids'=>[8],'labels'=>['Summer']],
], 2500), 'Coupon only discounts category and label eligible items');

echo "Promotion and stock domain tests passed.\n";
