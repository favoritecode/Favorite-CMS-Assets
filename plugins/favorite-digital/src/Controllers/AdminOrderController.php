<?php

declare(strict_types=1);

namespace FavoriteCMS\Digital\Controllers;

use FavoriteCMS\Core\Application;
use FavoriteCMS\Core\Request;
use FavoriteCMS\Core\Response;
use FavoriteCMS\Digital\Domain\ProductType;
use FavoriteCMS\Digital\Services\BulkActionService;
use FavoriteCMS\Digital\Services\DigitalFileStorageService;
use FavoriteCMS\Digital\Services\OrderService;
use FavoriteCMS\Digital\Support\OrderLifecycleState;
use FavoriteCMS\Models\User;
use Throwable;

class AdminOrderController
{
    protected Application $app;
    protected OrderService $orderService;
    protected ?\FavoriteCMS\Digital\Services\FulfillmentService $fulfillmentService;
    protected ?\FavoriteCMS\Digital\Repositories\EntitlementRepository $entitlementRepo;
    protected ?\FavoriteCMS\Digital\Services\RefundService $refundService;
    protected ?DigitalFileStorageService $storageService;
    protected BulkActionService $bulkService;

    public function __construct(
        Application $app,
        OrderService $orderService,
        ?\FavoriteCMS\Digital\Services\FulfillmentService $fulfillmentService = null,
        ?\FavoriteCMS\Digital\Repositories\EntitlementRepository $entitlementRepo = null,
        ?\FavoriteCMS\Digital\Services\RefundService $refundService = null,
        ?DigitalFileStorageService $storageService = null
    ) {
        $this->app = $app;
        $this->orderService = $orderService;
        $this->fulfillmentService = $fulfillmentService;
        $this->entitlementRepo = $entitlementRepo;
        $this->refundService = $refundService;
        $this->storageService = $storageService ?? ($app->has(DigitalFileStorageService::class) ? $app->make(DigitalFileStorageService::class) : new DigitalFileStorageService());
        $this->bulkService = new BulkActionService();
    }

    public function setBulkActionService(BulkActionService $bulkService): void
    {
        $this->bulkService = $bulkService;
    }

    public function getOrderService(): OrderService
    {
        return $this->orderService;
    }

    public function getRefundService(): ?\FavoriteCMS\Digital\Services\RefundService
    {
        return $this->refundService;
    }

    public function handle(Request $request): Response|string
    {
        // 1. Authenticate user
        $userId = (int)($_SESSION['auth_user_id'] ?? 0);
        if ($userId <= 0 && !isset($GLOBALS['_test_current_user'])) {
            return Response::redirect('/admin/login');
        }

        $currentUser = $this->resolveCurrentUser($userId);
        if ($currentUser && method_exists($currentUser, 'isActive') && !$currentUser->isActive()) {
            return Response::make('<h1>403 Access Denied</h1><p>Your account is inactive or banned.</p>', 403);
        }

        // 2. Authorize capability
        if ($currentUser && method_exists($currentUser, 'can') && !$currentUser->can('manage_options')) {
            return Response::make('<h1>403 Access Denied</h1><p>You do not have permission to manage orders.</p>', 403);
        } elseif (!$currentUser && function_exists('current_user_can')) {
            try {
                if (!current_user_can('manage_options')) {
                    return Response::make('<h1>403 Access Denied</h1><p>You do not have permission to manage orders.</p>', 403);
                }
            } catch (Throwable) {
            }
        }

        // 3. Dispatch based on method & action
        if ($request->method() === 'POST') {
            return $this->handlePost($request);
        }

        return $this->handleGet($request);
    }

    protected function handleGet(Request $request): Response|string
    {
        $action = (string)$request->get('action', 'index');
        $id = (int)$request->get('id', 0);

        return match ($action) {
            'view'  => $this->viewOrder($request, $id),
            default => $this->index($request),
        };
    }

    protected function handlePost(Request $request): Response
    {
        if (!$this->validateCsrf($request)) {
            $_SESSION['flash_error'] = 'Security token expired or invalid (CSRF failure). Please try again.';
            $orderId = (int)$request->post('id', 0);
            $redirect = $orderId > 0
                ? '/admin/page/favorite-digital-orders?action=view&id=' . $orderId
                : '/admin/page/favorite-digital-orders';
            return Response::redirect($redirect);
        }

        $action = (string)$request->post('action', '');
        $id = (int)$request->post('id', 0);

        return match ($action) {
            'accept_order', 'accept_manual_order', 'confirm_payment' => $this->acceptOrder($request, $id),
            'update_service_status'      => $this->updateServiceStatus($request, $id),
            'cancel_order'               => $this->cancelOrder($request, $id),
            'update_status'              => $this->updateStatus($request, $id),
            'fulfill'                    => $this->fulfillOrder($request, $id),
            'refund', 'process_refund'   => $this->processRefund($request, $id),
            'record_partial_settlement', 'record_partial_refund', 'partial_settlement', 'partial_refund' => $this->recordPartialSettlement($request, $id),
            'add_deliverable'            => $this->addDeliverable($request, $id),
            'toggle_deliverable_release' => $this->toggleDeliverableRelease($request, $id),
            'delete_deliverable'         => $this->deleteDeliverable($request, $id),
            'bulk_action'                => $this->handleBulkAction($request),
            default                      => Response::redirect('/admin/page/favorite-digital-orders'),
        };
    }

