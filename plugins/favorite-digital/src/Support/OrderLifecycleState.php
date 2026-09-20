<?php

declare(strict_types=1);

namespace FavoriteCMS\Digital\Support;

/**
 * OrderLifecycleState
 *
 * Defines the orthogonal payment and fulfillment states for Favorite Digital orders.
 *
 * Progression:
 * 1. Pending Payment:
 *    payment_status = pending, fulfillment_status = unfulfilled, status = pending
 * 2. Verified Successful Payment:
 *    payment_status = paid, fulfillment_status = unfulfilled, status = processing
 * 3. Access Granted / Fulfilled:
 *    payment_status = paid, fulfillment_status = fulfilled, status = completed
 * 4. Failed Payment:
 *    payment_status = failed, fulfillment_status = unfulfilled, status = failed
 * 5. Refunded / Revoked:
 *    payment_status = refunded, fulfillment_status = revoked, status = refunded
 */
final class OrderLifecycleState
{
    // Payment Statuses
    public const PAYMENT_UNPAID             = 'unpaid';
    public const PAYMENT_PENDING            = 'pending';
    public const PAYMENT_PARTIALLY_PAID     = 'partially_paid';
    public const PAYMENT_PAID               = 'paid';
    public const PAYMENT_PARTIALLY_REFUNDED = 'partially_refunded';
    public const PAYMENT_FAILED             = 'failed';
    public const PAYMENT_REFUNDED           = 'refunded';

    // Fulfillment Statuses
    public const FULFILLMENT_UNFULFILLED         = 'unfulfilled';
    public const FULFILLMENT_PARTIALLY_FULFILLED = 'partially_fulfilled';
    public const FULFILLMENT_FULFILLED           = 'fulfilled';
    public const FULFILLMENT_CANCELLED           = 'cancelled';
    public const FULFILLMENT_REVOKED             = 'revoked';

    // Overall Aggregate Statuses
    public const STATUS_PENDING    = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_PARTIAL    = 'partial';
    public const STATUS_COMPLETED  = 'completed';
    public const STATUS_FAILED     = 'failed';
    public const STATUS_CANCELLED  = 'cancelled';
    public const STATUS_REFUNDED   = 'refunded';

