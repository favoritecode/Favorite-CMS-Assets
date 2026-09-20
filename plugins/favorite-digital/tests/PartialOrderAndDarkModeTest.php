<?php

declare(strict_types=1);

namespace FavoriteCMS\Digital\Tests;

use CreateFavoriteDigitalProductsTable;
use CreateFavoriteDigitalProductDetailsTable;
use CreateFavoriteDigitalServiceDetailsTable;
use CreateFavoriteDigitalEntitlementsTable;
use CreateFavoriteDigitalDownloadsTable;
use CreateFavoriteDigitalOrderItemsTable;
use CreateFavoriteDigitalOrderPaymentsTable;
use CreateFavoriteDigitalOrdersTable;
use CreateFavoriteDigitalRefundsTable;
use CreateFavoriteDigitalWalletsTable;
use CreateFavoriteDigitalWalletTransactionsTable;
use AddPartialSettlementAndManualRefundFields;
use FavoriteCMS\Core\Application;
use FavoriteCMS\Core\Database;
use FavoriteCMS\Core\Request;
use FavoriteCMS\Digital\Controllers\AdminOrderController;
use FavoriteCMS\Digital\Domain\ProductType;
use FavoriteCMS\Digital\Repositories\EntitlementRepository;
use FavoriteCMS\Digital\Repositories\OrderRepository;
use FavoriteCMS\Digital\Repositories\ProductRepository;
use FavoriteCMS\Digital\Repositories\RefundRepository;
use FavoriteCMS\Digital\Repositories\WalletRepository;
use FavoriteCMS\Digital\Services\CheckoutService;
use FavoriteCMS\Digital\Services\FulfillmentService;
use FavoriteCMS\Digital\Services\MembershipLifecycleService;
use FavoriteCMS\Digital\Services\OrderService;
use FavoriteCMS\Digital\Services\RefundService;
use FavoriteCMS\Digital\Services\WalletService;
use FavoriteCMS\Digital\Support\OrderLifecycleState;
use InvalidArgumentException;
use PDO;
use PHPUnit\Framework\TestCase;

class PartialOrderAndDarkModeTest extends TestCase
{
    private Database $db;
    private PDO $pdo;
    private Application $app;
    private OrderRepository $orderRepo;
    private RefundRepository $refundRepo;
    private WalletRepository $walletRepo;
    private WalletService $walletService;
    private EntitlementRepository $entitlementRepo;
    private MembershipLifecycleService $membershipService;
    private RefundService $refundService;
    private OrderService $orderService;
    private FulfillmentService $fulfillmentService;
    private CheckoutService $checkoutService;
    private AdminOrderController $adminController;

    protected function setUp(): void
    {
        // 1. In-memory SQLite for complete test isolation
        $this->pdo = new PDO('sqlite::memory:', '', '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
        ]);

        $this->db = new class($this->pdo) extends Database {
            public function __construct(PDO $pdo)
            {
                $this->pdo = $pdo;
                $this->config = ['driver' => 'sqlite'];
                $this->prefix = '';
            }
        };

        // 2. Run migrations
        $migDir = dirname(__DIR__) . '/database/migrations';
        require_once $migDir . '/001_create_favorite_digital_products_table.php';
        require_once $migDir . '/002_create_favorite_digital_product_details_table.php';
        require_once $migDir . '/003_create_favorite_digital_service_details_table.php';
        require_once $migDir . '/008_create_favorite_digital_orders_table.php';
        require_once $migDir . '/009_create_favorite_digital_order_items_table.php';
        require_once $migDir . '/010_create_favorite_digital_order_payments_table.php';
        require_once $migDir . '/011_create_favorite_digital_entitlements_table.php';
        require_once $migDir . '/012_create_favorite_digital_downloads_table.php';
        require_once $migDir . '/013_create_favorite_digital_wallets_table.php';
        require_once $migDir . '/014_create_favorite_digital_wallet_transactions_table.php';
        require_once $migDir . '/015_create_favorite_digital_refunds_table.php';
        require_once $migDir . '/017_add_partial_settlement_and_manual_refund_fields.php';

