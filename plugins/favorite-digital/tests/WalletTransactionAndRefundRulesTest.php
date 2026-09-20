<?php

declare(strict_types=1);

namespace FavoriteCMS\Digital\Tests;

use DateTimeImmutable;
use FavoriteCMS\Core\Application;
use FavoriteCMS\Core\Database;
use FavoriteCMS\Core\Request;
use FavoriteCMS\Digital\Controllers\AdminOrderController;
use FavoriteCMS\Digital\Domain\ProductType;
use FavoriteCMS\Digital\Exceptions\CheckoutException;
use FavoriteCMS\Digital\Exceptions\DownloadException;
use FavoriteCMS\Digital\Repositories\DownloadRepository;
use FavoriteCMS\Digital\Repositories\EntitlementRepository;
use FavoriteCMS\Digital\Repositories\OrderRepository;
use FavoriteCMS\Digital\Repositories\ProductRepository;
use FavoriteCMS\Digital\Repositories\RefundRepository;
use FavoriteCMS\Digital\Repositories\WalletRepository;
use FavoriteCMS\Digital\Services\CheckoutService;
use FavoriteCMS\Digital\Services\DefaultEntitlementChecker;
use FavoriteCMS\Digital\Services\DigitalFileStorageService;
use FavoriteCMS\Digital\Services\DownloadService;
use FavoriteCMS\Digital\Services\FulfillmentService;
use FavoriteCMS\Digital\Services\MembershipLifecycleService;
use FavoriteCMS\Digital\Services\OrderService;
use FavoriteCMS\Digital\Services\RefundService;
use FavoriteCMS\Digital\Services\WalletService;
use FavoriteCMS\Digital\Support\OrderLifecycleState;
use FavoriteCMS\Pay\Contracts\CurrencyServiceInterface;
use FavoriteCMS\Pay\Contracts\GatewayRegistry;
use FavoriteCMS\Pay\Contracts\PaymentServiceInterface;
use FavoriteCMS\Pay\Gateways\Contracts\PaymentGatewayInterface;
use FavoriteCMS\Pay\Domain\Money;
use FavoriteCMS\Pay\Domain\PaymentAttempt;
use FavoriteCMS\Pay\Domain\PaymentIntent;
use FavoriteCMS\Pay\Domain\PaymentMethodType;
use FavoriteCMS\Pay\Domain\PaymentStatus;
use InvalidArgumentException;
use PDO;
use PHPUnit\Framework\TestCase;
use Throwable;

class WalletTransactionAndRefundRulesTest extends TestCase
{
    private Database $db;
    private PDO $pdo;
    private Application $app;
    private OrderRepository $orderRepo;
    private RefundRepository $refundRepo;
    private WalletRepository $walletRepo;
    private WalletService $walletService;
    private EntitlementRepository $entitlementRepo;
    private DownloadRepository $downloadRepo;
    private ProductRepository $productRepo;
    private MembershipLifecycleService $membershipService;
    private RefundService $refundService;
    private OrderService $orderService;
    private FulfillmentService $fulfillmentService;
    private CheckoutService $checkoutService;
    private DownloadService $downloadService;
    private AdminOrderController $adminController;
    private ?\FavoriteCMS\Pay\Services\WalletService $favPayWalletService = null;