    public static function allStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_PROCESSING,
            self::STATUS_PARTIAL,
            self::STATUS_COMPLETED,
            self::STATUS_FAILED,
            self::STATUS_CANCELLED,
            self::STATUS_REFUNDED,
        ];
    }

    public static function allPaymentStatuses(): array
    {
        return [
            self::PAYMENT_UNPAID,
            self::PAYMENT_PENDING,
            self::PAYMENT_PARTIALLY_PAID,
            self::PAYMENT_PAID,
            self::PAYMENT_PARTIALLY_REFUNDED,
            self::PAYMENT_FAILED,
            self::PAYMENT_REFUNDED,
        ];
    }

    public static function allFulfillmentStatuses(): array
    {
        return [
            self::FULFILLMENT_UNFULFILLED,
            self::FULFILLMENT_PARTIALLY_FULFILLED,
            self::FULFILLMENT_FULFILLED,
            self::FULFILLMENT_CANCELLED,
            self::FULFILLMENT_REVOKED,
        ];
    }

    public static function isValidStatus(string $status): bool
    {
        return in_array($status, self::allStatuses(), true);
    }

    public static function isValidPaymentStatus(string $status): bool
    {
        return in_array($status, self::allPaymentStatuses(), true);
    }

    public static function isValidFulfillmentStatus(string $status): bool
    {
        return in_array($status, self::allFulfillmentStatuses(), true);
    }

    /**
     * Build state payload on payment success.
     * Note: fulfillment remains unfulfilled until access/entitlements are created.
     */
    public static function onPaymentSuccess(): array
    {
        return [
            'payment_status'     => self::PAYMENT_PAID,
            'fulfillment_status' => self::FULFILLMENT_UNFULFILLED,
            'status'             => self::STATUS_PROCESSING,
        ];
    }

    /**
     * Build state payload on fulfillment completion.
     */
    public static function onFulfillmentSuccess(): array
    {
        return [
            'payment_status'     => self::PAYMENT_PAID,
            'fulfillment_status' => self::FULFILLMENT_FULFILLED,
            'status'             => self::STATUS_COMPLETED,
        ];
    }

    /**
     * Build state payload on payment failure.
     */
    public static function onPaymentFailure(): array
    {
        return [
            'payment_status'     => self::PAYMENT_FAILED,
            'fulfillment_status' => self::FULFILLMENT_UNFULFILLED,
            'status'             => self::STATUS_FAILED,
        ];
    }

    /**
     * The ONLY four selectable status options in the Favorite Digital admin dropdown:
     * - Processing
     * - Partial
     * - Complete
     * - Refund
     *
     * Pending and Cancel are payment-controlled states and are strictly NOT selectable options in the dropdown.
     */
    public static function selectableAdminStatuses(): array
    {
        return [
            self::STATUS_PROCESSING => 'Processing',
            self::STATUS_PARTIAL    => 'Partial',
            self::STATUS_COMPLETED  => 'Complete',
            self::STATUS_REFUNDED   => 'Refund',
        ];
    }

    public static function isSelectableAdminStatus(string $status): bool
    {
        $normalized = match (strtolower(trim($status))) {
            'complete' => self::STATUS_COMPLETED,
            'refund'   => self::STATUS_REFUNDED,
            default    => strtolower(trim($status)),
        };
        return array_key_exists($normalized, self::selectableAdminStatuses());
    }

    /**
     * Customer-facing payment-controlled states.
     * Derived from payment lifecycle events (Favorite Pay confirmation/rejection).
     * Never selectable dropdown states in Favorite Digital.
     */
    public static function paymentControlledStatuses(): array
    {
        return [
            self::STATUS_PENDING   => 'Pending',
            self::STATUS_CANCELLED => 'Cancel',
        ];
    }

    public static function isPaymentControlledStatus(string $status): bool
    {
        $normalized = match (strtolower(trim($status))) {
            'cancel' => self::STATUS_CANCELLED,
            default  => strtolower(trim($status)),
        };
        return array_key_exists($normalized, self::paymentControlledStatuses());
    }

    /**
     * Check if a combination of order status, payment status, and fulfillment status is contradictory.
     * Returns error description string if contradictory, or null if consistent.
     */
    public static function areStatusesContradictory(string $orderStatus, string $paymentStatus, string $fulfillmentStatus): ?string
    {
        // Completed orders MUST have paid payment status and cannot have unfulfilled/cancelled/revoked fulfillment
        if ($orderStatus === self::STATUS_COMPLETED) {
            if ($paymentStatus !== self::PAYMENT_PAID) {
                return "Contradictory combination: Completed order must have Paid payment status.";
            }
            if (in_array($fulfillmentStatus, [self::FULFILLMENT_UNFULFILLED, self::FULFILLMENT_CANCELLED, self::FULFILLMENT_REVOKED], true)) {
                return "Contradictory combination: Completed order cannot have {$fulfillmentStatus} fulfillment status.";
            }
        }

        // Pending orders cannot be paid or cancelled/fulfilled
        if ($orderStatus === self::STATUS_PENDING) {
            if ($paymentStatus === self::PAYMENT_PAID) {
                return "Contradictory combination: Pending order cannot have Paid payment status.";
            }
            if ($fulfillmentStatus === self::FULFILLMENT_FULFILLED) {
                return "Contradictory combination: Pending order cannot have Fulfilled status.";
            }
            if ($fulfillmentStatus === self::FULFILLMENT_CANCELLED) {
                return "Contradictory combination: Pending order cannot have Cancelled fulfillment status.";
            }
        }

        // Unpaid or pending payments cannot have completed or fulfilled or revoked status
        if (in_array($paymentStatus, [self::PAYMENT_UNPAID, self::PAYMENT_PENDING], true)) {
            if ($orderStatus === self::STATUS_COMPLETED) {
                return "Contradictory combination: Unpaid/pending order cannot be Completed.";
            }
            if ($fulfillmentStatus === self::FULFILLMENT_FULFILLED) {
                return "Contradictory combination: Unpaid/pending order cannot be Fulfilled.";
            }
            if ($fulfillmentStatus === self::FULFILLMENT_REVOKED) {
                return "Contradictory combination: Unpaid/pending order cannot have Revoked fulfillment status.";
            }
        }

        // Failed payments cannot be completed or fulfilled
        if ($paymentStatus === self::PAYMENT_FAILED) {
            if ($orderStatus === self::STATUS_COMPLETED) {
                return "Contradictory combination: Order with failed payment cannot be Completed.";
            }
            if ($fulfillmentStatus === self::FULFILLMENT_FULFILLED) {
                return "Contradictory combination: Order with failed payment cannot be Fulfilled.";
            }
        }

        // Revoked fulfillment requires refunded state
        if ($fulfillmentStatus === self::FULFILLMENT_REVOKED) {
            if ($paymentStatus !== self::PAYMENT_REFUNDED && $orderStatus !== self::STATUS_REFUNDED) {
                return "Contradictory combination: Fulfillment cannot be revoked without order refund.";
            }
        }

        // Cancelled orders cannot be paid or fulfilled
        if ($orderStatus === self::STATUS_CANCELLED) {
            if ($paymentStatus === self::PAYMENT_PAID) {
                return "Contradictory combination: Cancelled order cannot have Paid payment status.";
            }
            if ($fulfillmentStatus === self::FULFILLMENT_FULFILLED) {
                return "Contradictory combination: Cancelled order cannot have Fulfilled status.";
            }
        }

        return null;
    }
}