    public function index(Request $request): string
    {
        $statusFilter      = (string)$request->get('status', 'all');
        $paymentFilter     = (string)$request->get('payment_status', 'all');
        $fulfillmentFilter = (string)$request->get('fulfillment_status', 'all');
        $search            = trim((string)$request->get('search', ''));
        $page              = max(1, (int)$request->get('page', 1));

        $filters = [];
        if ($statusFilter !== 'all' && OrderLifecycleState::isValidStatus($statusFilter)) {
            $filters['status'] = $statusFilter;
        }
        if ($paymentFilter !== 'all' && OrderLifecycleState::isValidPaymentStatus($paymentFilter)) {
            $filters['payment_status'] = $paymentFilter;
        }
        if ($fulfillmentFilter !== 'all' && OrderLifecycleState::isValidFulfillmentStatus($fulfillmentFilter)) {
            $filters['fulfillment_status'] = $fulfillmentFilter;
        }
        if ($search !== '') {
            $filters['search'] = $search;
        }

        $result = $this->orderService->listOrders($filters, $page, 20);

        return $this->renderView('orders/index', [
            'orders'            => $result['data'],
            'total'             => $result['total'],
            'page'              => $result['page'],
            'totalPages'        => $result['total_pages'],
            'statusFilter'      => $statusFilter,
            'paymentFilter'     => $paymentFilter,
            'fulfillmentFilter' => $fulfillmentFilter,
            'search'            => $search,
            'csrfToken'         => $this->getCsrfToken(),
            'flashSuccess'      => $_SESSION['flash_success'] ?? null,
            'flashError'        => $_SESSION['flash_error'] ?? null,
        ]);
    }

    public function viewOrder(Request $request, int $id): Response|string
    {
        $order = null;
        if ($id > 0) {
            $order = $this->orderService->getOrder($id);
        } else {
            $orderNumber = trim((string)$request->get('order_number', ''));
            if ($orderNumber !== '') {
                $order = $this->orderService->getOrderByNumber($orderNumber);
            }
        }

        if (!$order) {
            $_SESSION['flash_error'] = 'Order not found.';
            return Response::redirect('/admin/page/favorite-digital-orders');
        }

        $entitlements = [];
        if ($this->entitlementRepo !== null) {
            $entitlements = $this->entitlementRepo->getEntitlementsByOrder((int)$order->id);
        }

        $refunds = [];
        if ($this->refundService !== null) {
            $refunds = $this->refundService->getRefundRepository()->findRefundsByOrderId((int)$order->id);
        }

        $totalReceived = '0.00';
        $totalRefunded = '0.00';
        if ($this->refundService !== null) {
            $totalReceived = $this->refundService->calculateAuthoritativeRefundAmount($order);
            $totalRefunded = $this->refundService->calculateAuthoritativeRefundedAmount((int)$order->id);
        } else {
            $settledMinor = 0;
            foreach ($order->payments ?? [] as $p) {
                if ($p->status === 'completed' || $p->status === 'paid') {
                    $settledMinor += (int)round((float)$p->amount_paid * 100);
                }
            }
            $totalReceived = number_format($settledMinor / 100, 2, '.', '');
        }

        $orderTotalMinor      = (int)round((float)$order->total_amount * 100);
        $totalReceivedMinor   = (int)round((float)$totalReceived * 100);
        $totalRefundedMinor   = (int)round((float)$totalRefunded * 100);
        $netRetainedMinor     = max(0, $totalReceivedMinor - $totalRefundedMinor);
        $remainingUnpaidMinor = max(0, $orderTotalMinor - $totalReceivedMinor);

        $retainedAmount  = $order->retained_amount ?? null;
        $retainedMinor   = ($retainedAmount !== null) ? (int)round((float)$retainedAmount * 100) : $netRetainedMinor;
        $refundableMinor = max(0, $totalReceivedMinor - $totalRefundedMinor);

        $accounting = [
            'order_total'       => number_format($orderTotalMinor / 100, 2, '.', ''),
            'total_received'    => number_format($totalReceivedMinor / 100, 2, '.', ''),
            'total_refunded'    => number_format($totalRefundedMinor / 100, 2, '.', ''),
            'net_retained'      => number_format($netRetainedMinor / 100, 2, '.', ''),
            'remaining_unpaid'  => number_format($remainingUnpaidMinor / 100, 2, '.', ''),
            'retained_amount'   => ($retainedAmount !== null) ? number_format($retainedMinor / 100, 2, '.', '') : null,
            'refundable_amount' => number_format($refundableMinor / 100, 2, '.', ''),
        ];

        $deliverables = [];
        try {
            $deliverables = $this->orderService->getOrderRepository()->getDeliverablesByOrderId($id, false);
        } catch (\Throwable) {
        }

        return $this->renderView('orders/view', [
            'order'        => $order,
            'entitlements' => $entitlements,
            'refunds'      => $refunds,
            'accounting'   => $accounting,
            'deliverables' => $deliverables,
            'csrfToken'    => $this->getCsrfToken(),
            'flashSuccess' => $_SESSION['flash_success'] ?? null,
            'flashError'   => $_SESSION['flash_error'] ?? null,
        ]);
    }

