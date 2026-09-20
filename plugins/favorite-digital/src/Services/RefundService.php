<?php

declare(strict_types=1);

namespace FavoriteCMS\Digital\Services;

use DateTimeImmutable;
use FavoriteCMS\Core\Database;
use FavoriteCMS\Digital\Domain\ProductType;
use FavoriteCMS\Digital\Exceptions\RefundException;
use FavoriteCMS\Digital\Repositories\EntitlementRepository;
use FavoriteCMS\Digital\Repositories\OrderRepository;
use FavoriteCMS\Digital\Repositories\RefundRepository;
use FavoriteCMS\Digital\Support\OrderLifecycleState;
use Throwable;

/**
 * RefundService
 *
 * Authoritative orchestrator for Favorite Digital refunds:
 * - Server-side refund eligibility & calculation
 * - 100% wallet credit destination regardless of original payment method
 * - Immutable ledger entry in favorite_digital_wallet_transactions (type = refund_credit)
 * - Immutable record in favorite_digital_refunds
 * - Atomic entitlement revocation for direct purchases & package-derived children
 * - Membership revocation via MembershipLifecycleService public API
 * - Order lifecycle state completion (payment: refunded, fulfillment: revoked, aggregate: refunded)
 * - Strict idempotency across repeated executions
 */
class RefundService
{
    protected OrderRepository $orderRepo;
    protected RefundRepository $refundRepo;
    protected WalletService $walletService;
    protected EntitlementRepository $entitlementRepo;
    protected MembershipLifecycleService $membershipService;
    protected ?Database $db;

    public function __construct(
        OrderRepository $orderRepo,
        RefundRepository $refundRepo,
        WalletService $walletService,
        EntitlementRepository $entitlementRepo,
        MembershipLifecycleService $membershipService,
        ?Database $db = null
    ) {
        $this->orderRepo = $orderRepo;
        $this->refundRepo = $refundRepo;
        $this->walletService = $walletService;
        $this->entitlementRepo = $entitlementRepo;
        $this->membershipService = $membershipService;
        $this->db = $db ?? $orderRepo->getDatabase();
    }

    public function getOrderRepository(): OrderRepository
    {
        return $this->orderRepo;
    }

    public function getRefundRepository(): RefundRepository
    {
        return $this->refundRepo;
    }

    public function getWalletService(): WalletService
    {
        return $this->walletService;
    }

    public function getEntitlementRepository(): EntitlementRepository
    {
        return $this->entitlementRepo;
    }

    public function getMembershipService(): MembershipLifecycleService
    {
        return $this->membershipService;
    }

    /**
     * Calculate server-authoritative verified paid amount in BDT.
     * Sums only completed/paid payment records.
     */
    public function calculateAuthoritativeRefundAmount(object $order): string
    {
        $payments = $this->orderRepo->getOrderPayments((int)$order->id);
        $settledMinor = 0;

        foreach ($payments as $payment) {
            if ($payment->status === 'completed' || $payment->status === 'paid') {
                $clean = trim((string)$payment->amount_paid);
                if ($clean !== '' && is_numeric($clean)) {
                    $settledMinor += (int)round((float)$clean * 100);
                }
            }
        }

        return number_format($settledMinor / 100, 2, '.', '');
    }

    /**
     * Calculate server-authoritative total refunded amount in BDT.
     * Sums only completed refund records.
     */
    public function calculateAuthoritativeRefundedAmount(int $orderId): string
    {
        return $this->refundRepo->calculateTotalRefundedAmount($orderId);
    }