    protected function setUp(): void
    {
        // 1. Isolated in-memory SQLite database
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

        // 2. Run schema migrations
        $migDir = dirname(__DIR__) . '/database/migrations';
        require_once $migDir . '/001_create_favorite_digital_products_table.php';
        require_once $migDir . '/002_create_favorite_digital_product_details_table.php';
        require_once $migDir . '/003_create_favorite_digital_service_details_table.php';
        require_once $migDir . '/006_create_favorite_digital_membership_plans_table.php';
        require_once $migDir . '/007_create_favorite_digital_memberships_table.php';
        require_once $migDir . '/008_create_favorite_digital_orders_table.php';
        require_once $migDir . '/009_create_favorite_digital_order_items_table.php';
        require_once $migDir . '/010_create_favorite_digital_order_payments_table.php';
        require_once $migDir . '/011_create_favorite_digital_entitlements_table.php';
        require_once $migDir . '/012_create_favorite_digital_downloads_table.php';
        require_once $migDir . '/013_create_favorite_digital_wallets_table.php';
        require_once $migDir . '/014_create_favorite_digital_wallet_transactions_table.php';
        require_once $migDir . '/015_create_favorite_digital_refunds_table.php';
        require_once $migDir . '/016_add_v101_media_and_resource_fields.php';
        require_once $migDir . '/017_add_partial_settlement_and_manual_refund_fields.php';
        require_once $migDir . '/018_create_favorite_digital_order_deliverables_table.php';

        (new \CreateFavoriteDigitalProductsTable($this->db))->up();
        (new \CreateFavoriteDigitalProductDetailsTable($this->db))->up();
        (new \CreateFavoriteDigitalServiceDetailsTable($this->db))->up();
        (new \CreateFavoriteDigitalMembershipPlansTable($this->db))->up();
        (new \CreateFavoriteDigitalMembershipsTable($this->db))->up();
        (new \CreateFavoriteDigitalOrdersTable($this->db))->up();
        (new \CreateFavoriteDigitalOrderItemsTable($this->db))->up();
        (new \CreateFavoriteDigitalOrderPaymentsTable($this->db))->up();
        (new \CreateFavoriteDigitalEntitlementsTable($this->db))->up();
        (new \CreateFavoriteDigitalDownloadsTable($this->db))->up();
        (new \CreateFavoriteDigitalWalletsTable($this->db))->up();
        (new \CreateFavoriteDigitalWalletTransactionsTable($this->db))->up();
        (new \CreateFavoriteDigitalRefundsTable($this->db))->up();
        (new \AddV101MediaAndResourceFields($this->db))->up();
        (new \AddPartialSettlementAndManualRefundFields($this->db))->up();
        (new \CreateFavoriteDigitalOrderDeliverablesTable($this->db))->up();

        // 3. Favorite Pay Schema
        $this->db->execute("
            CREATE TABLE IF NOT EXISTS `favorite_pay_wallets` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `user_id` BIGINT NOT NULL UNIQUE,
                `balance` BIGINT NOT NULL DEFAULT 0,
                `currency` VARCHAR(3) NOT NULL DEFAULT 'BDT',
                `status` VARCHAR(32) NOT NULL DEFAULT 'active',
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
        ");
        $this->db->execute("
            CREATE TABLE IF NOT EXISTS `favorite_pay_wallet_entries` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `entry_id` VARCHAR(64) NOT NULL UNIQUE,
                `wallet_id` BIGINT NOT NULL,
                `user_id` BIGINT NOT NULL,
                `type` VARCHAR(32) NOT NULL,
                `amount` BIGINT NOT NULL,
                `balance_after` BIGINT NOT NULL,
                `reference_type` VARCHAR(64) NOT NULL,
                `reference_id` VARCHAR(191) NOT NULL,
                `idempotency_key` VARCHAR(191) NULL UNIQUE,
                `description` VARCHAR(500) NOT NULL DEFAULT '',
                `metadata` TEXT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
        ");

        // 4. DI Container & Repositories
        $this->app = Application::getInstance();
        $this->app->instance(Database::class, $this->db);

        $favPayCurrency = new \FavoriteCMS\Pay\Services\CurrencyService();
        $this->favPayWalletService = new \FavoriteCMS\Pay\Services\WalletService($favPayCurrency, null, $this->db);
        $this->app->instance(\FavoriteCMS\Pay\Contracts\WalletServiceInterface::class, $this->favPayWalletService);

        $this->orderRepo = new OrderRepository($this->db);
        $this->refundRepo = new RefundRepository($this->db);
        $this->walletRepo = new WalletRepository($this->db);
        $this->walletService = new WalletService($this->walletRepo, $this->db, $this->favPayWalletService);
        $this->entitlementRepo = new EntitlementRepository($this->db);
        $this->downloadRepo = new DownloadRepository($this->db);
        $this->productRepo = new ProductRepository($this->db);
        $this->membershipService = new MembershipLifecycleService($this->productRepo);

        $this->fulfillmentService = new FulfillmentService(
            $this->orderRepo,
            $this->entitlementRepo,
            $this->productRepo,
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

        $this->orderService = new OrderService(
            $this->orderRepo,
            $this->productRepo,
            $this->membershipService,
            new DefaultEntitlementChecker($this->db, $this->entitlementRepo),
            $this->db
        );

        $this->checkoutService = new CheckoutService(
            $this->orderRepo,
            $this->walletService,
            null,
            $this->db,
            $this->fulfillmentService
        );

        $storageService = new DigitalFileStorageService();
        $this->downloadService = new DownloadService(
            $this->downloadRepo,
            $this->entitlementRepo,
            $this->productRepo,
            $this->membershipService,
            new DefaultEntitlementChecker($this->db, $this->entitlementRepo),
            $storageService,
            $this->db,
            $this->orderRepo
        );

        $this->adminController = new AdminOrderController(
            $this->app,
            $this->orderService,
            $this->fulfillmentService,
            $this->entitlementRepo,
            $this->refundService,
            $storageService
        );
    }

    private function createOrder(
        float $total,
        int $userId = 1,
        string $productType = ProductType::DIGITAL,
        float $paid = 0.0
    ): int {
        $orderId = $this->orderRepo->createOrder([
            'order_number'       => 'ORD-' . strtoupper(bin2hex(random_bytes(4))),
            'user_id'            => $userId,
            'status'             => OrderLifecycleState::STATUS_PENDING,
            'payment_status'     => OrderLifecycleState::PAYMENT_PENDING,
            'fulfillment_status' => OrderLifecycleState::FULFILLMENT_UNFULFILLED,
            'subtotal_amount'    => number_format($total, 2, '.', ''),
            'discount_amount'    => '0.00',
            'total_amount'       => number_format($total, 2, '.', ''),
            'currency'           => 'BDT',
            'notes'              => 'Test Order',
            'created_at'         => date('Y-m-d H:i:s'),
            'updated_at'         => date('Y-m-d H:i:s'),
        ]);

        $productId = $this->productRepo->createProduct([
            'title'        => $productType === ProductType::SERVICE ? 'Test Digital Service' : 'Test Digital Product',
            'slug'         => 'prod-' . bin2hex(random_bytes(3)),
            'product_type' => $productType,
            'original_price' => number_format($total, 2, '.', ''),
            'final_price'    => number_format($total, 2, '.', ''),
            'status'       => 'published',
            'currency'     => 'BDT',
            'created_at'   => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);

        if ($productType === ProductType::DIGITAL) {
            $storageService = new DigitalFileStorageService();
            $sampleDir = $storageService->getStorageDir() . '/digital-products';
            if (!is_dir($sampleDir)) {
                mkdir($sampleDir, 0777, true);
            }
            file_put_contents($sampleDir . '/sample.zip', 'SAMPLE_FILE_CONTENT_ZIP_12345');

            $this->productRepo->saveProductDetails($productId, [
                'file_path'     => 'digital-products/sample.zip',
                'file_name'     => 'sample.zip',
                'file_size'     => 1024,
                'resource_type' => 'file',
            ]);
        } else {
            $this->productRepo->saveServiceDetails($productId, [
                'delivery_time_days' => 3,
            ]);
        }

        $orderItemId = $this->orderRepo->createOrderItem([
            'order_id'         => $orderId,
            'product_id'       => $productId,
            'product_type'     => $productType,
            'unit_price'       => number_format($total, 2, '.', ''),
            'discount_percent' => '0.00',
            'final_price'      => number_format($total, 2, '.', ''),
            'currency'         => 'BDT',
            'snapshot_data'    => json_encode(['title' => 'Product ' . $productId]),
            'created_at'       => date('Y-m-d H:i:s'),
        ]);

        if ($paid > 0) {
            $this->orderRepo->createOrderPayment([
                'order_id'           => $orderId,
                'payment_method'     => 'wallet',
                'favorite_pay_tx_id' => null,
                'wallet_tx_id'       => 'wtx_' . bin2hex(random_bytes(4)),
                'amount_paid'        => number_format($paid, 2, '.', ''),
                'currency'           => 'BDT',
                'status'             => 'completed',
                'created_at'         => date('Y-m-d H:i:s'),
                'updated_at'         => date('Y-m-d H:i:s'),
            ]);
            $this->orderRepo->updatePaymentStatus($orderId, OrderLifecycleState::PAYMENT_PAID);
            $this->orderRepo->updateOrderStatus($orderId, $productType === ProductType::SERVICE ? OrderLifecycleState::STATUS_PROCESSING : OrderLifecycleState::STATUS_COMPLETED);
            $this->orderRepo->updateFulfillmentStatus($orderId, $productType === ProductType::SERVICE ? OrderLifecycleState::FULFILLMENT_PARTIALLY_FULFILLED : OrderLifecycleState::FULFILLMENT_FULFILLED);

            if ($productType === ProductType::DIGITAL) {
                $this->entitlementRepo->createEntitlement([
                    'user_id'     => $userId,
                    'product_id'  => $productId,
                    'source_type' => 'purchase',
                    'source_id'   => $orderItemId,
                    'status'      => 'active',
                ]);
            }
        }

        return $orderId;
    }

    /**
     * TEST 1: Wallet payment succeeds without nested PDO transaction error even when
     * an external service begins a transaction on the same connection.
     */
    public function testWalletPaymentSucceedsWithoutNestedPdoTransactionError(): void
    {
        $userId = 101;
        $this->walletService->credit($userId, '500.00', 'dep_101', 'Initial deposit');
        $orderId = $this->createOrder(190.00, $userId, ProductType::DIGITAL);

        // Simulate Favorite Pay wallet interceptor that executes $db->transaction() during debit
        $simulatedFavPayWalletService = new class($this->db) implements \FavoriteCMS\Pay\Contracts\WalletServiceInterface {
            private Database $db;
            public function __construct(Database $db) { $this->db = $db; }
            public function getBalance(int $userId): \FavoriteCMS\Pay\Domain\Money { return \FavoriteCMS\Pay\Domain\Money::bdt(50000); }
            public function getAvailableBalance(int $userId): \FavoriteCMS\Pay\Domain\Money { return \FavoriteCMS\Pay\Domain\Money::bdt(50000); }
            public function deposit(int $userId, \FavoriteCMS\Pay\Domain\Money $amount, string $referenceId, string $description = ''): \FavoriteCMS\Pay\Domain\WalletLedgerEntry {
                return new \FavoriteCMS\Pay\Domain\WalletLedgerEntry('e1', $userId, 'credit', $amount, $amount, 'deposit', $referenceId);
            }
            public function debit(int $userId, \FavoriteCMS\Pay\Domain\Money $amount, string $ref, string $desc = ''): \FavoriteCMS\Pay\Domain\WalletLedgerEntry {
                // This calls $db->transaction() which calls $pdo->beginTransaction()
                return $this->db->transaction(function () use ($userId, $amount, $ref, $desc) {
                    return new \FavoriteCMS\Pay\Domain\WalletLedgerEntry('e2', $userId, 'debit', $amount, \FavoriteCMS\Pay\Domain\Money::bdt(31000), 'checkout', $ref, $desc);
                });
            }
            public function hold(int $userId, \FavoriteCMS\Pay\Domain\Money $amount, string $referenceId): \FavoriteCMS\Pay\Domain\WalletLedgerEntry {
                return new \FavoriteCMS\Pay\Domain\WalletLedgerEntry('e3', $userId, 'hold', $amount, $amount, 'hold', $referenceId);
            }
            public function releaseHold(int $userId, \FavoriteCMS\Pay\Domain\Money $amount, string $referenceId): \FavoriteCMS\Pay\Domain\WalletLedgerEntry {
                return new \FavoriteCMS\Pay\Domain\WalletLedgerEntry('e4', $userId, 'release', $amount, $amount, 'release', $referenceId);
            }
            public function finalizeHold(int $userId, \FavoriteCMS\Pay\Domain\Money $amount, string $referenceId, string $description = ''): \FavoriteCMS\Pay\Domain\WalletLedgerEntry {
                return new \FavoriteCMS\Pay\Domain\WalletLedgerEntry('e5', $userId, 'debit', $amount, $amount, 'finalize', $referenceId, $description);
            }
            public function settleSuccessfulPayment(string $transactionId): \FavoriteCMS\Pay\Domain\WalletLedgerEntry {
                return new \FavoriteCMS\Pay\Domain\WalletLedgerEntry('e6', 1, 'credit', \FavoriteCMS\Pay\Domain\Money::bdt(0), \FavoriteCMS\Pay\Domain\Money::bdt(0), 'settle', $transactionId);
            }
            public function getLedgerHistory(int $userId, int $limit = 50, int $offset = 0): array { return []; }
            public function getHeldBalance(int $userId): \FavoriteCMS\Pay\Domain\Money { return \FavoriteCMS\Pay\Domain\Money::bdt(0); }
            public function getTotalBalance(int $userId): \FavoriteCMS\Pay\Domain\Money { return \FavoriteCMS\Pay\Domain\Money::bdt(50000); }
            public function getFilteredLedgerHistory(int $userId, array $filters = [], int $limit = 20, int $offset = 0): array { return []; }
            public function getFilteredLedgerCount(int $userId, array $filters = []): int { return 0; }
            public function getLedgerEntry(string $entryId, ?int $userId = null): ?\FavoriteCMS\Pay\Domain\WalletLedgerEntry { return null; }
            public function getGlobalWalletOverview(): array {
                return ['total_wallets' => 1, 'total_balance' => \FavoriteCMS\Pay\Domain\Money::bdt(0), 'available_balance' => \FavoriteCMS\Pay\Domain\Money::bdt(0), 'held_balance' => \FavoriteCMS\Pay\Domain\Money::bdt(0), 'currency' => 'BDT'];
            }
            public function searchCustomerWallets(string $query, int $limit = 20): array { return []; }
            public function getGlobalRecentActivity(int $limit = 15): array { return []; }
        };

        $walletServiceWithFavPay = new WalletService($this->walletRepo, $this->db, $simulatedFavPayWalletService);
        $checkout = new CheckoutService($this->orderRepo, $walletServiceWithFavPay, null, $this->db, $this->fulfillmentService);

        // This must NOT throw "There is already an active transaction"!
        $result = $checkout->processWalletPayment($orderId, $userId);
        $this->assertNotNull($result);
        $this->assertSame(OrderLifecycleState::STATUS_COMPLETED, $result->status);
        $this->assertSame(OrderLifecycleState::PAYMENT_PAID, $result->payment_status);
    }

    /**
     * TEST 2: Wallet payment deducts the correct full amount.
     */
    public function testWalletPaymentDeductsCorrectFullAmount(): void
    {
        $userId = 102;
        $this->walletService->credit($userId, '500.00', 'dep_102', 'Initial deposit');
        $orderId = $this->createOrder(190.00, $userId, ProductType::DIGITAL);

        $this->checkoutService->processWalletPayment($orderId, $userId);

        $remainingWallet = (float)$this->walletService->getBalance($userId);
        $this->assertEquals(310.00, $remainingWallet);
    }

    /**
     * TEST 3: Favorite Digital payment record is created and updated correctly.
     */
    public function testFavoriteDigitalPaymentRecordCreatedAndUpdated(): void
    {
        $userId = 103;
        $this->walletService->credit($userId, '200.00', 'dep_103', 'Initial deposit');
        $orderId = $this->createOrder(190.00, $userId, ProductType::DIGITAL);

        $this->checkoutService->processWalletPayment($orderId, $userId);

        $payments = $this->orderRepo->getOrderPayments($orderId);
        $this->assertCount(1, $payments);
        $this->assertSame('wallet', $payments[0]->payment_method);
        $this->assertSame('190.00', $payments[0]->amount_paid);
        $this->assertSame('completed', $payments[0]->status);
    }

    /**
     * TEST 4: Wallet payment for Digital Product results in COMPLETED / PAID / FULFILLED.
     */
    public function testWalletPaymentForDigitalProductResultsInCompletedPaidFulfilled(): void
    {
        $userId = 104;
        $this->walletService->credit($userId, '100.00', 'dep_104', 'Initial deposit');
        $orderId = $this->createOrder(100.00, $userId, ProductType::DIGITAL);

        $order = $this->checkoutService->processWalletPayment($orderId, $userId);

        $this->assertSame(OrderLifecycleState::STATUS_COMPLETED, $order->status);
        $this->assertSame(OrderLifecycleState::PAYMENT_PAID, $order->payment_status);
        $this->assertSame(OrderLifecycleState::FULFILLMENT_FULFILLED, $order->fulfillment_status);

        $orderRecord = $this->orderRepo->findOrder($orderId);
        $items = $this->orderRepo->getOrderItems($orderId);
        $productId = (int)$items[0]->product_id;
        $ent = $this->entitlementRepo->findActiveEntitlement($userId, $productId);
        $this->assertNotNull($ent);
        $this->assertSame('active', $ent->status);
    }

    /**
     * TEST 5: Wallet payment for Digital Service results in PROCESSING / PAID / PARTIALLY_FULFILLED.
     */
    public function testWalletPaymentForDigitalServiceResultsInProcessingPaidPartiallyFulfilled(): void
    {
        $userId = 105;
        $this->walletService->credit($userId, '250.00', 'dep_105', 'Initial deposit');
        $orderId = $this->createOrder(250.00, $userId, ProductType::SERVICE);

        $order = $this->checkoutService->processWalletPayment($orderId, $userId);

        $this->assertSame(OrderLifecycleState::STATUS_PROCESSING, $order->status);
        $this->assertSame(OrderLifecycleState::PAYMENT_PAID, $order->payment_status);
        $this->assertSame(OrderLifecycleState::FULFILLMENT_PARTIALLY_FULFILLED, $order->fulfillment_status);
    }

    /**
     * TEST 6: Successful automatic payment results in the correct final state.
     */
    public function testSuccessfulAutomaticPaymentResultsInCorrectFinalState(): void
    {
        // Digital Product
        $orderProdId = $this->createOrder(150.00, 201, ProductType::DIGITAL);
        $mockPayService = $this->createMock(PaymentServiceInterface::class);
        $mockPayService->method('getIntent')
            ->willReturn(new PaymentIntent('pi_test_prod', 'favorite-digital', (string)$orderProdId, Money::bdt(15000), Money::bdt(15000), PaymentStatus::SUCCEEDED));

        $checkout = new CheckoutService($this->orderRepo, $this->walletService, $mockPayService, $this->db, $this->fulfillmentService);
        $settledProd = $checkout->verifyAndSettlePayment($orderProdId, 'pi_test_prod');

        $this->assertSame(OrderLifecycleState::STATUS_COMPLETED, $settledProd->status);
        $this->assertSame(OrderLifecycleState::PAYMENT_PAID, $settledProd->payment_status);
        $this->assertSame(OrderLifecycleState::FULFILLMENT_FULFILLED, $settledProd->fulfillment_status);

        // Digital Service
        $orderServId = $this->createOrder(200.00, 202, ProductType::SERVICE);
        $mockPayServiceServ = $this->createMock(PaymentServiceInterface::class);
        $mockPayServiceServ->method('getIntent')
            ->willReturn(new PaymentIntent('pi_test_serv', 'favorite-digital', (string)$orderServId, Money::bdt(20000), Money::bdt(20000), PaymentStatus::SUCCEEDED));

        $checkoutServ = new CheckoutService($this->orderRepo, $this->walletService, $mockPayServiceServ, $this->db, $this->fulfillmentService);
        $settledServ = $checkoutServ->verifyAndSettlePayment($orderServId, 'pi_test_serv');

        $this->assertSame(OrderLifecycleState::STATUS_PROCESSING, $settledServ->status);
        $this->assertSame(OrderLifecycleState::PAYMENT_PAID, $settledServ->payment_status);
        $this->assertSame(OrderLifecycleState::FULFILLMENT_PARTIALLY_FULFILLED, $settledServ->fulfillment_status);
    }

    /**
     * TEST 7: Manual payment submission remains PENDING / UNPAID / UNFULFILLED until admin confirmation.
     */
    public function testManualPaymentSubmissionRemainsPendingUnpaidUnfulfilledUntilAdminConfirmation(): void
    {
        $userId = 107;
        $orderId = $this->createOrder(300.00, $userId, ProductType::SERVICE);

        $mockPay = $this->createMock(PaymentServiceInterface::class);
        $mockPay->method('submitManualVerification')
            ->willReturn(new PaymentAttempt('att_mv', 'pi_m', 'bdt_manual_bkash', Money::bdt(30000), PaymentStatus::AWAITING_VERIFICATION));

        $checkout = new CheckoutService($this->orderRepo, $this->walletService, $mockPay, $this->db, $this->fulfillmentService);
        $res = $checkout->submitManualPayment($orderId, $userId, 'pi_m', 'bdt_manual_bkash', 'TRX_TEST_123', ['sender' => '01700000000']);

        $order = $this->orderRepo->findOrder($orderId);
        $this->assertSame(OrderLifecycleState::STATUS_PENDING, $order->status);
        $this->assertSame(OrderLifecycleState::PAYMENT_PENDING, $order->payment_status);
        $this->assertSame(OrderLifecycleState::FULFILLMENT_UNFULFILLED, $order->fulfillment_status);
    }

    /**
     * TEST 8: Admin confirmation of manual Digital Product results in COMPLETED / PAID / FULFILLED.
     */
    public function testAdminConfirmationOfManualDigitalProduct(): void
    {
        $userId = 108;
        $orderId = $this->createOrder(200.00, $userId, ProductType::DIGITAL);

        $this->adminController->acceptSingleOrder($orderId, 'auto');

        $order = $this->orderRepo->findOrder($orderId);
        $this->assertSame(OrderLifecycleState::STATUS_COMPLETED, $order->status);
        $this->assertSame(OrderLifecycleState::PAYMENT_PAID, $order->payment_status);
        $this->assertSame(OrderLifecycleState::FULFILLMENT_FULFILLED, $order->fulfillment_status);
    }

    /**
     * TEST 9: Admin confirmation of manual Digital Service results in PROCESSING / PAID / PARTIALLY_FULFILLED.
     */
    public function testAdminConfirmationOfManualDigitalService(): void
    {
        $userId = 109;
        $orderId = $this->createOrder(350.00, $userId, ProductType::SERVICE);

        $this->adminController->acceptSingleOrder($orderId, 'auto');

        $order = $this->orderRepo->findOrder($orderId);
        $this->assertSame(OrderLifecycleState::STATUS_PROCESSING, $order->status);
        $this->assertSame(OrderLifecycleState::PAYMENT_PAID, $order->payment_status);
        $this->assertSame(OrderLifecycleState::FULFILLMENT_PARTIALLY_FULFILLED, $order->fulfillment_status);
    }

    /**
     * TEST 10: Digital Product partial refund is rejected:
     * - no wallet credit
     * - no refund record
     * - no status mutation
     * - no access mutation
     */
    public function testDigitalProductPartialRefundIsRejected(): void
    {
        $userId = 110;
        $orderId = $this->createOrder(200.00, $userId, ProductType::DIGITAL, 200.00);

        $walletBefore = (float)$this->walletService->getBalance($userId);
        $refundCountBefore = count($this->refundRepo->findRefundsByOrderId($orderId));

        // Attempt partial refund directly via service
        $thrown = false;
        try {
            $this->refundService->processPartialRefund($orderId, 50.00, 'Partial refund attempt');
        } catch (InvalidArgumentException $e) {
            $thrown = true;
            $this->assertSame('Partial refund is not allowed for Digital Product orders.', $e->getMessage());
        }
        $this->assertTrue($thrown, "Expected InvalidArgumentException for Digital Product partial refund");

        // Attempt partial refund via Admin controller
        $request = new Request([], [
            'action'          => 'record_partial_settlement',
            'id'              => (string)$orderId,
            'refund_amount'   => '50.00',
            'retained_amount' => '150.00',
            'refund_reason'   => 'Partial attempt',
        ]);
        $this->adminController->recordPartialSettlement($request, $orderId);

        $this->assertSame('Partial refund is not allowed for Digital Product orders.', $_SESSION['flash_error'] ?? '');

        // Verify no financial or status mutation
        $walletAfter = (float)$this->walletService->getBalance($userId);
        $this->assertEquals($walletBefore, $walletAfter);

        $refundCountAfter = count($this->refundRepo->findRefundsByOrderId($orderId));
        $this->assertSame($refundCountBefore, $refundCountAfter);

        $order = $this->orderRepo->findOrder($orderId);
        $this->assertSame(OrderLifecycleState::STATUS_COMPLETED, $order->status);
        $this->assertSame(OrderLifecycleState::PAYMENT_PAID, $order->payment_status);
        $this->assertSame(OrderLifecycleState::FULFILLMENT_FULFILLED, $order->fulfillment_status);

        // Verify product access NOT revoked
        $items = $this->orderRepo->getOrderItems($orderId);
        $productId = (int)$items[0]->product_id;
        $ent = $this->entitlementRepo->findActiveEntitlement($userId, $productId);
        $this->assertNotNull($ent);
        $this->assertSame('active', $ent->status);
    }

    /**
     * TEST 11: Digital Product full refund:
     * - full wallet credit
     * - REFUNDED / REFUNDED / REVOKED
     * - product access revoked
     */
    public function testDigitalProductFullRefund(): void
    {
        $userId = 111;
        $orderId = $this->createOrder(250.00, $userId, ProductType::DIGITAL, 250.00);

        $walletBefore = (float)$this->walletService->getBalance($userId);

        $refund = $this->refundService->processRefund($orderId, 'Defective file, full refund');
        $this->assertNotNull($refund);

        $walletAfter = (float)$this->walletService->getBalance($userId);
        $this->assertEquals($walletBefore + 250.00, $walletAfter);

        $order = $this->orderRepo->findOrder($orderId);
        $this->assertSame(OrderLifecycleState::STATUS_REFUNDED, $order->status);
        $this->assertSame(OrderLifecycleState::PAYMENT_REFUNDED, $order->payment_status);
        $this->assertSame(OrderLifecycleState::FULFILLMENT_REVOKED, $order->fulfillment_status);
        $this->assertSame('250.00', $order->refunded_amount);
        $this->assertSame('0.00', $order->retained_amount);

        // Entitlement must be revoked
        $items = $this->orderRepo->getOrderItems($orderId);
        $productId = (int)$items[0]->product_id;
        $activeEnt = $this->entitlementRepo->findActiveEntitlement($userId, $productId);
        $this->assertNull($activeEnt);
    }

    /**
     * TEST 12: Digital Service partial refund:
     * - correct wallet credit
     * - PARTIAL / PARTIALLY_REFUNDED / PARTIALLY_FULFILLED
     * - retained/refunded accounting correct
     */
    public function testDigitalServicePartialRefund(): void
    {
        $userId = 112;
        $orderId = $this->createOrder(500.00, $userId, ProductType::SERVICE, 500.00);

        $walletBefore = (float)$this->walletService->getBalance($userId);

        $refund = $this->refundService->processPartialRefund($orderId, 150.00, 'Partial refund for 1 milestone');
        $this->assertNotNull($refund);

        $walletAfter = (float)$this->walletService->getBalance($userId);
        $this->assertEquals($walletBefore + 150.00, $walletAfter);

        $order = $this->orderRepo->findOrder($orderId);
        $this->assertSame(OrderLifecycleState::STATUS_PARTIAL, $order->status);
        $this->assertSame(OrderLifecycleState::PAYMENT_PARTIALLY_REFUNDED, $order->payment_status);
        $this->assertSame(OrderLifecycleState::FULFILLMENT_PARTIALLY_FULFILLED, $order->fulfillment_status);
        $this->assertSame('350.00', $order->retained_amount);
        $this->assertSame('150.00', $order->refunded_amount);
    }

    /**
     * TEST 13: Digital Service multiple partial refunds cannot exceed refundable amount.
     */
    public function testDigitalServiceMultiplePartialRefundsCannotExceedRefundableAmount(): void
    {
        $userId = 113;
        $orderId = $this->createOrder(300.00, $userId, ProductType::SERVICE, 300.00);

        $this->refundService->processPartialRefund($orderId, 100.00, 'Part 1');
        $this->refundService->processPartialRefund($orderId, 150.00, 'Part 2');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/exceeds the maximum refundable amount of 50\.00/');

        $this->refundService->processPartialRefund($orderId, 60.00, 'Part 3 - invalid');
    }

    /**
     * TEST 14: Full refund cannot exceed refundable amount.
     */
    public function testFullRefundCannotExceedRefundableAmount(): void
    {
        $userId = 114;
        $orderId = $this->createOrder(300.00, $userId, ProductType::SERVICE, 300.00);

        $firstRefund = $this->refundService->processRefund($orderId, 'Full refund');
        $walletAfterFirst = (float)$this->walletService->getBalance($userId);

        $secondRefund = $this->refundService->processRefund($orderId, 'Second full refund');
        $walletAfterSecond = (float)$this->walletService->getBalance($userId);

        // Wallet was NOT credited again, idempotent handling prevents exceeding refundable amount
        $this->assertEquals($walletAfterFirst, $walletAfterSecond);
        $this->assertSame($firstRefund->id, $secondRefund->id);

        // Attempting partial refund on already refunded order must fail
        $thrown = false;
        try {
            $this->refundService->processPartialRefund($orderId, 10.00, 'Partial on refunded');
        } catch (InvalidArgumentException $e) {
            $thrown = true;
            $this->assertStringContainsString('already fully refunded', $e->getMessage());
        }
        $this->assertTrue($thrown);
    }

    /**
     * TEST 15: Cancelled payment:
     * - CANCELLED / FAILED / CANCELLED
     * - zero refund
     * - zero wallet credit
     * - no refund record
     */
    public function testCancelledPayment(): void
    {
        $userId = 115;
        $orderId = $this->createOrder(400.00, $userId, ProductType::DIGITAL);

        $walletBefore = (float)$this->walletService->getBalance($userId);

        $this->adminController->cancelSingleOrder($orderId, 'Order cancelled by admin');

        $order = $this->orderRepo->findOrder($orderId);
        $this->assertSame(OrderLifecycleState::STATUS_CANCELLED, $order->status);
        $this->assertSame(OrderLifecycleState::PAYMENT_FAILED, $order->payment_status);
        $this->assertSame(OrderLifecycleState::FULFILLMENT_CANCELLED, $order->fulfillment_status);
        $this->assertSame('0.00', $order->refunded_amount ?? '0.00');

        $walletAfter = (float)$this->walletService->getBalance($userId);
        $this->assertEquals($walletBefore, $walletAfter);

        $refunds = $this->refundRepo->findRefundsByOrderId($orderId);
        $this->assertEmpty($refunds);
    }

    /**
     * TEST 16: A cancelled previous payment does not prevent a new wallet payment attempt.
     */
    public function testCancelledPreviousPaymentDoesNotPreventNewWalletPaymentAttempt(): void
    {
        $userId = 116;
        $orderId = $this->createOrder(190.00, $userId, ProductType::DIGITAL);

        // Record a previously failed/cancelled payment attempt on this order
        $this->orderRepo->createOrderPayment([
            'order_id'           => $orderId,
            'payment_method'     => 'favorite_pay',
            'favorite_pay_tx_id' => 'tx_failed_prev',
            'wallet_tx_id'       => null,
            'amount_paid'        => '190.00',
            'currency'           => 'BDT',
            'status'             => 'cancelled',
            'created_at'         => date('Y-m-d H:i:s'),
            'updated_at'         => date('Y-m-d H:i:s'),
        ]);

        $this->walletService->credit($userId, '500.00', 'dep_116', 'Wallet deposit');

        // Payment attempt must succeed and pay the full remaining amount
        $order = $this->checkoutService->processWalletPayment($orderId, $userId);

        $this->assertSame(OrderLifecycleState::STATUS_COMPLETED, $order->status);
        $this->assertSame(OrderLifecycleState::PAYMENT_PAID, $order->payment_status);
        $this->assertSame(OrderLifecycleState::FULFILLMENT_FULFILLED, $order->fulfillment_status);
        $this->assertEquals(310.00, (float)$this->walletService->getBalance($userId));
    }

    /**
     * TEST 17: Two genuinely active/conflicting payment attempts remain protected by duplicate-payment protection.
     */
    public function testTwoGenuinelyActiveConflictingPaymentAttemptsProtected(): void
    {
        $userId = 117;
        $orderId = $this->createOrder(100.00, $userId, ProductType::DIGITAL);
        $this->walletService->credit($userId, '300.00', 'dep_117', 'Wallet deposit');

        $this->checkoutService->processWalletPayment($orderId, $userId);

        // Attempting to pay again must be blocked
        $this->expectException(CheckoutException::class);
        $this->expectExceptionMessageMatches('/already paid/');

        $this->checkoutService->processWalletPayment($orderId, $userId);
    }

    /**
     * TEST 18: If wallet debit succeeds but final order transaction fails:
     * - wallet debit is reversed
     * - order is not falsely marked paid
     */
    public function testIfWalletDebitSucceedsButFinalOrderTransactionFailsWalletDebitIsReversed(): void
    {
        $userId = 118;
        $orderId = $this->createOrder(120.00, $userId, ProductType::DIGITAL);
        $this->walletService->credit($userId, '200.00', 'dep_118', 'Wallet deposit');

        // Create failing mock order repository that throws during createOrderPayment
        $failingOrderRepo = new class($this->db) extends OrderRepository {
            public function createOrderPayment(array $data): int {
                throw new \RuntimeException("Database disk failure during payment creation");
            }
        };

        $checkoutWithFailure = new CheckoutService(
            $failingOrderRepo,
            $this->walletService,
            null,
            $this->db,
            $this->fulfillmentService
        );

        $thrown = false;
        try {
            $checkoutWithFailure->processWalletPayment($orderId, $userId);
        } catch (\RuntimeException $e) {
            $thrown = true;
            $this->assertSame("Database disk failure during payment creation", $e->getMessage());
        }
        $this->assertTrue($thrown);

        // Customer's balance must be fully restored (debit reversed)
        $walletBal = (float)$this->walletService->getBalance($userId);
        $this->assertEquals(200.00, $walletBal, "Wallet balance must be restored when post-debit transaction fails");

        // Order must NOT be marked paid
        $order = $this->orderRepo->findOrder($orderId);
        $this->assertNotSame(OrderLifecycleState::PAYMENT_PAID, $order->payment_status);
    }

    /**
     * TEST 19: Existing Digital Product download authorization still works after valid full payment.
     */
    public function testDigitalProductDownloadAuthorizationWorksAfterValidFullPayment(): void
    {
        $userId = 119;
        $orderId = $this->createOrder(100.00, $userId, ProductType::DIGITAL, 100.00);

        $items = $this->orderRepo->getOrderItems($orderId);
        $productId = (int)$items[0]->product_id;
        $ent = $this->entitlementRepo->findActiveEntitlement($userId, $productId);

        $tokenRecord = $this->downloadService->getOrCreateDownloadToken($userId, $productId, (int)$ent->id);
        $this->assertNotNull($tokenRecord);

        // Authorize download using token
        $auth = $this->downloadService->authorizeDownload($tokenRecord->download_token, $userId);
        $this->assertNotNull($auth);
        $this->assertSame('sample.zip', $auth['file_name']);
    }

    /**
     * TEST 20: Digital Product download is denied after full refund/revocation.
     */
    public function testDigitalProductDownloadDeniedAfterFullRefund(): void
    {
        $userId = 120;
        $orderId = $this->createOrder(100.00, $userId, ProductType::DIGITAL, 100.00);

        $items = $this->orderRepo->getOrderItems($orderId);
        $productId = (int)$items[0]->product_id;
        $ent = $this->entitlementRepo->findActiveEntitlement($userId, $productId);
        $tokenRecord = $this->downloadService->getOrCreateDownloadToken($userId, $productId, (int)$ent->id);

        // Process full refund
        $this->refundService->processRefund($orderId, 'Refund and revoke');

        // Customer download attempt must throw DownloadException::entitlementRevoked
        $this->expectException(DownloadException::class);
        $this->expectExceptionMessageMatches('/revoked/');

        $this->downloadService->authorizeDownload($tokenRecord->download_token, $userId);
    }

    /**
     * TEST 21: Existing Digital Service deliverable authorization remains correct.
     */
    public function testDigitalServiceDeliverableAuthorizationRemainsCorrect(): void
    {
        $userId = 121;
        $orderId = $this->createOrder(300.00, $userId, ProductType::SERVICE, 300.00);

        // Add deliverable
        $deliverableId = $this->orderRepo->createDeliverable([
            'order_id'       => $orderId,
            'title'          => 'Final Project Report',
            'resource_type'  => 'url',
            'resource_url'   => 'https://example.com/report.pdf',
            'is_released'    => 1, // Released
            'download_token' => 'tok_service_deliv_' . bin2hex(random_bytes(16)),
        ]);
        $deliv = $this->orderRepo->findDeliverable($deliverableId);

        $auth = $this->downloadService->authorizeDownload($deliv->download_token, $userId);
        $this->assertNotNull($auth);
        $this->assertTrue($auth['is_deliverable']);
        $this->assertSame('https://example.com/report.pdf', $auth['resource_url']);

        // If unreleased, access must be denied
        $unreleasedId = $this->orderRepo->createDeliverable([
            'order_id'       => $orderId,
            'title'          => 'Draft Working File',
            'resource_type'  => 'url',
            'resource_url'   => 'https://example.com/draft.pdf',
            'is_released'    => 0, // Unreleased
            'download_token' => 'tok_draft_' . bin2hex(random_bytes(16)),
        ]);
        $delivUnreleased = $this->orderRepo->findDeliverable($unreleasedId);

        $thrown = false;
        try {
            $this->downloadService->authorizeDownload($delivUnreleased->download_token, $userId);
        } catch (DownloadException $e) {
            $thrown = true;
            $this->assertStringContainsString('not been released yet', $e->getMessage());
        }
        $this->assertTrue($thrown);
    }

    /**
     * TEST 22: Cross-customer download protection remains intact.
     */
    public function testCrossCustomerDownloadProtectionIntact(): void
    {
        $userA = 122;
        $userB = 123;
        $orderId = $this->createOrder(100.00, $userA, ProductType::DIGITAL, 100.00);

        $items = $this->orderRepo->getOrderItems($orderId);
        $productId = (int)$items[0]->product_id;
        $tokenRecord = $this->downloadService->getOrCreateDownloadToken($userA, $productId);

        // User B attempts to access User A's token
        $this->expectException(DownloadException::class);
        $this->expectExceptionMessageMatches('/not authorized/');

        $this->downloadService->authorizeDownload($tokenRecord->download_token, $userB);
    }

    /**
     * TEST 23: Order dropdown is strictly restricted to the 4 selectable statuses (Processing, Partial, Complete, Refund).
     * Cancel and Pending are payment-controlled states and are strictly rejected in updateStatus.
     */
    public function testOrderDropdownRestrictedToFourSelectableStatusesAndRejectsCancelAndPending(): void
    {
        // 1. Validate internal architecture defines strictly the 4 selectable statuses
        $selectable = OrderLifecycleState::selectableAdminStatuses();
        $this->assertSame([
            'processing' => 'Processing',
            'partial'    => 'Partial',
            'completed'  => 'Complete',
            'refunded'   => 'Refund',
        ], $selectable);

        $this->assertTrue(OrderLifecycleState::isSelectableAdminStatus('processing'));
        $this->assertTrue(OrderLifecycleState::isSelectableAdminStatus('partial'));
        $this->assertTrue(OrderLifecycleState::isSelectableAdminStatus('completed'));
        $this->assertTrue(OrderLifecycleState::isSelectableAdminStatus('complete'));
        $this->assertTrue(OrderLifecycleState::isSelectableAdminStatus('refunded'));
        $this->assertTrue(OrderLifecycleState::isSelectableAdminStatus('refund'));

        // Payment-controlled states must NOT be selectable
        $this->assertFalse(OrderLifecycleState::isSelectableAdminStatus('pending'));
        $this->assertFalse(OrderLifecycleState::isSelectableAdminStatus('cancelled'));
        $this->assertFalse(OrderLifecycleState::isSelectableAdminStatus('cancel'));
        $this->assertFalse(OrderLifecycleState::isSelectableAdminStatus('failed'));

        // 2. AdminOrderController::updateStatus rejects any attempt to submit 'cancelled' or 'pending'
        $userId = 126;
        $orderId = $this->createOrder(100.00, $userId, ProductType::DIGITAL);

        $_SESSION['flash_error'] = '';
        $reqCancel = new Request([], ['status' => 'cancelled']);
        $this->adminController->updateStatus($reqCancel, $orderId);

        $this->assertStringContainsString('Invalid status option', $_SESSION['flash_error']);
        $this->assertStringContainsString('Processing, Partial, Complete, or Refund', $_SESSION['flash_error']);

        $_SESSION['flash_error'] = '';
        $reqPending = new Request([], ['status' => 'pending']);
        $this->adminController->updateStatus($reqPending, $orderId);

        $this->assertStringContainsString('Invalid status option', $_SESSION['flash_error']);
    }

    /**
     * TEST 24: Automatic payment failure and wallet payment failure transition order to Cancel
     * with 0 refund, 0 wallet credit, and no refund record.
     */
    public function testAutomaticAndWalletPaymentFailureTransitionsToCancelWithoutRefundOrWalletCredit(): void
    {
        // 1. Automatic payment gateway failure
        $userIdA = 127;
        $orderIdA = $this->createOrder(150.00, $userIdA, ProductType::DIGITAL);
        $mockPayService = $this->createMock(PaymentServiceInterface::class);
        $mockPayService->method('getIntent')
            ->willReturn(new PaymentIntent('pi_fail_test', 'favorite-digital', (string)$orderIdA, Money::bdt(15000), Money::bdt(0), PaymentStatus::FAILED));

        $checkout = new CheckoutService($this->orderRepo, $this->walletService, $mockPayService, $this->db, $this->fulfillmentService);
        $orderA = $checkout->verifyAndSettlePayment($orderIdA, 'pi_fail_test');

        $this->assertSame(OrderLifecycleState::STATUS_CANCELLED, $orderA->status);
        $this->assertSame(OrderLifecycleState::PAYMENT_FAILED, $orderA->payment_status);
        $this->assertSame(OrderLifecycleState::FULFILLMENT_CANCELLED, $orderA->fulfillment_status);

        // Verify no wallet credit and no refund records
        $this->assertEquals(0.00, (float)$this->walletService->getBalance($userIdA));
        $this->assertEmpty($this->refundRepo->findRefundsByOrderId($orderIdA));

        // 2. Wallet payment failure (e.g. debit fails)
        $userIdB = 128;
        $orderIdB = $this->createOrder(200.00, $userIdB, ProductType::DIGITAL);
        // User has 0 balance, debit will fail
        $thrown = false;
        try {
            $this->checkoutService->processWalletPayment($orderIdB, $userIdB);
        } catch (\Throwable $e) {
            $thrown = true;
        }
        $this->assertTrue($thrown, "Expected exception on insufficient wallet balance");

        $orderB = $this->orderRepo->findOrder($orderIdB);
        $this->assertSame(OrderLifecycleState::STATUS_CANCELLED, $orderB->status);
        $this->assertSame(OrderLifecycleState::PAYMENT_FAILED, $orderB->payment_status);
        $this->assertSame(OrderLifecycleState::FULFILLMENT_CANCELLED, $orderB->fulfillment_status);
        $this->assertEquals(0.00, (float)$this->walletService->getBalance($userIdB));
        $this->assertEmpty($this->refundRepo->findRefundsByOrderId($orderIdB));
    }

    /**
     * TEST 25: Favorite Pay manual payment rejection transitions order to Cancel
     * with 0 refund, 0 wallet credit, and no refund record.
     */
    public function testFavoritePayRejectionTransitionsOrderToCancel(): void
    {
        $userId = 129;
        $orderId = $this->createOrder(300.00, $userId, ProductType::SERVICE);

        $walletBefore = (float)$this->walletService->getBalance($userId);

        $msg = $this->adminController->cancelSingleOrder($orderId, 'Payment rejected by payment gateway.');
        $this->assertStringContainsString('cancelled', $msg);

        $order = $this->orderRepo->findOrder($orderId);
        $this->assertSame(OrderLifecycleState::STATUS_CANCELLED, $order->status);
        $this->assertSame(OrderLifecycleState::PAYMENT_FAILED, $order->payment_status);
        $this->assertSame(OrderLifecycleState::FULFILLMENT_CANCELLED, $order->fulfillment_status);
        $this->assertSame('0.00', $order->refunded_amount ?? '0.00');

        $this->assertEquals($walletBefore, (float)$this->walletService->getBalance($userId));
        $this->assertEmpty($this->refundRepo->findRefundsByOrderId($orderId));
    }

    /**
     * TEST 26: Final Service partial refund transitions order to Refund / Revoked and revokes access.
     */
    public function testFinalServicePartialRefundTransitionsToRefundAndRevokesAccess(): void
    {
        $userId = 130;
        $orderId = $this->createOrder(300.00, $userId, ProductType::SERVICE, 300.00);

        // Add a released deliverable
        $deliverableId = $this->orderRepo->createDeliverable([
            'order_id'       => $orderId,
            'title'          => 'Final Service Output',
            'resource_type'  => 'url',
            'resource_url'   => 'https://example.com/output.pdf',
            'is_released'    => 1,
            'download_token' => 'tok_service_fin_' . bin2hex(random_bytes(16)),
        ]);
        $deliv = $this->orderRepo->findDeliverable($deliverableId);

        // Initial check: deliverable is accessible
        $auth1 = $this->downloadService->authorizeDownload($deliv->download_token, $userId);
        $this->assertNotNull($auth1);

        // 1st partial refund: 100 BDT
        $this->refundService->processPartialRefund($orderId, 100.00, 'Milestone 1 partial');
        $order1 = $this->orderRepo->findOrder($orderId);
        $this->assertSame(OrderLifecycleState::STATUS_PARTIAL, $order1->status);
        // Deliverable STILL accessible after partial refund!
        $auth2 = $this->downloadService->authorizeDownload($deliv->download_token, $userId);
        $this->assertNotNull($auth2);

        // 2nd and FINAL partial refund: remaining 200 BDT (exhausts refundable balance)
        $this->refundService->processPartialRefund($orderId, 200.00, 'Milestone 2 final remaining refund');
        $orderFinal = $this->orderRepo->findOrder($orderId);

        // Must automatically transition to REFUNDED / REFUNDED / REVOKED
        $this->assertSame(OrderLifecycleState::STATUS_REFUNDED, $orderFinal->status);
        $this->assertSame(OrderLifecycleState::PAYMENT_REFUNDED, $orderFinal->payment_status);
        $this->assertSame(OrderLifecycleState::FULFILLMENT_REVOKED, $orderFinal->fulfillment_status);

        // Now deliverable access MUST be revoked!
        $this->expectException(DownloadException::class);
        $this->expectExceptionMessageMatches('/revoked/');

        $this->downloadService->authorizeDownload($deliv->download_token, $userId);
    }

    /**
     * TEST 27: Admin view template contains ONLY the four selectable dropdown options
     * and contains NO duplicate control panels (Manual Payment Decisions, Partial Settlement, Service Progress).
     */
    public function testViewTemplateContainsOnlyFourSelectableDropdownOptions(): void
    {
        $viewFile = dirname(__DIR__) . '/views/admin/orders/view.php';
        $this->assertFileExists($viewFile);
        $content = file_get_contents($viewFile);

        // Dropdown contains strictly the 4 allowed options
        $this->assertStringContainsString('value="processing"', $content);
        $this->assertStringContainsString('value="partial"', $content);
        $this->assertStringContainsString('value="completed"', $content);
        $this->assertStringContainsString('value="refunded"', $content);

        // Dropdown does NOT contain Cancel or Pending as a selectable option in the select
        $this->assertDoesNotMatchRegularExpression('/<select[^>]*name="status"[^>]*>[\s\S]*?<option[^>]*value="cancelled"/i', $content);

        // Exactly ONE <select name="status" exists on the page
        $matches = [];
        preg_match_all('/<select[^>]*name="status"/i', $content, $matches);
        $this->assertCount(1, $matches[0], 'Must contain exactly ONE editable Order Status dropdown on the entire page.');

        // Does NOT have separate editable dropdowns for payment_status or fulfillment_status
        $this->assertDoesNotMatchRegularExpression('/<select[^>]*name="payment_status"/i', $content);
        $this->assertDoesNotMatchRegularExpression('/<select[^>]*name="fulfillment_status"/i', $content);

        // Does NOT have duplicate panels
        $this->assertStringNotContainsString('Manual Payment Decisions', $content);
        $this->assertStringNotContainsString('partial-settlement-panel', $content);
        $this->assertStringNotContainsString('fd-service-progress-box', $content);

        // Displays read-only badges
        $this->assertStringContainsString('Payment Status:', $content);
        $this->assertStringContainsString('Fulfillment Status:', $content);

        // Has dark mode semantic tokens
        $this->assertStringContainsString('var(--admin-surface', $content);
        $this->assertStringContainsString('var(--admin-border', $content);
        $this->assertStringContainsString('var(--admin-text', $content);
    }

    /**
     * TEST 28: AdminOrderController rejects refund amount > remaining refundable with exact formatted error message:
     * "Refund amount cannot exceed the remaining refundable amount of ৳10.00."
     */
    public function testOrderControllerRejectRefundAmountExceedingRemainingBalanceWithExactMessage(): void
    {
        $userId = 131;
        $orderId = $this->createOrder(10.00, $userId, ProductType::SERVICE, 10.00);

        $walletBefore = (float)$this->walletService->getBalance($userId);
        $refundCountBefore = count($this->refundRepo->findRefundsByOrderId($orderId));

        $_SESSION['flash_error'] = '';
        $req = new Request([], [
            'status'        => 'partial',
            'refund_amount' => '59.00',
            'refund_reason' => 'Too much requested',
        ]);

        $this->adminController->updateStatus($req, $orderId);

        $this->assertSame(
            'Refund amount cannot exceed the remaining refundable amount of ৳10.00.',
            $_SESSION['flash_error']
        );

        // Zero wallet mutation
        $this->assertEquals($walletBefore, (float)$this->walletService->getBalance($userId));

        // Zero refund records created
        $this->assertCount($refundCountBefore, $this->refundRepo->findRefundsByOrderId($orderId));

        // Order status unchanged
        $order = $this->orderRepo->findOrder($orderId);
        $this->assertSame(OrderLifecycleState::STATUS_PROCESSING, $order->status);
    }

    /**
     * TEST 29: AdminOrderController blocks Partial refund on Digital Product via main Order Status action
     * with exact message "Partial refund is not allowed for Digital Product orders."
     */
    public function testOrderControllerDigitalProductPartialRefundIsBlockedViaOrderStatus(): void
    {
        $userId = 132;
        $orderId = $this->createOrder(100.00, $userId, ProductType::DIGITAL, 100.00);

        $walletBefore = (float)$this->walletService->getBalance($userId);
        $refundCountBefore = count($this->refundRepo->findRefundsByOrderId($orderId));

        $_SESSION['flash_error'] = '';
        $req = new Request([], [
            'status'        => 'partial',
            'refund_amount' => '25.00',
        ]);

        $this->adminController->updateStatus($req, $orderId);

        $this->assertSame(
            'Partial refund is not allowed for Digital Product orders.',
            $_SESSION['flash_error']
        );

        // Zero wallet mutation
        $this->assertEquals($walletBefore, (float)$this->walletService->getBalance($userId));

        // Zero refund records created
        $this->assertCount($refundCountBefore, $this->refundRepo->findRefundsByOrderId($orderId));

        // Order status remains completed
        $order = $this->orderRepo->findOrder($orderId);
        $this->assertSame(OrderLifecycleState::STATUS_COMPLETED, $order->status);
    }

    /**
     * TEST 30: Full refund after a partial refund refunds ONLY the remaining balance,
     * sets retained_amount = 0.00 and cumulative refunded_amount = totalPaid,
     * and revokes deliverables.
     */
    public function testFullRefundAfterPartialRefundRefundsOnlyRemainingBalanceAndCorrectsCumulativeAccounting(): void
    {
        $userId = 133;
        $orderId = $this->createOrder(300.00, $userId, ProductType::SERVICE, 300.00);

        // Add deliverable
        $delivId = $this->orderRepo->createDeliverable([
            'order_id'       => $orderId,
            'title'          => 'Service Report',
            'resource_type'  => 'url',
            'resource_url'   => 'https://example.com/report.pdf',
            'is_released'    => 1,
            'download_token' => 'tok_deliv_part_full_' . bin2hex(random_bytes(16)),
        ]);
        $deliv = $this->orderRepo->findDeliverable($delivId);

        $walletStart = (float)$this->walletService->getBalance($userId);

        // 1. Partial refund of 100.00 BDT
        $_SESSION['flash_error'] = '';
        $reqPartial = new Request([], [
            'status'        => 'partial',
            'refund_amount' => '100.00',
            'refund_reason' => 'First partial settlement',
        ]);
        $this->adminController->updateStatus($reqPartial, $orderId);

        $this->assertEquals($walletStart + 100.00, (float)$this->walletService->getBalance($userId));
        $orderPart = $this->orderRepo->findOrder($orderId);
        $this->assertSame(OrderLifecycleState::STATUS_PARTIAL, $orderPart->status);
        $this->assertSame('100.00', $orderPart->refunded_amount);
        $this->assertSame('200.00', $orderPart->retained_amount);

        // Deliverable still accessible
        $this->assertNotNull($this->downloadService->authorizeDownload($deliv->download_token, $userId));

        // 2. Issue Full Refund via main Order Status action
        $_SESSION['flash_error'] = '';
        $reqFull = new Request([], [
            'status'             => 'refunded',
            'full_refund_reason' => 'Customer requested full cancellation of remaining service',
        ]);
        $this->adminController->updateStatus($reqFull, $orderId);

        // Wallet must have received ONLY the remaining 200.00 (total increase = 300.00)
        $this->assertEquals($walletStart + 300.00, (float)$this->walletService->getBalance($userId));

        // Final order lifecycle states
        $orderFull = $this->orderRepo->findOrder($orderId);
        $this->assertSame(OrderLifecycleState::STATUS_REFUNDED, $orderFull->status);
        $this->assertSame(OrderLifecycleState::PAYMENT_REFUNDED, $orderFull->payment_status);
        $this->assertSame(OrderLifecycleState::FULFILLMENT_REVOKED, $orderFull->fulfillment_status);

        // Cumulative accounting: retained = 0.00, refunded = 300.00
        $this->assertSame('0.00', $orderFull->retained_amount);
        $this->assertSame('300.00', $orderFull->refunded_amount);

        // Exactly 2 refund records exist (1 partial for 100.00, 1 full for 200.00)
        $refunds = $this->refundRepo->findRefundsByOrderId($orderId);
        $this->assertCount(2, $refunds);

        // Deliverable access is now revoked
        $this->expectException(DownloadException::class);
        $this->expectExceptionMessageMatches('/revoked/');
        $this->downloadService->authorizeDownload($deliv->download_token, $userId);
    }

    /**
     * TEST 31: Repeated Full Refund submissions do not credit wallet twice (terminal state protection).
     */
    public function testRepeatedFullRefundSubmissionsDoNotCreditWalletTwice(): void
    {
        $userId = 134;
        $orderId = $this->createOrder(150.00, $userId, ProductType::DIGITAL, 150.00);

        // Issue initial full refund via updateStatus
        $req1 = new Request([], [
            'status'             => 'refunded',
            'full_refund_reason' => 'Initial full refund',
        ]);
        $this->adminController->updateStatus($req1, $orderId);

        $walletAfterRefund1 = (float)$this->walletService->getBalance($userId);
        $refundCount1 = count($this->refundRepo->findRefundsByOrderId($orderId));

        // Attempt repeated full refund
        $_SESSION['flash_error'] = '';
        $req2 = new Request([], [
            'status'             => 'refunded',
            'full_refund_reason' => 'Duplicate full refund attempt',
        ]);
        $this->adminController->updateStatus($req2, $orderId);

        $this->assertStringContainsString('already fully refunded', $_SESSION['flash_error']);

        // Zero wallet mutation
        $this->assertEquals($walletAfterRefund1, (float)$this->walletService->getBalance($userId));

        // Zero additional refund records
        $this->assertCount($refundCount1, $this->refundRepo->findRefundsByOrderId($orderId));
    }

    /**
     * TEST 32: Digital Service partial refund increases Favorite Pay authoritative wallet balance
     * and creates Favorite Pay ledger entry.
     */
    public function testDigitalServicePartialRefundCreditsFavoritePayWalletAndCreatesLedgerEntry(): void
    {
        $userId = 201;
        $orderId = $this->createOrder(190.00, $userId, ProductType::SERVICE, 190.00);

        $initialFpBalance = $this->favPayWalletService->getBalance($userId)->getAmount();
        $this->assertSame(0, $initialFpBalance);

        // Admin issues Partial Refund of BDT 100.00 via updateStatus
        $req = new Request([], [
            'status'        => 'partial',
            'refund_amount' => '100.00',
            'refund_reason' => 'Partial service settlement',
        ]);
        $this->adminController->updateStatus($req, $orderId);

        // 1. Authoritative Favorite Pay wallet balance must increase to 100.00 BDT (10000 minor units)
        $newFpBalance = $this->favPayWalletService->getBalance($userId)->getAmount();
        $this->assertSame(10000, $newFpBalance);
        $this->assertSame('100.00', $this->walletService->getBalance($userId));

        // 2. Direct database verification on favorite_pay_wallets
        $fpWalletRow = $this->db->selectOne("SELECT * FROM favorite_pay_wallets WHERE user_id = ?", [$userId]);
        $this->assertNotNull($fpWalletRow);
        $this->assertSame(10000, (int)$fpWalletRow->balance);

        // 3. Direct database verification on favorite_pay_wallet_entries
        $fpEntries = $this->db->select("SELECT * FROM favorite_pay_wallet_entries WHERE user_id = ?", [$userId]);
        $this->assertCount(1, $fpEntries);
        $this->assertSame('credit', $fpEntries[0]->type);
        $this->assertSame(10000, (int)$fpEntries[0]->amount);
        $this->assertSame(10000, (int)$fpEntries[0]->balance_after);

        // 4. Order and refund record verification
        $order = $this->orderRepo->findOrder($orderId);
        $this->assertSame(OrderLifecycleState::STATUS_PARTIAL, $order->status);
        $this->assertSame(OrderLifecycleState::PAYMENT_PARTIALLY_REFUNDED, $order->payment_status);
        $this->assertSame(OrderLifecycleState::FULFILLMENT_PARTIALLY_FULFILLED, $order->fulfillment_status);
        $this->assertSame('90.00', $order->retained_amount);
        $this->assertSame('100.00', $order->refunded_amount);

        $refunds = $this->refundRepo->findRefundsByOrderId($orderId);
        $this->assertCount(1, $refunds);
        $this->assertSame('completed', $refunds[0]->status);
        $this->assertSame('100.00', $refunds[0]->refund_amount);
        $this->assertSame('wallet', $refunds[0]->destination);
        $this->assertSame($fpEntries[0]->reference_id, $refunds[0]->reference);
    }

    /**
     * TEST 33: Digital Service full refund after partial refund credits only remaining amount to Favorite Pay.
     */
    public function testDigitalServiceFullRefundAfterPartialRefundCreditsOnlyRemainingAmount(): void
    {
        $userId = 202;
        $orderId = $this->createOrder(190.00, $userId, ProductType::SERVICE, 190.00);

        // 1. Partial refund: 100.00
        $req1 = new Request([], [
            'status'        => 'partial',
            'refund_amount' => '100.00',
            'refund_reason' => 'Phase 1 partial refund',
        ]);
        $this->adminController->updateStatus($req1, $orderId);

        $this->assertSame(10000, $this->favPayWalletService->getBalance($userId)->getAmount());

        // 2. Full refund of remaining amount (90.00)
        $req2 = new Request([], [
            'status'             => 'refunded',
            'full_refund_reason' => 'Phase 2 final refund',
        ]);
        $this->adminController->updateStatus($req2, $orderId);

        // Authoritative Favorite Pay wallet balance must now be 190.00 BDT (19000 minor units)
        $this->assertSame(19000, $this->favPayWalletService->getBalance($userId)->getAmount());
        $this->assertSame('190.00', $this->walletService->getBalance($userId));

        // Exactly 2 entries in favorite_pay_wallet_entries
        $fpEntries = $this->db->select("SELECT * FROM favorite_pay_wallet_entries WHERE user_id = ? ORDER BY id ASC", [$userId]);
        $this->assertCount(2, $fpEntries);
        $this->assertSame(10000, (int)$fpEntries[0]->amount);
        $this->assertSame(9000, (int)$fpEntries[1]->amount);
        $this->assertSame(19000, (int)$fpEntries[1]->balance_after);

        // Order lifecycle transitions to refunded & revoked
        $order = $this->orderRepo->findOrder($orderId);
        $this->assertSame(OrderLifecycleState::STATUS_REFUNDED, $order->status);
        $this->assertSame(OrderLifecycleState::PAYMENT_REFUNDED, $order->payment_status);
        $this->assertSame(OrderLifecycleState::FULFILLMENT_REVOKED, $order->fulfillment_status);
        $this->assertSame('0.00', $order->retained_amount);
        $this->assertSame('190.00', $order->refunded_amount);
    }

    /**
     * TEST 34: Digital Product partial refund is rejected with exact error and produces zero wallet credit.
     */
    public function testDigitalProductPartialRefundIsRejectedAndProducesZeroWalletCredit(): void
    {
        $userId = 203;
        $orderId = $this->createOrder(190.00, $userId, ProductType::DIGITAL, 190.00);

        $_SESSION['flash_error'] = '';
        $req = new Request([], [
            'status'        => 'partial',
            'refund_amount' => '100.00',
            'refund_reason' => 'Attempt partial refund on ebook',
        ]);
        $this->adminController->updateStatus($req, $orderId);

        // Exact error message
        $this->assertSame("Partial refund is not allowed for Digital Product orders.", $_SESSION['flash_error']);

        // Zero wallet credit in Favorite Pay
        $this->assertSame(0, $this->favPayWalletService->getBalance($userId)->getAmount());
        $this->assertSame('0.00', $this->walletService->getBalance($userId));

        // Zero refund records
        $this->assertEmpty($this->refundRepo->findRefundsByOrderId($orderId));

        // Order status unchanged
        $order = $this->orderRepo->findOrder($orderId);
        $this->assertSame(OrderLifecycleState::STATUS_COMPLETED, $order->status);

        // Entitlement remains active
        $entitlements = $this->entitlementRepo->getEntitlementsByUser($userId);
        $this->assertNotEmpty($entitlements);
        $this->assertSame('active', $entitlements[0]->status);
    }

    /**
     * TEST 35: Digital Product full refund credits Favorite Pay wallet correctly and revokes access.
     */
    public function testDigitalProductFullRefundCreditsFavoritePayWalletAndRevokesAccess(): void
    {
        $userId = 204;
        $orderId = $this->createOrder(190.00, $userId, ProductType::DIGITAL, 190.00);

        $req = new Request([], [
            'status'             => 'refunded',
            'full_refund_reason' => 'Customer unsatisfied with digital download',
        ]);
        $this->adminController->updateStatus($req, $orderId);

        // Favorite Pay wallet balance increased by full BDT 190.00
        $this->assertSame(19000, $this->favPayWalletService->getBalance($userId)->getAmount());
        $this->assertSame('190.00', $this->walletService->getBalance($userId));

        // Ledger entry in favorite_pay_wallet_entries
        $fpEntries = $this->db->select("SELECT * FROM favorite_pay_wallet_entries WHERE user_id = ?", [$userId]);
        $this->assertCount(1, $fpEntries);
        $this->assertSame(19000, (int)$fpEntries[0]->amount);

        // Refund record created
        $refunds = $this->refundRepo->findRefundsByOrderId($orderId);
        $this->assertCount(1, $refunds);
        $this->assertSame('190.00', $refunds[0]->refund_amount);
        $this->assertSame('completed', $refunds[0]->status);

        // Entitlement revoked
        $entitlements = $this->entitlementRepo->getEntitlementsByUser($userId);
        $this->assertNotEmpty($entitlements);
        $this->assertSame('revoked', $entitlements[0]->status);
    }

    /**
     * TEST 36: Correct customer wallet receives money, not admin or another user.
     */
    public function testCorrectCustomerWalletReceivesMoneyAndNotAdminOrSeller(): void
    {
        $customerUserId = 205;
        $adminUserId = 999;
        $_SESSION['auth_user_id'] = $adminUserId;

        $orderId = $this->createOrder(190.00, $customerUserId, ProductType::SERVICE, 190.00);

        $req = new Request([], [
            'status'        => 'partial',
            'refund_amount' => '75.00',
            'refund_reason' => 'Scope reduction',
        ]);
        $this->adminController->updateStatus($req, $orderId);

        // Customer wallet received 75.00 BDT
        $this->assertSame(7500, $this->favPayWalletService->getBalance($customerUserId)->getAmount());

        // Admin wallet received 0.00 BDT
        $this->assertSame(0, $this->favPayWalletService->getBalance($adminUserId)->getAmount());
    }

    /**
     * TEST 37: Refund record references the real Favorite Pay wallet transaction.
     */
    public function testRefundRecordReferencesRealFavoritePayWalletTransaction(): void
    {
        $userId = 206;
        $orderId = $this->createOrder(190.00, $userId, ProductType::SERVICE, 190.00);

        $refund = $this->refundService->processPartialRefund($orderId, '80.00', 'Partial refund test', 'wallet');
        $this->assertNotNull($refund);

        $fpEntry = $this->db->selectOne("SELECT * FROM favorite_pay_wallet_entries WHERE user_id = ?", [$userId]);
        $this->assertNotNull($fpEntry);

        // Verify that the refund reference matches the Favorite Pay ledger entry reference
        $this->assertSame($fpEntry->reference_id, $refund->reference);
        $this->assertNotNull($refund->wallet_transaction_id);

        // Verify Favorite Digital transaction table matches
        $fdTx = $this->db->selectOne("SELECT * FROM favorite_digital_wallet_transactions WHERE id = ?", [$refund->wallet_transaction_id]);
        $this->assertNotNull($fdTx);
        $this->assertSame($fpEntry->reference_id, $fdTx->reference_id);
    }

    /**
     * TEST 38: Wallet credit failure does not create a COMPLETED refund or false records.
     */
    public function testWalletCreditFailureDoesNotCreateCompletedRefund(): void
    {
        $userId = 207;
        $orderId = $this->createOrder(190.00, $userId, ProductType::SERVICE, 190.00);

        // Inject a failing Favorite Pay wallet service
        $failingFavPay = new class implements \FavoriteCMS\Pay\Contracts\WalletServiceInterface {
            public function getBalance(int $userId): \FavoriteCMS\Pay\Domain\Money { return \FavoriteCMS\Pay\Domain\Money::bdt(0); }
            public function getAvailableBalance(int $userId): \FavoriteCMS\Pay\Domain\Money { return \FavoriteCMS\Pay\Domain\Money::bdt(0); }
            public function deposit(int $userId, \FavoriteCMS\Pay\Domain\Money $amount, string $referenceId, string $description = ''): \FavoriteCMS\Pay\Domain\WalletLedgerEntry {
                throw new \RuntimeException("Favorite Pay connection timeout during deposit");
            }
            public function debit(int $userId, \FavoriteCMS\Pay\Domain\Money $amount, string $ref, string $desc = ''): \FavoriteCMS\Pay\Domain\WalletLedgerEntry { throw new \RuntimeException("n/a"); }
            public function hold(int $userId, \FavoriteCMS\Pay\Domain\Money $amount, string $ref): \FavoriteCMS\Pay\Domain\WalletLedgerEntry { throw new \RuntimeException("n/a"); }
            public function releaseHold(int $userId, \FavoriteCMS\Pay\Domain\Money $amount, string $ref): \FavoriteCMS\Pay\Domain\WalletLedgerEntry { throw new \RuntimeException("n/a"); }
            public function finalizeHold(int $userId, \FavoriteCMS\Pay\Domain\Money $amount, string $ref, string $desc = ''): \FavoriteCMS\Pay\Domain\WalletLedgerEntry { throw new \RuntimeException("n/a"); }
            public function settleSuccessfulPayment(string $txId): \FavoriteCMS\Pay\Domain\WalletLedgerEntry { throw new \RuntimeException("n/a"); }
            public function getLedgerHistory(int $userId, int $limit = 50, int $offset = 0): array { return []; }
            public function getHeldBalance(int $userId): \FavoriteCMS\Pay\Domain\Money { return \FavoriteCMS\Pay\Domain\Money::bdt(0); }
            public function getTotalBalance(int $userId): \FavoriteCMS\Pay\Domain\Money { return \FavoriteCMS\Pay\Domain\Money::bdt(0); }
            public function getFilteredLedgerHistory(int $userId, array $filters = [], int $limit = 20, int $offset = 0): array { return []; }
            public function getFilteredLedgerCount(int $userId, array $filters = []): int { return 0; }
            public function getLedgerEntry(string $entryId, ?int $userId = null): ?\FavoriteCMS\Pay\Domain\WalletLedgerEntry { return null; }
            public function getGlobalWalletOverview(): array { return []; }
            public function searchCustomerWallets(string $query, int $limit = 20): array { return []; }
            public function getGlobalRecentActivity(int $limit = 15): array { return []; }
        };

        $walletServiceWithFailingFp = new WalletService($this->walletRepo, $this->db, $failingFavPay);
        $refundServiceWithFailingFp = new RefundService(
            $this->orderRepo,
            $this->refundRepo,
            $walletServiceWithFailingFp,
            $this->entitlementRepo,
            $this->membershipService,
            $this->db
        );

        $this->expectException(\FavoriteCMS\Digital\Exceptions\WalletException::class);
        $this->expectExceptionMessageMatches('/Favorite Pay connection timeout/');

        $refundServiceWithFailingFp->processPartialRefund($orderId, '100.00', 'Failing refund test', 'wallet');

        // Zero completed refunds in DB
        $refunds = $this->refundRepo->findRefundsByOrderId($orderId);
        $this->assertEmpty($refunds);

        // Order status unchanged
        $order = $this->orderRepo->findOrder($orderId);
        $this->assertSame(OrderLifecycleState::STATUS_PROCESSING, $order->status);
    }

    /**
     * TEST 39: Repeated identical partial refund request does not double-credit wallet.
     */
    public function testRepeatedIdenticalPartialRefundDoesNotDoubleCredit(): void
    {
        $userId = 208;
        $orderId = $this->createOrder(190.00, $userId, ProductType::SERVICE, 190.00);

        // Initial partial refund
        $this->refundService->processPartialRefund($orderId, '100.00', 'Partial refund test', 'wallet', 'ref_fixed_123');

        $fpBalanceAfterFirst = $this->favPayWalletService->getBalance($userId)->getAmount();
        $this->assertSame(10000, $fpBalanceAfterFirst);

        // Repeated identical partial refund
        $this->refundService->processPartialRefund($orderId, '100.00', 'Partial refund test', 'wallet', 'ref_fixed_123');

        // Wallet balance must NOT double-credit
        $fpBalanceAfterSecond = $this->favPayWalletService->getBalance($userId)->getAmount();
        $this->assertSame(10000, $fpBalanceAfterSecond);

        // Exactly 1 entry in favorite_pay_wallet_entries
        $fpEntries = $this->db->select("SELECT * FROM favorite_pay_wallet_entries WHERE user_id = ?", [$userId]);
        $this->assertCount(1, $fpEntries);
    }

    /**
     * TEST 40: Invalid user ID is safely rejected.
     */
    public function testInvalidUserIdIsSafelyRejected(): void
    {
        $orderId = $this->createOrder(190.00, 0, ProductType::SERVICE, 190.00);

        $this->expectException(\FavoriteCMS\Digital\Exceptions\RefundException::class);
        $this->expectExceptionMessageMatches('/invalid customer user ID/i');

        $this->refundService->processPartialRefund($orderId, '50.00', 'Refund for guest/invalid user', 'wallet');
    }
}