    public function updateStatus(Request $request, int $id): Response
    {
        $order = $this->orderService->getOrder($id);
        if (!$order) {
            $_SESSION['flash_error'] = 'Order not found.';
            return Response::redirect('/admin/page/favorite-digital-orders');
        }

        // Terminal state guards: Refunded and Cancelled orders cannot be modified
        if ($order->status === OrderLifecycleState::STATUS_REFUNDED) {
            $_SESSION['flash_error'] = 'Order is already fully refunded and its status cannot be modified.';
            return Response::redirect('/admin/page/favorite-digital-orders?action=view&id=' . $id);
        }
        if ($order->status === OrderLifecycleState::STATUS_CANCELLED) {
            $_SESSION['flash_error'] = 'Order is cancelled and its status cannot be modified.';
            return Response::redirect('/admin/page/favorite-digital-orders?action=view&id=' . $id);
        }

        $rawStatus = trim((string)$request->post('status', ''));
        $targetStatus = match (strtolower($rawStatus)) {
            'complete' => OrderLifecycleState::STATUS_COMPLETED,
            'refund'   => OrderLifecycleState::STATUS_REFUNDED,
            default    => strtolower($rawStatus),
        };

        // Favorite Digital dropdown strictly allows only: Processing, Partial, Complete, Refund.
        // Pending and Cancel are payment-controlled states and are strictly NOT allowed in updateStatus.
        if (!OrderLifecycleState::isSelectableAdminStatus($targetStatus)) {
            $_SESSION['flash_error'] = 'Invalid status option. Order status can only be Processing, Partial, Complete, or Refund.';
            return Response::redirect('/admin/page/favorite-digital-orders?action=view&id=' . $id);
        }

        try {
            // 1. Partial Refund Workflow
            if ($targetStatus === OrderLifecycleState::STATUS_PARTIAL) {
                $hasService = false;
                $items = $order->items ?? $this->orderService->getOrderRepository()->getOrderItems($id);
                foreach ($items as $it) {
                    if ((string)($it->product_type ?? '') === ProductType::SERVICE) {
                        $hasService = true;
                        break;
                    }
                }

                // Digital Product: Partial refund is STRICTLY FORBIDDEN
                if (!$hasService) {
                    $_SESSION['flash_error'] = 'Partial refund is not allowed for Digital Product orders.';
                    return Response::redirect('/admin/page/favorite-digital-orders?action=view&id=' . $id);
                }

                if ($this->refundService === null) {
                    $_SESSION['flash_error'] = 'Refund service is unavailable.';
                    return Response::redirect('/admin/page/favorite-digital-orders?action=view&id=' . $id);
                }

                $refundAmount = (float)$request->post('refund_amount', 0);
                $refundReason = trim((string)$request->post('refund_reason', 'Partial order settlement refund'));
                if ($refundReason === '') {
                    $refundReason = 'Partial order settlement refund';
                }

                if ($refundAmount <= 0) {
                    $_SESSION['flash_error'] = 'Please enter a valid refund amount greater than 0 for partial refund.';
                    return Response::redirect('/admin/page/favorite-digital-orders?action=view&id=' . $id);
                }

                $totalReceived = (float)$this->refundService->calculateAuthoritativeRefundAmount($order);
                $totalRefunded = (float)$this->refundService->calculateAuthoritativeRefundedAmount($id);
                $maxRefundable = max(0.0, $totalReceived - $totalRefunded);

                $orderSymbol = !empty($order->currency_symbol) ? $order->currency_symbol : '৳';
                if ($refundAmount > $maxRefundable) {
                    $_SESSION['flash_error'] = "Refund amount cannot exceed the remaining refundable amount of {$orderSymbol}" . number_format($maxRefundable, 2, '.', '') . ".";
                    return Response::redirect('/admin/page/favorite-digital-orders?action=view&id=' . $id);
                }

                $actorUserId = (int)($_SESSION['auth_user_id'] ?? 0);
                if (isset($GLOBALS['_test_current_user']->id)) {
                    $actorUserId = (int)$GLOBALS['_test_current_user']->id;
                }

                $this->refundService->processPartialRefund(
                    $id,
                    (string)$refundAmount,
                    $refundReason,
                    'wallet',
                    null,
                    $actorUserId
                );

                $_SESSION['flash_success'] = "Partial refund of {$refundAmount} BDT processed successfully.";
                return Response::redirect('/admin/page/favorite-digital-orders?action=view&id=' . $id);
            }

            // 2. Full Refund Workflow
            if ($targetStatus === OrderLifecycleState::STATUS_REFUNDED) {
                if ($this->refundService === null) {
                    $_SESSION['flash_error'] = 'Refund service is unavailable.';
                    return Response::redirect('/admin/page/favorite-digital-orders?action=view&id=' . $id);
                }

                $reason = trim((string)$request->post('full_refund_reason', ''));
                if ($reason === '') {
                    $reason = trim((string)$request->post('refund_reason', 'Order refund requested by administrator'));
                }
                if ($reason === '') {
                    $reason = 'Order refund requested by administrator';
                }

                $totalReceived = (float)$this->refundService->calculateAuthoritativeRefundAmount($order);
                $totalRefunded = (float)$this->refundService->calculateAuthoritativeRefundedAmount($id);
                $refundable = max(0.0, $totalReceived - $totalRefunded);

                if ($refundable <= 0) {
                    $_SESSION['flash_error'] = 'Order has no verified refundable balance to refund.';
                    return Response::redirect('/admin/page/favorite-digital-orders?action=view&id=' . $id);
                }

                $actorUserId = (int)($_SESSION['auth_user_id'] ?? 0);
                if (isset($GLOBALS['_test_current_user']->id)) {
                    $actorUserId = (int)$GLOBALS['_test_current_user']->id;
                }

                $this->refundService->processRefund($id, $reason, $actorUserId, true);
                $_SESSION['flash_success'] = 'Full order refund processed. Customer wallet credited and access revoked.';
                return Response::redirect('/admin/page/favorite-digital-orders?action=view&id=' . $id);
            }

            // 3. Complete Workflow
            if ($targetStatus === OrderLifecycleState::STATUS_COMPLETED) {
                if ($order->payment_status !== OrderLifecycleState::PAYMENT_PAID) {
                    $_SESSION['flash_error'] = 'Cannot set order to Complete before payment is confirmed.';
                    return Response::redirect('/admin/page/favorite-digital-orders?action=view&id=' . $id);
                }

                $this->orderService->updateStatus($id, OrderLifecycleState::STATUS_COMPLETED);
                $this->orderService->updateFulfillmentStatus($id, OrderLifecycleState::FULFILLMENT_FULFILLED);

                if ($this->fulfillmentService !== null) {
                    $this->fulfillmentService->fulfillOrder($id, OrderLifecycleState::STATUS_COMPLETED);
                }

                $_SESSION['flash_success'] = 'Order marked as Complete and fulfilled successfully.';
                return Response::redirect('/admin/page/favorite-digital-orders?action=view&id=' . $id);
            }

            // 4. Processing Workflow
            if ($targetStatus === OrderLifecycleState::STATUS_PROCESSING) {
                $this->orderService->updateStatus($id, OrderLifecycleState::STATUS_PROCESSING);
                if ($order->payment_status === OrderLifecycleState::PAYMENT_PAID) {
                    $this->orderService->updateFulfillmentStatus($id, OrderLifecycleState::FULFILLMENT_PARTIALLY_FULFILLED);
                }

                $_SESSION['flash_success'] = 'Order status set to Processing.';
                return Response::redirect('/admin/page/favorite-digital-orders?action=view&id=' . $id);
            }
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = 'Failed to update order status: ' . $e->getMessage();
        }

        return Response::redirect('/admin/page/favorite-digital-orders?action=view&id=' . $id);
    }