    /**
     * Validate refund eligibility for an order.
     */
    public function validateRefundEligibility(int $orderId): array
    {
        $order = $this->orderRepo->findOrderWithItems($orderId);
        if (!$order) {
            throw RefundException::orderNotFound($orderId);
        }

        $orderCurrency = !empty($order->currency) ? strtoupper(trim((string)$order->currency)) : (class_exists(\FavoriteCMS\Core\Currency::class) ? \FavoriteCMS\Core\Currency::getPrimaryCurrency() : 'BDT');

        // Check if already refunded
        if ($order->payment_status === OrderLifecycleState::PAYMENT_REFUNDED ||
            $order->status === OrderLifecycleState::STATUS_REFUNDED) {
            $existing = $this->refundRepo->findRefundByOrderId($orderId);
            return [
                'eligible'             => false,
                'already_refunded'     => true,
                'existing_refund'      => $existing,
                'order'                => $order,
                'verified_paid_amount' => '0.00',
                'currency'             => $orderCurrency,
                'reason'               => 'Order has already been refunded.',
            ];
        }

        $paidAmount = $this->calculateAuthoritativeRefundAmount($order);
        $paidMinor = (int)round((float)$paidAmount * 100);

        if ($paidMinor <= 0) {
            return [
                'eligible'             => false,
                'already_refunded'     => false,
                'order'                => $order,
                'verified_paid_amount' => '0.00',
                'currency'             => $orderCurrency,
                'reason'               => 'Order has no verified paid component to refund.',
            ];
        }

        return [
            'eligible'             => true,
            'already_refunded'     => false,
            'order'                => $order,
            'verified_paid_amount' => $paidAmount,
            'currency'             => $orderCurrency,
            'reason'               => 'Eligible for wallet refund.',
        ];
    }

