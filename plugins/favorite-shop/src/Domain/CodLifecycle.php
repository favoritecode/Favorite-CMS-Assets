<?php
declare(strict_types=1);
namespace FavoriteCMS\Shop\Domain;
/** COD is unpaid at checkout; delivery and collection are separate lifecycle events. */
final class CodLifecycle
{
    public const PAYMENT_UNPAID = 'unpaid';
    public const PAYMENT_COLLECTED = 'collected';
    public const ORDER_PENDING = 'pending';
    public const ORDER_PROCESSING = 'processing';
    public const ORDER_SHIPPED = 'shipped';
    public const ORDER_DELIVERED = 'delivered';
    public const ORDER_CANCELLED = 'cancelled';
    public const ORDER_RETURNED = 'returned';
    public static function canCollect(string $orderStatus, string $paymentStatus): bool {
        return !in_array($orderStatus, [self::ORDER_CANCELLED, self::ORDER_RETURNED], true) && $paymentStatus !== self::PAYMENT_COLLECTED;
    }
    public static function canShip(string $orderStatus): bool {
        return !in_array($orderStatus, [self::ORDER_CANCELLED, self::ORDER_RETURNED, self::ORDER_DELIVERED], true);
    }
    public static function paymentStatusAfterCheckout(string $method): string { return self::PAYMENT_UNPAID; }
    public static function paymentStatusAfterCollection(int $collectedCents, int $orderTotalCents): string {
        return $collectedCents >= $orderTotalCents ? self::PAYMENT_COLLECTED : self::PAYMENT_UNPAID;
    }
}