    /**
     * Accept a single order (settle payment, fulfill, and update status).
     */
    public function acceptSingleOrder(int $id, string $chosenStatus = 'auto'): string
    {
        $order = $this->orderService->getOrder($id);
        if (!$order) {
            throw new \InvalidArgumentException("Order #{$id} not found.");
        }

        if ($order->status === OrderLifecycleState::STATUS_CANCELLED) {
            throw new \InvalidArgumentException("Order #{$order->order_number} is already cancelled and cannot be transitioned.");
        }
        if ($order->status === OrderLifecycleState::STATUS_REFUNDED) {
            throw new \InvalidArgumentException("Order #{$order->order_number} is already refunded and cannot be transitioned.");
        }

        $orderRepo = $this->orderService->getOrderRepository();

        $hasService = false;
        $orderItems = $order->items ?? $orderRepo->getOrderItems($id);
        foreach ($orderItems as $it) {
            if ((string)($it->product_type ?? '') === ProductType::SERVICE) {
                $hasService = true;
                break;
            }
        }

        if (empty($chosenStatus) || $chosenStatus === 'auto' || $chosenStatus === 'pending') {
            $chosenStatus = $hasService ? OrderLifecycleState::STATUS_PROCESSING : OrderLifecycleState::STATUS_COMPLETED;
        } elseif ($chosenStatus === 'complete') {
            $chosenStatus = OrderLifecycleState::STATUS_COMPLETED;
        }

        // 1. Settle all pending payments as completed
        $payments = $orderRepo->getOrderPayments($id);
        $hasPaid = false;
        foreach ($payments as $pay) {
            if ($pay->status === 'pending') {
                $orderRepo->updatePayment((int)$pay->id, ['status' => 'completed']);
                $hasPaid = true;
            } elseif ($pay->status === 'completed' || $pay->status === 'paid') {
                $hasPaid = true;
            }
        }

        if (!$hasPaid && (float)$order->total_amount > 0) {
            $orderRepo->createOrderPayment([
                'order_id'           => $id,
                'payment_method'     => 'manual',
                'favorite_pay_tx_id' => null,
                'wallet_tx_id'       => null,
                'amount_paid'        => $order->total_amount,
                'currency'           => $order->currency ?? (class_exists(\FavoriteCMS\Core\Currency::class) ? \FavoriteCMS\Core\Currency::getPrimaryCurrency() : 'BDT'),
                'status'             => 'completed',
                'created_at'         => date('Y-m-d H:i:s'),
                'updated_at'         => date('Y-m-d H:i:s'),
            ]);
        }

        // 2. Mark order payment_status as paid
        $orderRepo->updatePaymentStatus($id, OrderLifecycleState::PAYMENT_PAID);

        // 3. Fulfill items and set the chosen status
        if ($this->fulfillmentService !== null) {
            $this->fulfillmentService->fulfillOrder($id, $chosenStatus);
        } else {
            $orderRepo->updateOrderStatus($id, $chosenStatus);
            if ($chosenStatus === 'completed') {
                $orderRepo->updateFulfillmentStatus($id, OrderLifecycleState::FULFILLMENT_FULFILLED);
            } elseif ($chosenStatus === 'processing') {
                $orderRepo->updateFulfillmentStatus($id, OrderLifecycleState::FULFILLMENT_PARTIALLY_FULFILLED);
            } else {
                $orderRepo->updateFulfillmentStatus($id, OrderLifecycleState::FULFILLMENT_UNFULFILLED);
            }
        }

        // 4. Update retained amount = total_amount, refunded amount = 0.00
        $orderRepo->updateOrderPartialSettlement(
            $id,
            $chosenStatus,
            OrderLifecycleState::PAYMENT_PAID,
            ($chosenStatus === 'completed') ? OrderLifecycleState::FULFILLMENT_FULFILLED : OrderLifecycleState::FULFILLMENT_PARTIALLY_FULFILLED,
            $order->total_amount,
            '0.00'
        );

        $displayStatus = ucfirst($chosenStatus);
        return "Order #{$order->order_number} confirmed and set to {$displayStatus}.";
    }