    /**
     * Process a full order refund atomically and idempotently.
     *
     * @param int $orderId
     * @param string $reason Business reason for refund
     * @param int $actorUserId User performing or requesting the refund
     * @param bool $isAdmin Whether the action is performed by an admin
     * @return object Refund record
     * @throws RefundException
     */
    public function processRefund(
        int $orderId,
        string $reason,
        int $actorUserId = 0,
        bool $isAdmin = false
    ): object {
        $cleanReason = trim($reason);
        if ($cleanReason === '') {
            throw RefundException::invalidReason("A specific reason is required to issue a refund.");
        }

        $order = $this->orderRepo->findOrderWithItems($orderId);
        if (!$order) {
            throw RefundException::orderNotFound($orderId);
        }

        // Idempotency: If already refunded, return existing refund record
        if ($order->payment_status === OrderLifecycleState::PAYMENT_REFUNDED ||
            $order->status === OrderLifecycleState::STATUS_REFUNDED) {
            $existing = $this->refundRepo->findRefundByOrderId($orderId);
            if ($existing) {
                return $existing;
            }
            throw RefundException::alreadyRefunded($order->order_number);
        }

        // Calculate authoritative total received and previous refunds
        $totalReceivedAmount = $this->calculateAuthoritativeRefundAmount($order);
        $totalReceivedMinor  = (int)round((float)$totalReceivedAmount * 100);
        $totalRefundedAmount = $this->calculateAuthoritativeRefundedAmount($orderId);
        $previousRefundsMinor = (int)round((float)$totalRefundedAmount * 100);

        // Security check: Unpaid order cannot create money
        if ($totalReceivedMinor <= 0) {
            // An unpaid order can be cancelled, but cannot generate a refund credit
            if ($order->status === OrderLifecycleState::STATUS_PENDING) {
                $this->orderRepo->updateOrderStatus($orderId, OrderLifecycleState::STATUS_CANCELLED);
                $this->orderRepo->updateFulfillmentStatus($orderId, OrderLifecycleState::FULFILLMENT_CANCELLED);
            }
            throw RefundException::noVerifiedPayment($order->order_number);
        }

        // Remaining refundable balance to credit to customer wallet
        $remainingRefundableMinor = max(0, $totalReceivedMinor - $previousRefundsMinor);
        if ($remainingRefundableMinor <= 0) {
            $existing = $this->refundRepo->findRefundByOrderId($orderId);
            if ($existing) {
                return $existing;
            }
            throw RefundException::alreadyRefunded($order->order_number);
        }

        $refundAmount    = number_format($remainingRefundableMinor / 100, 2, '.', '');
        $totalPaidAmount = number_format($totalReceivedMinor / 100, 2, '.', '');

        $userId = (int)$order->user_id;
        if ($userId <= 0) {
            throw RefundException::invalidUserId($userId);
        }

        // Generate deterministic refund reference for idempotency
        $refId = "ref_refund_ord_{$orderId}";
        $txDesc = "Refund for Order #{$order->order_number}: {$cleanReason}";

        // Credit Customer Wallet via WalletService OUTSIDE executeInTransaction to prevent nested PDO transactions with Favorite Pay
        $walletTx = $this->walletService->credit(
            $userId,
            $refundAmount,
            $refId,
            $txDesc,
            $orderId,
            'refund_credit'
        );

        try {
            return $this->executeInTransaction(function () use (
                $order,
                $orderId,
                $userId,
                $refundAmount,
                $totalPaidAmount,
                $cleanReason,
                $actorUserId,
                $walletTx,
                $refId
            ) {
                // 1. Double check order status inside transaction (lock / race protection)
                $freshOrder = $this->orderRepo->findOrder($orderId);
                if ($freshOrder->payment_status === OrderLifecycleState::PAYMENT_REFUNDED ||
                    $freshOrder->status === OrderLifecycleState::STATUS_REFUNDED) {
                    $existing = $this->refundRepo->findRefundByOrderId($orderId);
                    if ($existing) {
                        return $existing;
                    }
                    throw RefundException::alreadyRefunded($order->order_number);
                }

                // 2. Create immutable refund record in favorite_digital_refunds
                $now = date('Y-m-d H:i:s');
                $refundId = $this->refundRepo->createRefund([
                    'order_id'              => $orderId,
                    'order_item_id'         => null, // Full order refund
                    'user_id'               => $userId,
                    'refund_amount'         => $refundAmount,
                    'currency'              => !empty($order->currency) ? strtoupper(trim((string)$order->currency)) : (class_exists(\FavoriteCMS\Core\Currency::class) ? \FavoriteCMS\Core\Currency::getPrimaryCurrency() : 'BDT'),
                    'destination'           => 'wallet', // Strictly wallet
                    'refund_method'         => 'wallet',
                    'reference'             => $refId,
                    'refund_type'           => 'full',
                    'processed_by'          => $actorUserId > 0 ? $actorUserId : null,
                    'wallet_transaction_id' => (int)$walletTx->id,
                    'reason'                => $cleanReason,
                    'status'                => 'completed',
                    'processed_at'          => $now,
                    'created_at'            => $now,
                ]);

                // 3. Update settled payment records to 'refunded'
                $payments = $this->orderRepo->getOrderPayments($orderId);
                foreach ($payments as $pay) {
                    if ($pay->status === 'completed' || $pay->status === 'paid') {
                        $this->orderRepo->updatePayment((int)$pay->id, [
                            'status' => 'refunded',
                        ]);
                    }
                }

                // 4. Revoke access / entitlements generated by this order
                $this->revokeOrderEntitlements($order);

                // 5. Complete order lifecycle state transition
                $this->orderRepo->updatePaymentStatus($orderId, OrderLifecycleState::PAYMENT_REFUNDED);
                $this->orderRepo->updateFulfillmentStatus($orderId, OrderLifecycleState::FULFILLMENT_REVOKED);
                $this->orderRepo->updateOrderStatus($orderId, OrderLifecycleState::STATUS_REFUNDED);
                $this->orderRepo->updateOrderPartialSettlement(
                    $orderId,
                    OrderLifecycleState::STATUS_REFUNDED,
                    OrderLifecycleState::PAYMENT_REFUNDED,
                    OrderLifecycleState::FULFILLMENT_REVOKED,
                    '0.00',
                    $totalPaidAmount
                );

                return $this->refundRepo->findRefund($refundId);
            });
        } catch (Throwable $e) {
            // Post-credit compensation: if local persistence fails, reverse the wallet credit cleanly outside transaction
            try {
                $this->walletService->debit(
                    $userId,
                    $refundAmount,
                    'rev_' . $refId,
                    "Reversal of uncommitted refund on Order #{$order->order_number}",
                    $orderId
                );
            } catch (Throwable) {
            }
            throw $e;
        }
    }