        (new CreateFavoriteDigitalProductsTable($this->db))->up();
        (new CreateFavoriteDigitalProductDetailsTable($this->db))->up();
        (new CreateFavoriteDigitalServiceDetailsTable($this->db))->up();
        (new CreateFavoriteDigitalOrdersTable($this->db))->up();
        (new CreateFavoriteDigitalOrderItemsTable($this->db))->up();
        (new CreateFavoriteDigitalOrderPaymentsTable($this->db))->up();
        (new CreateFavoriteDigitalEntitlementsTable($this->db))->up();
        (new CreateFavoriteDigitalDownloadsTable($this->db))->up();
        (new CreateFavoriteDigitalWalletsTable($this->db))->up();
        (new CreateFavoriteDigitalWalletTransactionsTable($this->db))->up();
        (new CreateFavoriteDigitalRefundsTable($this->db))->up();
        (new AddPartialSettlementAndManualRefundFields($this->db))->up();

        // 3. Initialize Repositories & Services
        $this->app = Application::getInstance();
        $this->app->instance(Database::class, $this->db);

        $this->orderRepo = new OrderRepository($this->db);
        $this->refundRepo = new RefundRepository($this->db);
        $this->walletRepo = new WalletRepository($this->db);
        $this->walletService = new WalletService($this->walletRepo);
        $this->entitlementRepo = new EntitlementRepository($this->db);
        $productRepo = new ProductRepository($this->db);
        $this->membershipService = new MembershipLifecycleService($productRepo);

        $this->fulfillmentService = new FulfillmentService(
            $this->orderRepo,
            $this->entitlementRepo,
            $productRepo,
            $this->membershipService,
            $this->db
        );

        $this->refundService = new RefundService(
            $this->orderRepo,
            $this->refundRepo,
            $this->walletService,
            $this->entitlementRepo,
            $this->membershipService,
            $this->db
        );

        $this->checkoutService = new CheckoutService(
            $this->orderRepo,
            $this->walletService,
            null,
            $this->db,
            $this->fulfillmentService
        );

        $this->orderService = new OrderService(
            $this->orderRepo,
            $productRepo,
            $this->membershipService,
            null,
            $this->db
        );

        $this->adminController = new AdminOrderController(
            $this->app,
            $this->orderService,
            $this->fulfillmentService,
            $this->entitlementRepo,
            $this->refundService
        );

        // Setup test admin user in session
        $_SESSION = [
            'auth_user_id' => 1,
            '_token'       => 'test_csrf_token_123',
        ];