    /**
     * Cancel a single order without refunding to wallet (Cancel != Refund).
     *
     * Fake/unverified/unwanted order:
     * - Payment -> failed
     * - Order -> cancelled
     * - Fulfillment -> cancelled
     * - Retained = 0.00, Refunded = 0.00
     * - Zero refund, zero wallet credit, no refund record created.
     */
    public function cancelSingleOrder(int $id, string $reason = 'Order cancelled by administrator.', ?int $userId = null): string
    {
        $order = $this->orderService->getOrder($id);
        if (!$order) {
            throw new \InvalidArgumentException("Order #{$id} not found.");
        }

        if ($order->status === OrderLifecycleState::STATUS_CANCELLED) {
            throw new \InvalidArgumentException("Order #{$order->order_number} is already cancelled.");
        }
        if ($order->status === OrderLifecycleState::STATUS_REFUNDED) {
            throw new \InvalidArgumentException("Order #{$order->order_number} is already refunded and cannot be cancelled.");
        }

        $orderRepo = $this->orderService->getOrderRepository();

        // 1. Mark pending payments as failed
        $payments = $orderRepo->getOrderPayments($id);
        foreach ($payments as $pay) {
            if ($pay->status === 'pending') {
                $orderRepo->updatePayment((int)$pay->id, ['status' => 'failed']);
            }
        }

        // 2. Set statuses: payment = failed, fulfillment = cancelled, aggregate = cancelled
        $orderRepo->updatePaymentStatus($id, OrderLifecycleState::PAYMENT_FAILED);
        $orderRepo->updateFulfillmentStatus($id, OrderLifecycleState::FULFILLMENT_CANCELLED);
        $orderRepo->updateOrderStatus($id, OrderLifecycleState::STATUS_CANCELLED);

        // 3. Update accounting settlement: 0 retained, 0 refunded
        $orderRepo->updateOrderPartialSettlement(
            $id,
            OrderLifecycleState::STATUS_CANCELLED,
            OrderLifecycleState::PAYMENT_FAILED,
            OrderLifecycleState::FULFILLMENT_CANCELLED,
            '0.00',
            '0.00'
        );

        return "Order #{$order->order_number} has been cancelled. No refund or wallet credit issued.";
    }