    /**
     * Process a partial order settlement and refund.
     *
     * Invariants & Accounting Rules:
     * - Order Total = $order->total_amount
     * - Amount Received = Sum of completed/paid payments
     * - Total Refunded = Sum of completed refunds (previous + this)
     * - Retained Amount = Amount Received - Total Refunded
     * - Remaining Refundable = max(0, Amount Received - Previous Refunds)
     * - Refund Amount Validation: 0 < Refund Amount <= Remaining Refundable
     * - Order status: partial
     * - Payment status: partially_refunded (or refunded if all received money is refunded)
     * - Fulfillment status: partially_fulfilled
     * - Wallet credit: +Refund Amount
     * - Refund record: created in favorite_digital_refunds with refund_type = 'partial'
     *
     * Supports both calling signatures:
     * 1) ($orderId, $refundAmount, $reason, $method, $reference, $actorUserId)
     * 2) ($orderId, $retainedAmount, $refundAmount, $reason, $method, $reference, $actorUserId)
     */
    public function processPartialRefund(
        int $orderId,
        float|string $param1,
        float|string $param2 = '',
        string $param3 = 'wallet',
        ?string $param4 = null,
        int $param5 = 0,
        float|string|null $param6 = null
    ): ?object {
        $order = $this->orderRepo->findOrderWithItems($orderId);
        if (!$order) {
            throw RefundException::orderNotFound($orderId);
        }

        if ($order->status === OrderLifecycleState::STATUS_CANCELLED) {
            throw new \InvalidArgumentException("Order #{$order->order_number} is cancelled and cannot be partially settled.");
        }
        if ($order->status === OrderLifecycleState::STATUS_REFUNDED || $order->payment_status === OrderLifecycleState::PAYMENT_REFUNDED) {
            throw new \InvalidArgumentException("Order #{$order->order_number} is already fully refunded.");
        }

        // Digital Product orders MUST NOT support partial refund
        $hasDigitalProduct = false;
        $orderItems = $order->items ?? $this->orderRepo->getOrderItems($orderId);
        foreach ($orderItems as $item) {
            $type = (string)($item->product_type ?? '');
            if ($type === ProductType::DIGITAL || $type === 'digital') {
                $hasDigitalProduct = true;
                break;
            }
        }
        if ($hasDigitalProduct) {
            throw new \InvalidArgumentException("Partial refund is not allowed for Digital Product orders.");
        }

        // Detect argument signature
        if (is_numeric($param2) && (float)$param2 >= 0 && (is_string($param2) ? strlen(trim($param2)) > 0 : true)) {
            // Signature 2: ($orderId, $retainedAmount, $refundAmount, $reason, $method, $reference, $actorUserId)
            $explicitRetained = trim((string)$param1);
            $cleanRefund      = trim((string)$param2);
            $cleanReason      = is_string($param3) && !in_array($param3, ['wallet', 'manual', 'manual_bkash', 'manual_nagad', 'manual_rocket', 'manual_bank'], true) ? trim($param3) : '';
            $method           = (is_string($param3) && in_array($param3, ['wallet', 'manual', 'manual_bkash', 'manual_nagad', 'manual_rocket', 'manual_bank'], true)) ? $param3 : (is_string($param4) ? $param4 : 'wallet');
            $reference        = is_string($param4) && !in_array($param4, ['wallet', 'manual'], true) ? $param4 : null;
            $actorUserId      = $param5;
        } else {
            // Signature 1: ($orderId, $refundAmount, $reason, $method, $reference, $actorUserId, $explicitRetained)
            $explicitRetained = $param6 !== null ? trim((string)$param6) : null;
            $cleanRefund      = trim((string)$param1);
            $cleanReason      = trim((string)$param2);
            $method           = (string)$param3;
            $reference        = $param4;
            $actorUserId      = $param5;
        }

        if (!is_numeric($cleanRefund) || (float)$cleanRefund <= 0) {
            throw new \InvalidArgumentException("Refund amount must be a positive number greater than zero.");
        }

        $orderTotalMinor     = (int)round((float)$order->total_amount * 100);
        $refundMinor         = (int)round((float)$cleanRefund * 100);
        $totalReceivedAmount = $this->calculateAuthoritativeRefundAmount($order);
        $totalReceivedMinor  = (int)round((float)$totalReceivedAmount * 100);

        if ($totalReceivedMinor <= 0) {
            throw new \InvalidArgumentException("Order #{$order->order_number} has no verified paid amount to refund.");
        }

        $totalRefundedAmount  = $this->calculateAuthoritativeRefundedAmount($orderId);
        $previousRefundsMinor = (int)round((float)$totalRefundedAmount * 100);

        $cleanRef = trim((string)($reference ?? ''));
        $formattedRefundAmount = number_format($refundMinor / 100, 2, '.', '');

        // Idempotency check across duplicate submissions (check before limit validation)
        $existingRefunds = $this->refundRepo->findRefundsByOrderId($orderId);
        foreach ($existingRefunds as $existing) {
            if ((float)$existing->refund_amount === (float)$formattedRefundAmount &&
                $existing->status === 'completed') {
                if ($cleanRef !== '' && $existing->reference === $cleanRef) {
                    return $existing;
                }
                if ($order->status === OrderLifecycleState::STATUS_PARTIAL &&
                    $order->retained_amount !== null &&
                    $explicitRetained !== null &&
                    (float)$order->retained_amount === (float)number_format((float)$explicitRetained, 2, '.', '')) {
                    return $existing;
                }
            }
        }

        // Refundable amount = max(0, Amount Received - Previous Refunds)
        $refundableMinor = max(0, $totalReceivedMinor - $previousRefundsMinor);
        if ($refundMinor > $refundableMinor) {
            $maxRefundStr = number_format($refundableMinor / 100, 2, '.', '');
            throw new \InvalidArgumentException("Requested refund of " . number_format($refundMinor / 100, 2, '.', '') . " exceeds the maximum refundable amount of {$maxRefundStr}.");
        }

        $newTotalRefundedMinor = $previousRefundsMinor + $refundMinor;

        if ($explicitRetained !== null && is_numeric($explicitRetained)) {
            $retainedMinor = (int)round((float)$explicitRetained * 100);
            if ($retainedMinor < 0) {
                throw new \InvalidArgumentException("Retained amount must be non-negative.");
            }
            if ($retainedMinor > $totalReceivedMinor) {
                throw new \InvalidArgumentException("Retained amount of " . number_format($retainedMinor / 100, 2, '.', '') . " cannot exceed total amount received of {$totalReceivedAmount}.");
            }
            if (($retainedMinor + $newTotalRefundedMinor) > $totalReceivedMinor) {
                throw new \InvalidArgumentException("Total retained amount plus refunds cannot exceed total amount received.");
            }
        } else {
            $retainedMinor = max(0, $totalReceivedMinor - $newTotalRefundedMinor);
        }

        if ($cleanReason === '') {
            $cleanReason = "Partial order settlement refund to customer wallet";
        }

        $userId = (int)$order->user_id;
        if ($userId <= 0) {
            throw RefundException::invalidUserId($userId);
        }

        $walletTxId = null;
        $walletTx = null;
        $destination = ($method === 'manual') ? 'manual' : 'wallet';
        $refSequence = count($existingRefunds) + 1;
        $refId = $cleanRef !== ''
            ? "ref_part_ord_{$orderId}_" . substr(md5($cleanRef), 0, 10)
            : "ref_part_ord_{$orderId}_s{$refSequence}_{$refundMinor}";
        $txDesc = "Partial refund for Order #{$order->order_number}: {$cleanReason}";

        if ($destination === 'wallet') {
            $walletTx = $this->walletService->credit(
                $userId,
                $formattedRefundAmount,
                $refId,
                $txDesc,
                $orderId,
                'refund_credit'
            );
            $walletTxId = (int)$walletTx->id;
        }

        try {
            return $this->executeInTransaction(function () use (
                $order,
                $orderId,
                $userId,
                $retainedMinor,
                $refundMinor,
                $totalReceivedMinor,
                $newTotalRefundedMinor,
                $formattedRefundAmount,
                $cleanReason,
                $cleanRef,
                $refId,
                $method,
                $destination,
                $walletTxId,
                $actorUserId
            ) {
                $now = date('Y-m-d H:i:s');
                $refundId = $this->refundRepo->createRefund([
                    'order_id'              => $orderId,
                    'order_item_id'         => null,
                    'user_id'               => $userId,
                    'refund_amount'         => $formattedRefundAmount,
                    'currency'              => !empty($order->currency) ? strtoupper(trim((string)$order->currency)) : (class_exists(\FavoriteCMS\Core\Currency::class) ? \FavoriteCMS\Core\Currency::getPrimaryCurrency() : 'BDT'),
                    'destination'           => $destination,
                    'refund_method'         => $method,
                    'reference'             => $cleanRef !== '' ? $cleanRef : $refId,
                    'processed_by'          => $actorUserId > 0 ? $actorUserId : null,
                    'refund_type'           => 'partial',
                    'wallet_transaction_id' => $walletTxId,
                    'reason'                => $cleanReason,
                    'status'                => 'completed',
                    'processed_at'          => $now,
                    'created_at'            => $now,
                ]);

                $createdRefund = $this->refundRepo->findRefund($refundId);

                // Determine statuses
                $newTotalRefundedStr  = number_format($newTotalRefundedMinor / 100, 2, '.', '');
                $formattedRetainedStr = number_format($retainedMinor / 100, 2, '.', '');

                if ($newTotalRefundedMinor >= $totalReceivedMinor && $totalReceivedMinor > 0) {
                    $targetStatus     = OrderLifecycleState::STATUS_REFUNDED;
                    $newPaymentStatus = OrderLifecycleState::PAYMENT_REFUNDED;
                    $newFulfillment   = OrderLifecycleState::FULFILLMENT_REVOKED;
                    $this->revokeOrderEntitlements($order);
                } else {
                    $targetStatus     = OrderLifecycleState::STATUS_PARTIAL;
                    $newPaymentStatus = OrderLifecycleState::PAYMENT_PARTIALLY_REFUNDED;
                    $newFulfillment   = OrderLifecycleState::FULFILLMENT_PARTIALLY_FULFILLED;
                }

                $this->orderRepo->updateOrderPartialSettlement(
                    $orderId,
                    $targetStatus,
                    $newPaymentStatus,
                    $newFulfillment,
                    $formattedRetainedStr,
                    $newTotalRefundedStr
                );

                return $createdRefund;
            });
        } catch (Throwable $e) {
            // Post-credit compensation: if local persistence fails, reverse the wallet credit cleanly outside transaction
            if ($destination === 'wallet' && $walletTx !== null) {
                try {
                    $this->walletService->debit(
                        $userId,
                        $formattedRefundAmount,
                        'rev_' . $refId,
                        "Reversal of uncommitted partial refund on Order #{$order->order_number}",
                        $orderId
                    );
                } catch (Throwable) {
                }
            }
            throw $e;
        }
    }

