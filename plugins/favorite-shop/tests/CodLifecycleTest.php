<?php
declare(strict_types=1);
require_once __DIR__ . '/../autoload.php';
use FavoriteCMS\Shop\Domain\CodLifecycle;
$assert = static function (bool $ok, string $message): void { if (!$ok) throw new RuntimeException($message); };
$assert(CodLifecycle::paymentStatusAfterCheckout('cash_on_delivery') === 'unpaid', 'COD checkout must remain unpaid.');
$assert(CodLifecycle::canCollect(CodLifecycle::ORDER_DELIVERED, CodLifecycle::PAYMENT_UNPAID), 'Delivered unpaid COD can be collected.');
$assert(!CodLifecycle::canCollect(CodLifecycle::ORDER_CANCELLED, CodLifecycle::PAYMENT_UNPAID), 'Cancelled order cannot be collected.');
$assert(!CodLifecycle::canShip(CodLifecycle::ORDER_DELIVERED), 'Delivered order cannot ship again.');
$assert(CodLifecycle::paymentStatusAfterCollection(10000, 10000) === 'collected', 'Full collection marks paid.');
$assert(CodLifecycle::paymentStatusAfterCollection(3000, 10000) === 'unpaid', 'Partial collection stays unpaid.');
echo "Favorite Shop COD lifecycle tests passed.\n";