    public function acceptOrder(Request $request, int $id): Response
    {
        $chosenStatus = trim((string)$request->post('chosen_status', 'auto'));

        try {
            $msg = $this->acceptSingleOrder($id, $chosenStatus);
            $_SESSION['flash_success'] = $msg;
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = 'Failed to accept order: ' . $e->getMessage();
        }

        return Response::redirect('/admin/page/favorite-digital-orders?action=view&id=' . $id);
    }

    public function updateServiceStatus(Request $request, int $id): Response
    {
        $order = $this->orderService->getOrder($id);
        if (!$order) {
            $_SESSION['flash_error'] = 'Order not found.';
            return Response::redirect('/admin/page/favorite-digital-orders');
        }

        $chosenStatus = trim((string)$request->post('chosen_status', 'pending'));
        if (!in_array($chosenStatus, ['pending', 'processing', 'completed', 'complete'], true)) {
            $chosenStatus = 'pending';
        }
        if ($chosenStatus === 'complete') {
            $chosenStatus = 'completed';
        }

        try {
            $orderRepo = $this->orderService->getOrderRepository();
            $orderRepo->updateOrderStatus($id, $chosenStatus);

            if ($chosenStatus === 'completed') {
                $orderRepo->updateFulfillmentStatus($id, OrderLifecycleState::FULFILLMENT_FULFILLED);
            } elseif ($chosenStatus === 'processing') {
                $orderRepo->updateFulfillmentStatus($id, OrderLifecycleState::FULFILLMENT_PARTIALLY_FULFILLED);
            } else {
                $orderRepo->updateFulfillmentStatus($id, OrderLifecycleState::FULFILLMENT_UNFULFILLED);
            }

            $displayStatus = ucfirst($chosenStatus);
            $_SESSION['flash_success'] = "Service order status updated to {$displayStatus} successfully.";
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = 'Failed to update service status: ' . $e->getMessage();
        }

        return Response::redirect('/admin/page/favorite-digital-orders?action=view&id=' . $id);
    }

    public function cancelOrder(Request $request, int $id): Response
    {
        $reason = trim((string)$request->post('cancel_reason', 'Order cancelled by administrator.'));
        if ($reason === '') {
            $reason = 'Order cancelled by administrator.';
        }

        $userId = (int)($_SESSION['auth_user_id'] ?? 0);
        if (isset($GLOBALS['_test_current_user']->id)) {
            $userId = (int)$GLOBALS['_test_current_user']->id;
        }

        try {
            $msg = $this->cancelSingleOrder($id, $reason, $userId);
            $_SESSION['flash_success'] = $msg;
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = 'Failed to cancel order: ' . $e->getMessage();
        }

        return Response::redirect('/admin/page/favorite-digital-orders?action=view&id=' . $id);
    }

    /**
     * Process bulk actions for orders.
     */
    public function handleBulkAction(Request $request): Response
    {
        return $this->bulkService->handle($request, [
            'processing' => fn(int $id) => $this->acceptSingleOrder($id, 'processing'),
            'completed'  => fn(int $id) => $this->acceptSingleOrder($id, 'completed'),
            'cancelled'  => fn(int $id) => $this->cancelSingleOrder($id, 'Bulk order cancellation by administrator.'),
        ], '/admin/page/favorite-digital-orders', [
            'processing' => 'Mark Processing',
            'completed'  => 'Mark Completed',
            'cancelled'  => 'Cancellation',
        ]);
    }

    protected function validateCsrf(Request $request): bool
    {
        $submittedToken = (string)$request->post('_token', '');
        $sessionToken   = (string)($_SESSION['_token'] ?? '');

        if ($submittedToken === '' || $sessionToken === '') {
            return false;
        }

        return hash_equals($sessionToken, $submittedToken);
    }

    protected function getCsrfToken(): string
    {
        $token = (string)($_SESSION['_token'] ?? '');
        if ($token === '' && function_exists('csrf_token')) {
            $token = csrf_token();
        }
        return $token;
    }

    protected function renderView(string $viewName, array $data = []): string
    {
        $viewPath = __DIR__ . '/../../views/admin/' . $viewName . '.php';
        if (!file_exists($viewPath)) {
            return "<div class='notice notice-error'>View not found: " . htmlspecialchars($viewName, ENT_QUOTES, 'UTF-8') . "</div>";
        }

        extract($data, EXTR_SKIP);
        ob_start();
        include $viewPath;
        return (string)ob_get_clean();
    }

    protected function resolveCurrentUser(int $userId): ?object
    {
        if (isset($GLOBALS['_test_current_user'])) {
            return $GLOBALS['_test_current_user'];
        }

        if (class_exists(User::class)) {
            try {
                return User::find($userId);
            } catch (Throwable) {
                return null;
            }
        }

        return null;
    }