        $GLOBALS['_test_current_user'] = new class {
            public int $id = 1;
            public function can(string $cap): bool { return true; }
            public function isActive(): bool { return true; }
        };
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['_test_current_user']);
        $_SESSION = [];
    }

    /**
     * Helper to create a test order with specified total and paid amount.
     */
    private function createTestOrder(
        float $totalAmount,
        float $paidAmount = 0.0,
        int $userId = 1,
        string $productType = ProductType::DIGITAL
    ): int {
        $orderId = $this->orderRepo->createOrder([
            'order_number'       => 'ORD-' . bin2hex(random_bytes(4)),
            'user_id'            => $userId,
            'status'             => OrderLifecycleState::STATUS_PENDING,
            'payment_status'     => OrderLifecycleState::PAYMENT_PENDING,
            'fulfillment_status' => OrderLifecycleState::FULFILLMENT_UNFULFILLED,
            'subtotal_amount'    => number_format($totalAmount, 2, '.', ''),
            'discount_amount'    => '0.00',
            'total_amount'       => number_format($totalAmount, 2, '.', ''),
            'currency'           => 'BDT',
            'notes'              => 'Test order',
            'created_at'         => date('Y-m-d H:i:s'),
            'updated_at'         => date('Y-m-d H:i:s'),
        ]);

        // Add order item
        $this->orderRepo->createOrderItem([
            'order_id'         => $orderId,
            'product_id'       => 10,
            'product_type'     => $productType,
            'unit_price'       => number_format($totalAmount, 2, '.', ''),
            'discount_percent' => '0.00',
            'final_price'      => number_format($totalAmount, 2, '.', ''),
            'currency'         => 'BDT',
            'snapshot_data'    => json_encode(['title' => 'Sample Item']),
            'created_at'       => date('Y-m-d H:i:s'),
        ]);

        if ($paidAmount > 0) {
            $this->orderRepo->createOrderPayment([
                'order_id'           => $orderId,
                'payment_method'     => 'bkash',
                'favorite_pay_tx_id' => 'TX_' . bin2hex(random_bytes(4)),
                'wallet_tx_id'       => null,
                'amount_paid'        => number_format($paidAmount, 2, '.', ''),
                'currency'           => 'BDT',
                'status'             => 'completed',
                'created_at'         => date('Y-m-d H:i:s'),
                'updated_at'         => date('Y-m-d H:i:s'),
            ]);
            $this->orderRepo->updatePaymentStatus($orderId, OrderLifecycleState::PAYMENT_PAID);
        }

        return $orderId;
    }

    /**
     * TEST A: Automatic Payment Verification for Digital Product.
     * Verified + Digital Product -> Order Status: completed, delivery triggered, Refund: 0, Wallet: 0.
     */
    public function testAutoPaymentVerificationDigitalProduct(): void
    {
        $userId = 10;
        // User starts with 500 in wallet
        $this->walletService->credit($userId, '500.00', 'init_dep', 'Deposit', null);

        $orderId = $this->createTestOrder(300.00, 0.0, $userId, ProductType::DIGITAL);

        // Process wallet payment (triggers auto verification and fulfillment)
        $order = $this->checkoutService->processWalletPayment($orderId, $userId);

        $this->assertSame(OrderLifecycleState::STATUS_COMPLETED, $order->status);
        $this->assertSame(OrderLifecycleState::PAYMENT_PAID, $order->payment_status);
        $this->assertSame(OrderLifecycleState::FULFILLMENT_FULFILLED, $order->fulfillment_status);

        // Wallet deduction was 300, remaining balance should be 200
        $bal = (float)$this->walletService->getBalance($userId);
        $this->assertEquals(200.00, $bal);

        // Zero refund, zero refund records
        $refunds = $this->refundRepo->findRefundsByOrderId($orderId);
        $this->assertEmpty($refunds);
    }

    /**
     * TEST B: Automatic Payment Verification for Digital Service.
     * Verified + Digital Service -> Order Status: processing, service workflow active, Refund: 0, Wallet: 0.
     */
    public function testAutoPaymentVerificationDigitalService(): void
    {
        $userId = 11;
        $this->walletService->credit($userId, '500.00', 'init_dep', 'Deposit', null);

        $orderId = $this->createTestOrder(300.00, 0.0, $userId, ProductType::SERVICE);

        // Process wallet payment
        $order = $this->checkoutService->processWalletPayment($orderId, $userId);

        $this->assertSame(OrderLifecycleState::STATUS_PROCESSING, $order->status);
        $this->assertSame(OrderLifecycleState::PAYMENT_PAID, $order->payment_status);
        $this->assertSame(OrderLifecycleState::FULFILLMENT_PARTIALLY_FULFILLED, $order->fulfillment_status);

        // Zero refund, zero refund records
        $refunds = $this->refundRepo->findRefundsByOrderId($orderId);
        $this->assertEmpty($refunds);
    }

    /**
     * TEST C: Manual Fake Payment -> Cancel.
     * Order -> cancelled, Payment -> failed, Fulfillment -> cancelled.
     * CRITICAL: NO REFUND. NO WALLET CREDIT (0). NO REFUND RECORD CREATED. (Cancel != Refund).
     */
    public function testManualCancelNoRefundNoWalletCredit(): void
    {
        $userId = 12;
        $orderId = $this->createTestOrder(300.00, 0.0, $userId, ProductType::DIGITAL);

        // Add a pending manual payment record (fake TrxID entered by customer)
        $this->orderRepo->createOrderPayment([
            'order_id'           => $orderId,
            'payment_method'     => 'manual',
            'amount_paid'        => '300.00',
            'currency'           => 'BDT',
            'status'             => 'pending',
            'created_at'         => date('Y-m-d H:i:s'),
            'updated_at'         => date('Y-m-d H:i:s'),
        ]);

        // Admin cancels the order
        $msg = $this->adminController->cancelSingleOrder($orderId, 'Fake TrxID submitted.');
        $this->assertStringContainsString('cancelled', $msg);

        $order = $this->orderRepo->findOrder($orderId);
        $this->assertSame(OrderLifecycleState::STATUS_CANCELLED, $order->status);
        $this->assertSame(OrderLifecycleState::PAYMENT_FAILED, $order->payment_status);
        $this->assertSame(OrderLifecycleState::FULFILLMENT_CANCELLED, $order->fulfillment_status);

        // Check payment record status
        $payments = $this->orderRepo->getOrderPayments($orderId);
        $this->assertSame('failed', $payments[0]->status);

        // STRICT INVARIANT: No wallet balance created, no refund record created
        $walletBal = (float)$this->walletService->getBalance($userId);
        $this->assertEquals(0.00, $walletBal);

        $refunds = $this->refundRepo->findRefundsByOrderId($orderId);
        $this->assertEmpty($refunds);
    }

    /**
     * TEST D: Manual Confirm for Digital Product.
     * Order -> completed, Payment -> paid, Fulfillment -> fulfilled. Retained = 300, Refund = 0.
     */
    public function testManualConfirmDigitalProduct(): void
    {
        $userId = 13;
        $orderId = $this->createTestOrder(300.00, 0.0, $userId, ProductType::DIGITAL);

        $this->orderRepo->createOrderPayment([
            'order_id'       => $orderId,
            'payment_method' => 'manual',
            'amount_paid'    => '300.00',
            'currency'       => 'BDT',
            'status'         => 'pending',
            'created_at'     => date('Y-m-d H:i:s'),
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);

        $this->adminController->acceptSingleOrder($orderId, 'auto');

        $order = $this->orderRepo->findOrder($orderId);
        $this->assertSame(OrderLifecycleState::STATUS_COMPLETED, $order->status);
        $this->assertSame(OrderLifecycleState::PAYMENT_PAID, $order->payment_status);
        $this->assertSame(OrderLifecycleState::FULFILLMENT_FULFILLED, $order->fulfillment_status);
        $this->assertSame('300.00', $order->retained_amount);
        $this->assertSame('0.00', $order->refunded_amount);

        // No refund records
        $this->assertEmpty($this->refundRepo->findRefundsByOrderId($orderId));
    }

    /**
     * TEST E: Manual Confirm for Digital Service.
     * Order -> processing, Payment -> paid, Fulfillment -> partially_fulfilled. Retained = 300, Refund = 0.
     */
    public function testManualConfirmDigitalService(): void
    {
        $userId = 14;
        $orderId = $this->createTestOrder(300.00, 0.0, $userId, ProductType::SERVICE);

        $this->orderRepo->createOrderPayment([
            'order_id'       => $orderId,
            'payment_method' => 'manual',
            'amount_paid'    => '300.00',
            'currency'       => 'BDT',
            'status'         => 'pending',
            'created_at'     => date('Y-m-d H:i:s'),
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);

        $this->adminController->acceptSingleOrder($orderId, 'auto');

        $order = $this->orderRepo->findOrder($orderId);
        $this->assertSame(OrderLifecycleState::STATUS_PROCESSING, $order->status);
        $this->assertSame(OrderLifecycleState::PAYMENT_PAID, $order->payment_status);
        $this->assertSame(OrderLifecycleState::FULFILLMENT_PARTIALLY_FULFILLED, $order->fulfillment_status);
        $this->assertSame('300.00', $order->retained_amount);
        $this->assertSame('0.00', $order->refunded_amount);

        // No refund records
        $this->assertEmpty($this->refundRepo->findRefundsByOrderId($orderId));
    }

    /**
     * TEST F: Full Refund.
     * Order was verified paid (300). Admin issues full refund.
     * Order -> refunded, Payment -> refunded, Retained = 0, Refunded = 300, Customer Wallet = +300.
     */
    public function testFullOrderRefund(): void
    {
        $userId = 15;
        $orderId = $this->createTestOrder(300.00, 300.00, $userId, ProductType::DIGITAL);

        $refund = $this->refundService->processRefund($orderId, 'Customer dispute resolved with full refund', 1, true);

        $this->assertNotNull($refund);
        $this->assertSame('300.00', $refund->refund_amount);
        $this->assertSame('completed', $refund->status);

        $order = $this->orderRepo->findOrder($orderId);
        $this->assertSame(OrderLifecycleState::STATUS_REFUNDED, $order->status);
        $this->assertSame(OrderLifecycleState::PAYMENT_REFUNDED, $order->payment_status);
        $this->assertSame(OrderLifecycleState::FULFILLMENT_REVOKED, $order->fulfillment_status);
        $this->assertSame('0.00', $order->retained_amount);
        $this->assertSame('300.00', $order->refunded_amount);

        // Wallet was credited with +300
        $bal = (float)$this->walletService->getBalance($userId);
        $this->assertEquals(300.00, $bal);
    }

    /**
     * TEST G: Partial Settlement (Admin-Side Fulfillment Decision).
     * Order Total = 300, Received = 300.
     * Admin delivers partial work and returns 100 to customer's wallet.
     * Order -> partial, Payment -> partially_refunded, Wallet = +100, Retained = 200, Refunded = 100.
     */
    public function testPartialSettlementAndRefund(): void
    {
        $userId = 16;
        $orderId = $this->createTestOrder(300.00, 300.00, $userId, ProductType::SERVICE);

        // Admin returns 100 to customer wallet
        $refund = $this->refundService->processPartialRefund(
            $orderId,
            100.00,
            'Completed milestone 1 (৳200); refunded milestone 2 (৳100)',
            'wallet',
            'REF-PART-01',
            1
        );

        $this->assertNotNull($refund);
        $this->assertSame('100.00', $refund->refund_amount);
        $this->assertSame('completed', $refund->status);

        $order = $this->orderRepo->findOrder($orderId);
        $this->assertSame(OrderLifecycleState::STATUS_PARTIAL, $order->status);
        $this->assertSame(OrderLifecycleState::PAYMENT_PARTIALLY_REFUNDED, $order->payment_status);
        $this->assertSame(OrderLifecycleState::FULFILLMENT_PARTIALLY_FULFILLED, $order->fulfillment_status);
        $this->assertSame('200.00', $order->retained_amount);
        $this->assertSame('100.00', $order->refunded_amount);

        // Wallet credited with 100
        $bal = (float)$this->walletService->getBalance($userId);
        $this->assertEquals(100.00, $bal);

        // Authoritative accounting reconciliation
        $totalReceived = (float)$this->refundService->calculateAuthoritativeRefundAmount($order);
        $totalRefunded = (float)$this->refundService->calculateAuthoritativeRefundedAmount($orderId);
        $netRetained   = $totalReceived - $totalRefunded;
        $remainingRef  = max(0, $totalReceived - $totalRefunded);

        $this->assertEquals(300.00, $totalReceived);
        $this->assertEquals(100.00, $totalRefunded);
        $this->assertEquals(200.00, $netRetained);
        $this->assertEquals(200.00, $remainingRef);
    }

    /**
     * TEST H: Partial Refund Limit Validation.
     * Refund amount cannot exceed Remaining Refundable Amount = max(0, Received - Previous Refunds).
     */
    public function testPartialRefundLimitValidation(): void
    {
        $userId = 17;
        $orderId = $this->createTestOrder(300.00, 300.00, $userId);
        $orderId = $this->createTestOrder(300.00, 300.00, $userId, ProductType::SERVICE);

        // 1st partial refund: 100
        $this->refundService->processPartialRefund($orderId, 100.00, 'First partial refund');

        // Remaining refundable is now 200. Attempting to refund 250 must throw InvalidArgumentException
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/exceeds the maximum refundable amount of 200\.00/');

        $this->refundService->processPartialRefund($orderId, 250.00, 'Exceeding refund attempt');
    }

    /**
     * TEST I: Subsequent Partial Refunds up to Full Amount.
     * 1st refund 100 (retained 200) -> 2nd refund 100 (retained 100) -> 3rd refund 100 (retained 0).
     */
    public function testSubsequentPartialRefunds(): void
    {
        $userId = 18;
        $orderId = $this->createTestOrder(300.00, 300.00, $userId);
        $orderId = $this->createTestOrder(300.00, 300.00, $userId, ProductType::SERVICE);

        $this->refundService->processPartialRefund($orderId, 100.00, 'Refund 1', 'wallet', 'R1');
        $this->refundService->processPartialRefund($orderId, 100.00, 'Refund 2', 'wallet', 'R2');

        $order = $this->orderRepo->findOrder($orderId);
        $this->assertSame(OrderLifecycleState::STATUS_PARTIAL, $order->status);
        $this->assertSame('100.00', $order->retained_amount);
        $this->assertSame('200.00', $order->refunded_amount);

        // Final refund for remaining 100
        $this->refundService->processPartialRefund($orderId, 100.00, 'Refund 3', 'wallet', 'R3');

        $finalOrder = $this->orderRepo->findOrder($orderId);
        $this->assertSame(OrderLifecycleState::STATUS_REFUNDED, $finalOrder->status);
        $this->assertSame(OrderLifecycleState::PAYMENT_REFUNDED, $finalOrder->payment_status);
        $this->assertSame('0.00', $finalOrder->retained_amount);
        $this->assertSame('300.00', $finalOrder->refunded_amount);

        // Total wallet credit = 300
        $this->assertEquals(300.00, (float)$this->walletService->getBalance($userId));
    }

    /**
     * TEST J: Idempotency of Partial Refund.
     * Duplicate submission with same reference must not double-credit wallet.
     */
    public function testPartialRefundIdempotency(): void
    {
        $userId = 19;
        $orderId = $this->createTestOrder(300.00, 300.00, $userId);
        $orderId = $this->createTestOrder(300.00, 300.00, $userId, ProductType::SERVICE);

        $ref1 = $this->refundService->processPartialRefund($orderId, 100.00, 'Refund test', 'wallet', 'UNIQUE-REF-99');
        $bal1 = (float)$this->walletService->getBalance($userId);

        // Re-submit with same reference
        $ref2 = $this->refundService->processPartialRefund($orderId, 100.00, 'Refund test', 'wallet', 'UNIQUE-REF-99');
        $bal2 = (float)$this->walletService->getBalance($userId);

        $this->assertSame($ref1->id, $ref2->id);
        $this->assertEquals(100.00, $bal1);
        $this->assertEquals(100.00, $bal2);
    }

    /**
     * TEST K: Admin UI and Dark Mode Tokens Verification.
     * Ensures viewOrder() has exactly ONE authoritative Order Status dropdown,
     * NO duplicate panels (Manual Payment Decisions, Partial Settlement, Service Progress Status),
     * read-only Payment & Fulfillment badges, and semantic CSS tokens for dark mode.
     */
    public function testAdminOrderViewAndDarkModeTokens(): void
    {
        $userId = 20;
        $orderId = $this->createTestOrder(300.00, 300.00, $userId, ProductType::SERVICE);

        $request = new Request(['action' => 'view', 'id' => (string)$orderId], [], ['REQUEST_METHOD' => 'GET']);
        $html = $this->adminController->viewOrder($request, $orderId);

        $this->assertIsString($html);

        // 1. Confirm absence of duplicate panels
        $this->assertStringNotContainsString('Manual Payment Decisions', $html);
        $this->assertStringNotContainsString('partial-settlement-panel', $html);
        $this->assertStringNotContainsString('fd-service-progress-box', $html);
        $this->assertStringNotContainsString('Update Service Status', $html);

        // 2. Confirm single Order Status dropdown with strictly 4 options
        $this->assertStringContainsString('id="fd_main_status_select"', $html);
        $this->assertStringContainsString('value="processing"', $html);
        $this->assertStringContainsString('value="partial"', $html);
        $this->assertStringContainsString('value="completed"', $html);
        $this->assertStringContainsString('value="refunded"', $html);
        $this->assertDoesNotMatchRegularExpression('/<select[^>]*name="status"[^>]*>[\s\S]*?<option[^>]*value="cancelled"/i', $html);

        // 3. Confirm Payment Status and Fulfillment Status are read-only
        $this->assertDoesNotMatchRegularExpression('/<select[^>]*name="payment_status"/i', $html);
        $this->assertDoesNotMatchRegularExpression('/<select[^>]*name="fulfillment_status"/i', $html);
        $this->assertStringContainsString('Payment Status:', $html);
        $this->assertStringContainsString('Fulfillment Status:', $html);

        // 4. Check for accounting summary labels
        $this->assertStringContainsString('Order Total', $html);
        $this->assertStringContainsString('Amount Received', $html);
        $this->assertStringContainsString('Amount Retained', $html);
        $this->assertStringContainsString('Amount Refunded', $html);
        $this->assertStringContainsString('Refundable Amount', $html);

        // 5. Check for dark mode semantic CSS variables
        $this->assertStringContainsString('[data-admin-theme="dark"]', $html);
        $this->assertStringContainsString('var(--admin-surface', $html);
        $this->assertStringContainsString('var(--admin-border', $html);
        $this->assertStringContainsString('var(--admin-text', $html);
        $this->assertStringContainsString('var(--admin-danger-bg', $html);
        $this->assertStringContainsString('var(--admin-warning-bg', $html);
    }
}