    /**
     * Revoke entitlements associated with order items.
     * Strictly targets purchase and package-derived entitlements for this order only.
     * Independent purchases and unrelated packages are never touched.
     */
    protected function revokeOrderEntitlements(object $order): void
    {
        $orderId = (int)$order->id;
        $userId = (int)$order->user_id;
        $items = $order->items ?? $this->orderRepo->getOrderItems($orderId);

        foreach ($items as $item) {
            $itemId = (int)$item->id;
            $productType = (string)($item->product_type ?? '');

            // Revoke direct purchase entitlement
            $this->entitlementRepo->revokeBySource('purchase', $itemId);

            // Revoke package-derived entitlements (where source_type = 'package' AND source_id = $itemId)
            $this->entitlementRepo->revokeBySource('package', $itemId);

            // Handle membership product type if applicable
            if ($productType === ProductType::MEMBERSHIP) {
                $this->revokeMembershipItemAccess($userId, (int)$item->product_id);
            }
        }
    }

    /**
     * Revoke membership access using MembershipLifecycleService public API.
     */
    protected function revokeMembershipItemAccess(int $userId, int $productId): void
    {
        try {
            $plan = $this->membershipService->getPlanByProductId($productId);
            if (!$plan) {
                return;
            }

            $activeMembership = $this->membershipService->getActiveMembership($userId);
            if ($activeMembership && (int)$activeMembership->plan_id === (int)$plan->id) {
                // Terminate membership using public API
                $this->membershipService->expireMembership((int)$activeMembership->id);
            }
        } catch (Throwable) {
            // Safe fallback if membership plan does not exist
        }
    }

    protected function executeInTransaction(callable $callback): mixed
    {
        if ($this->db === null) {
            return $callback();
        }

        $pdo = null;
        try {
            $pdo = $this->db->getConnection();
        } catch (Throwable) {
        }

        if ($pdo !== null && !$pdo->inTransaction()) {
            $pdo->beginTransaction();
            try {
                $result = $callback();
                $pdo->commit();
                return $result;
            } catch (Throwable $e) {
                $pdo->rollBack();
                throw $e;
            }
        }

        return $callback();
    }
}