    public function fulfillOrder(Request $request, int $id): Response
    {
        $order = $this->orderService->getOrder($id);
        if (!$order) {
            $_SESSION['flash_error'] = 'Order not found.';
            return Response::redirect('/admin/page/favorite-digital-orders');
        }

        if ($this->fulfillmentService === null) {
            $_SESSION['flash_error'] = 'Fulfillment service is unavailable.';
            return Response::redirect('/admin/page/favorite-digital-orders?action=view&id=' . $id);
        }

        try {
            $this->fulfillmentService->fulfillOrder($id);
            $_SESSION['flash_success'] = "Order #{$order->order_number} fulfilled successfully.";
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = "Fulfillment failed: " . $e->getMessage();
        }

        return Response::redirect('/admin/page/favorite-digital-orders?action=view&id=' . $id);
    }

    public function processRefund(Request $request, int $id): Response
    {
        $order = $this->orderService->getOrder($id);
        if (!$order) {
            $_SESSION['flash_error'] = 'Order not found.';
            return Response::redirect('/admin/page/favorite-digital-orders');
        }

        if ($this->refundService === null) {
            $_SESSION['flash_error'] = 'Refund service is unavailable.';
            return Response::redirect('/admin/page/favorite-digital-orders?action=view&id=' . $id);
        }

        $reason = trim((string)$request->post('reason', ''));
        if ($reason === '') {
            $_SESSION['flash_error'] = 'A refund reason must be provided.';
            return Response::redirect('/admin/page/favorite-digital-orders?action=view&id=' . $id);
        }

        $actorUserId = (int)($_SESSION['auth_user_id'] ?? 0);
        if (isset($GLOBALS['_test_current_user']->id)) {
            $actorUserId = (int)$GLOBALS['_test_current_user']->id;
        }

        try {
            $refund = $this->refundService->processRefund($id, $reason, $actorUserId, true);
            $sym = class_exists(\FavoriteCMS\Core\Currency::class) ? \FavoriteCMS\Core\Currency::getSymbol($refund->currency ?? null) : '৳';
            $_SESSION['flash_success'] = "Refund of {$sym}{$refund->refund_amount} processed successfully to customer wallet.";
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = "Refund failed: " . $e->getMessage();
        }

        return Response::redirect('/admin/page/favorite-digital-orders?action=view&id=' . $id);
    }

    public function recordPartialSettlement(Request $request, int $id): Response
    {
        $order = $this->orderService->getOrder($id);
        if (!$order) {
            $_SESSION['flash_error'] = 'Order not found.';
            return Response::redirect('/admin/page/favorite-digital-orders');
        }

        if ($this->refundService === null) {
            $_SESSION['flash_error'] = 'Refund service is unavailable.';
            return Response::redirect('/admin/page/favorite-digital-orders?action=view&id=' . $id);
        }

        $hasDigitalProduct = false;
        foreach ($order->items ?? [] as $it) {
            $type = (string)($it->product_type ?? '');
            if ($type === ProductType::DIGITAL || $type === 'digital') {
                $hasDigitalProduct = true;
                break;
            }
        }

        if ($hasDigitalProduct) {
            $_SESSION['flash_error'] = "Partial refund is not allowed for Digital Product orders.";
            return Response::redirect('/admin/page/favorite-digital-orders?action=view&id=' . $id);
        }

        $refundAmount   = trim((string)$request->post('refund_amount', ''));
        $retainedAmount = trim((string)$request->post('retained_amount', ''));
        $refundMethod   = trim((string)$request->post('refund_method', 'wallet'));
        $reference      = trim((string)$request->post('refund_reference', ''));
        $reason         = trim((string)$request->post('refund_reason', ''));

        $actorUserId = (int)($_SESSION['auth_user_id'] ?? 0);
        if (isset($GLOBALS['_test_current_user']->id)) {
            $actorUserId = (int)$GLOBALS['_test_current_user']->id;
        }

        try {
            $refund = $this->refundService->processPartialRefund(
                $id,
                $refundAmount,
                $reason,
                $refundMethod,
                $reference !== '' ? $reference : null,
                $actorUserId,
                $retainedAmount !== '' ? $retainedAmount : null
            );

            $sym = class_exists(\FavoriteCMS\Core\Currency::class) ? \FavoriteCMS\Core\Currency::getSymbol($order->currency ?? null) : '৳';
            $refundStr = number_format((float)$refundAmount, 2, '.', '');

            $_SESSION['flash_success'] = "Partial settlement processed successfully. Refund of {$sym}{$refundStr} credited to customer wallet. Order status updated to Partial.";
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = "Partial settlement failed: " . $e->getMessage();
            if ($e->getMessage() === "Partial refund is not allowed for Digital Product orders.") {
                $_SESSION['flash_error'] = $e->getMessage();
            } else {
                $_SESSION['flash_error'] = "Partial settlement failed: " . $e->getMessage();
            }
        }

        return Response::redirect('/admin/page/favorite-digital-orders?action=view&id=' . $id);
    }

    public function addDeliverable(Request $request, int $orderId): Response
    {
        $orderRepo = $this->orderService->getOrderRepository();
        $order = $orderRepo->findOrder($orderId);
        if (!$order) {
            $_SESSION['flash_error'] = 'Order not found.';
            return Response::redirect('/admin/page/favorite-digital-orders');
        }

        $title = trim((string)$request->post('deliverable_title', ''));
        if ($title === '') {
            $_SESSION['flash_error'] = 'Deliverable title is required.';
            return Response::redirect('/admin/page/favorite-digital-orders?action=view&id=' . $orderId);
        }

        $resType = strtolower(trim((string)$request->post('resource_type', 'file')));
        if (!in_array($resType, ['file', 'url'], true)) {
            $resType = 'file';
        }

        $isReleased = !empty($request->post('is_released')) ? 1 : 0;
        $token = bin2hex(random_bytes(32));

        $filePath = null;
        $fileName = null;
        $fileHash = null;
        $fileSize = 0;
        $mimeType = null;
        $resourceUrl = null;

        try {
            if ($resType === 'file') {
                $uploadedFile = $_FILES['deliverable_file'] ?? null;
                if (!$uploadedFile || (isset($uploadedFile['error']) && $uploadedFile['error'] === UPLOAD_ERR_NO_FILE)) {
                    $_SESSION['flash_error'] = 'Please select a file to upload for the deliverable.';
                    return Response::redirect('/admin/page/favorite-digital-orders?action=view&id=' . $orderId);
                }

                $stored = $this->storageService->storeUpload($uploadedFile);
                $filePath = $stored['file_path'];
                $fileName = $stored['file_name'];
                $fileHash = $stored['file_hash'];
                $fileSize = (int)$stored['file_size'];
                $mimeType = $stored['mime_type'];
            } else {
                $rawUrl = (string)$request->post('resource_url', '');
                $resourceUrl = $this->storageService->validateSafeUrl($rawUrl);

                $manualVal = $request->post('manual_file_size_val', null);
                $manualUnit = (string)$request->post('manual_file_size_unit', 'MB');
                if ($manualVal !== null && $manualVal !== '' && is_numeric($manualVal)) {
                    $num = (float)$manualVal;
                    if ($num >= 0) {
                        $fileSize = match (strtoupper(trim($manualUnit))) {
                            'GB' => (int)round($num * 1024 * 1024 * 1024),
                            'KB' => (int)round($num * 1024),
                            'B'  => (int)round($num),
                            default => (int)round($num * 1024 * 1024),
                        };
                    }
                }
            }

            $orderRepo->createDeliverable([
                'order_id'       => $orderId,
                'order_item_id'  => null,
                'title'          => $title,
                'description'    => trim((string)$request->post('description', '')),
                'resource_type'  => $resType,
                'file_path'      => $filePath,
                'file_name'      => $fileName,
                'file_hash'      => $fileHash,
                'file_size'      => $fileSize,
                'mime_type'      => $mimeType,
                'resource_url'   => $resourceUrl,
                'download_token' => $token,
                'is_released'    => $isReleased,
            ]);

            $_SESSION['flash_success'] = 'Service deliverable added successfully.' . ($isReleased ? ' Deliverable is now accessible to customer.' : ' Saved as unreleased draft.');
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = 'Failed to add deliverable: ' . $e->getMessage();
        }

        return Response::redirect('/admin/page/favorite-digital-orders?action=view&id=' . $orderId);
    }

    public function toggleDeliverableRelease(Request $request, int $orderId): Response
    {
        $deliverableId = (int)$request->post('deliverable_id', 0);
        $orderRepo = $this->orderService->getOrderRepository();
        $deliv = $orderRepo->findDeliverable($deliverableId);
        if (!$deliv || (int)$deliv->order_id !== $orderId) {
            $_SESSION['flash_error'] = 'Deliverable not found.';
            return Response::redirect('/admin/page/favorite-digital-orders?action=view&id=' . $orderId);
        }

        $newStatus = ((int)$deliv->is_released === 1) ? 0 : 1;
        $orderRepo->updateDeliverable($deliverableId, ['is_released' => $newStatus]);

        $_SESSION['flash_success'] = $newStatus === 1 ? 'Deliverable released to customer.' : 'Deliverable marked as unreleased draft.';
        return Response::redirect('/admin/page/favorite-digital-orders?action=view&id=' . $orderId);
    }

    public function deleteDeliverable(Request $request, int $orderId): Response
    {
        $deliverableId = (int)$request->post('deliverable_id', 0);
        $orderRepo = $this->orderService->getOrderRepository();
        $deliv = $orderRepo->findDeliverable($deliverableId);
        if (!$deliv || (int)$deliv->order_id !== $orderId) {
            $_SESSION['flash_error'] = 'Deliverable not found.';
            return Response::redirect('/admin/page/favorite-digital-orders?action=view&id=' . $orderId);
        }

        $orderRepo->deleteDeliverable($deliverableId);
        $_SESSION['flash_success'] = 'Deliverable deleted successfully.';
        return Response::redirect('/admin/page/favorite-digital-orders?action=view&id=' . $orderId);
    }
}
