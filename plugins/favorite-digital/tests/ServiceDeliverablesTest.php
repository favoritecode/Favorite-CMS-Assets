<?php

declare(strict_types=1);

namespace FavoriteCMS\Digital\Tests;

if (!defined('PHPUNIT_RUNNING')) {
    define('PHPUNIT_RUNNING', true);
}

use CreateFavoriteDigitalProductsTable;
use CreateFavoriteDigitalProductDetailsTable;
use CreateFavoriteDigitalServiceDetailsTable;
use CreateFavoriteDigitalMembershipPlansTable;
use CreateFavoriteDigitalMembershipsTable;
use CreateFavoriteDigitalEntitlementsTable;
use CreateFavoriteDigitalDownloadsTable;
use CreateFavoriteDigitalOrderItemsTable;
use CreateFavoriteDigitalOrderPaymentsTable;
use CreateFavoriteDigitalOrdersTable;
use CreateFavoriteDigitalRefundsTable;
use CreateFavoriteDigitalWalletsTable;
use CreateFavoriteDigitalWalletTransactionsTable;
use AddV101MediaAndResourceFields;
use AddPartialSettlementAndManualRefundFields;
use CreateFavoriteDigitalOrderDeliverablesTable;
use FavoriteCMS\Core\Application;
use FavoriteCMS\Core\Database;
use FavoriteCMS\Core\Request;
use FavoriteCMS\Digital\Controllers\AdminOrderController;
use FavoriteCMS\Digital\Controllers\CustomerDownloadController;
use FavoriteCMS\Digital\Controllers\CustomerOrderController;
use FavoriteCMS\Digital\Domain\ProductType;
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
use InvalidArgumentException;
use PDO;
use PHPUnit\Framework\TestCase;

class ServiceDeliverablesTest extends TestCase
{
    private Database $db;
    private PDO $pdo;
    private Application $app;
    private OrderRepository $orderRepo;
    private RefundRepository $refundRepo;
    private WalletRepository $walletRepo;
    private WalletService $walletService;
    private EntitlementRepository $entitlementRepo;
    private ProductRepository $productRepo;
    private DownloadRepository $downloadRepo;
    private MembershipLifecycleService $membershipService;
    private RefundService $refundService;
    private OrderService $orderService;
    private FulfillmentService $fulfillmentService;
    private CheckoutService $checkoutService;
    private DigitalFileStorageService $storageService;
    private DownloadService $downloadService;
    private AdminOrderController $adminController;
    private CustomerDownloadController $customerDownloadController;
    private CustomerOrderController $customerOrderController;
    private string $tempStorageDir;

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

        (new CreateFavoriteDigitalProductsTable($this->db))->up();
        (new CreateFavoriteDigitalProductDetailsTable($this->db))->up();
        (new CreateFavoriteDigitalServiceDetailsTable($this->db))->up();
        (new CreateFavoriteDigitalMembershipPlansTable($this->db))->up();
        (new CreateFavoriteDigitalMembershipsTable($this->db))->up();
        (new CreateFavoriteDigitalOrdersTable($this->db))->up();
        (new CreateFavoriteDigitalOrderItemsTable($this->db))->up();
        (new CreateFavoriteDigitalOrderPaymentsTable($this->db))->up();
        (new CreateFavoriteDigitalEntitlementsTable($this->db))->up();
        (new CreateFavoriteDigitalDownloadsTable($this->db))->up();
        (new CreateFavoriteDigitalWalletsTable($this->db))->up();
        (new CreateFavoriteDigitalWalletTransactionsTable($this->db))->up();
        (new CreateFavoriteDigitalRefundsTable($this->db))->up();
        (new AddV101MediaAndResourceFields($this->db))->up();
        (new AddPartialSettlementAndManualRefundFields($this->db))->up();
        (new CreateFavoriteDigitalOrderDeliverablesTable($this->db))->up();

        // 3. Isolated filesystem storage
        $this->tempStorageDir = sys_get_temp_dir() . '/fd_deliv_test_' . uniqid('', true);
        mkdir($this->tempStorageDir . '/files', 0777, true);
        mkdir($this->tempStorageDir . '/images', 0777, true);
        mkdir($this->tempStorageDir . '/proofs', 0777, true);

        $this->storageService = new DigitalFileStorageService(
            $this->tempStorageDir . '/files',
            104857600,
            $this->tempStorageDir . '/images',
            $this->tempStorageDir . '/proofs'
        );

        // 4. Initialize Repositories & Services
        $this->app = Application::getInstance();
        $this->app->instance(Database::class, $this->db);

        $this->orderRepo = new OrderRepository($this->db);
        $this->refundRepo = new RefundRepository($this->db);
        $this->walletRepo = new WalletRepository($this->db);
        $this->walletService = new WalletService($this->walletRepo);
        $this->entitlementRepo = new EntitlementRepository($this->db);
        $this->productRepo = new ProductRepository($this->db);
        $this->downloadRepo = new DownloadRepository($this->db);
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

        $this->checkoutService = new CheckoutService(
            $this->orderRepo,
            $this->walletService,
            null,
            $this->db,
            $this->fulfillmentService
        );

        $this->orderService = new OrderService(
            $this->orderRepo,
            $this->productRepo,
            $this->membershipService,
            null,
            $this->db
        );

        $entitlementChecker = new DefaultEntitlementChecker(
            $this->db,
            $this->entitlementRepo,
            $this->membershipService,
            $this->productRepo
        );

        $this->downloadService = new DownloadService(
            $this->downloadRepo,
            $this->entitlementRepo,
            $this->productRepo,
            $this->membershipService,
            $entitlementChecker,
            $this->storageService,
            $this->db,
            $this->orderRepo
        );

        $this->adminController = new AdminOrderController(
            $this->app,
            $this->orderService,
            $this->fulfillmentService,
            $this->entitlementRepo,
            $this->refundService,
            $this->storageService
        );

        $this->customerDownloadController = new CustomerDownloadController(
            $this->app,
            $this->downloadService,
            $this->entitlementRepo,
            $this->productRepo,
            $this->membershipService
        );

        $this->customerOrderController = new CustomerOrderController(
            $this->app,
            $this->orderService,
            $this->refundRepo
        );

        // Admin Session setup
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
        unset($GLOBALS['_test_current_user'], $GLOBALS['_test_current_user_id']);
        $_SESSION = [];
        $_FILES = [];

        // Clean up temporary directory
        $this->deleteDirectory($this->tempStorageDir);
    }

    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = array_diff(scandir($dir) ?: [], ['.', '..']);
        foreach ($files as $file) {
            $path = "$dir/$file";
            is_dir($path) ? $this->deleteDirectory($path) : @unlink($path);
        }
        @rmdir($dir);
    }

    /**
     * Helper to create a test order.
     */
    private function createTestOrder(
        float $totalAmount,
        float $paidAmount = 0.0,
        int $userId = 1,
        string $productType = ProductType::SERVICE,
        string $status = OrderLifecycleState::STATUS_PENDING
    ): int {
        $orderId = $this->orderRepo->createOrder([
            'order_number'       => 'ORD-' . bin2hex(random_bytes(4)),
            'user_id'            => $userId,
            'status'             => $status,
            'payment_status'     => $paidAmount >= $totalAmount ? OrderLifecycleState::PAYMENT_PAID : OrderLifecycleState::PAYMENT_PENDING,
            'fulfillment_status' => OrderLifecycleState::FULFILLMENT_UNFULFILLED,
            'subtotal_amount'    => number_format($totalAmount, 2, '.', ''),
            'discount_amount'    => '0.00',
            'total_amount'       => number_format($totalAmount, 2, '.', ''),
            'retained_amount'    => number_format($paidAmount, 2, '.', ''),
            'refunded_amount'    => '0.00',
            'currency'           => 'BDT',
            'notes'              => 'Test order',
            'created_at'         => date('Y-m-d H:i:s'),
            'updated_at'         => date('Y-m-d H:i:s'),
        ]);

        $this->orderRepo->createOrderItem([
            'order_id'         => $orderId,
            'product_id'       => 101,
            'product_type'     => $productType,
            'unit_price'       => number_format($totalAmount, 2, '.', ''),
            'discount_percent' => '0.00',
            'final_price'      => number_format($totalAmount, 2, '.', ''),
            'currency'         => 'BDT',
            'snapshot_data'    => json_encode(['title' => 'Sample Deliverable Service']),
            'created_at'       => date('Y-m-d H:i:s'),
        ]);

        if ($paidAmount > 0) {
            $this->orderRepo->createOrderPayment([
                'order_id'           => $orderId,
                'payment_method'     => 'bkash',
                'favorite_pay_tx_id' => 'TX_' . bin2hex(random_bytes(4)),
                'amount_paid'        => number_format($paidAmount, 2, '.', ''),
                'currency'           => 'BDT',
                'status'             => 'completed',
                'created_at'         => date('Y-m-d H:i:s'),
                'updated_at'         => date('Y-m-d H:i:s'),
            ]);
        }

        return $orderId;
    }

    /**
     * Helper to create a dummy physical file for upload testing.
     */
    private function createSampleFile(string $filename, string $content): string
    {
        $path = $this->tempStorageDir . '/' . $filename;
        file_put_contents($path, $content);
        return $path;
    }

    /**
     * SCENARIO 1: Digital Product direct upload works (no regression).
     * Existing digital product flow with direct uploaded file remains unchanged.
     */
    public function test01_DigitalProductDirectUploadNoRegression(): void
    {
        $userId = 10;
        $content = "PDF-1.4 sample digital product binary content for testing.";
        $tmpFile = $this->createSampleFile('ebook.pdf', $content);

        // Store file using DigitalFileStorageService
        $stored = $this->storageService->storeUpload([
            'name'     => 'ebook.pdf',
            'tmp_name' => $tmpFile,
            'size'     => strlen($content),
            'error'    => UPLOAD_ERR_OK,
        ]);

        $this->assertNotEmpty($stored['file_path']);
        $this->assertNotEmpty($stored['file_hash']);
        $this->assertSame(strlen($content), $stored['file_size']);

        // Create product & product details
        $productId = (int)$this->db->insert('favorite_digital_products', [
            'title'            => 'Mastering PHP E-Book',
            'slug'             => 'mastering-php-ebook-' . bin2hex(random_bytes(3)),
            'product_type'     => ProductType::DIGITAL,
            'status'           => 'published',
            'original_price'   => '49.00',
            'discount_percent' => '0.00',
            'final_price'      => '49.00',
            'created_at'       => date('Y-m-d H:i:s'),
            'updated_at'       => date('Y-m-d H:i:s'),
        ]);

        $this->db->insert('favorite_digital_product_details', [
            'product_id'    => $productId,
            'resource_type' => 'file',
            'file_path'     => $stored['file_path'],
            'file_name'     => 'Mastering_PHP.pdf',
            'file_hash'     => $stored['file_hash'],
            'file_size'     => $stored['file_size'],
            'mime_type'     => 'application/pdf',
        ]);

        // Entitlement and download token
        $entitlementId = $this->entitlementRepo->createEntitlement([
            'user_id'    => $userId,
            'product_id' => $productId,
            'status'     => 'active',
        ]);

        $token = bin2hex(random_bytes(32));
        $this->db->insert('favorite_digital_downloads', [
            'entitlement_id' => $entitlementId,
            'product_id'     => $productId,
            'user_id'        => $userId,
            'download_token' => $token,
            'download_count' => 0,
            'created_at'     => date('Y-m-d H:i:s'),
        ]);

        // Authorize download
        $auth = $this->downloadService->authorizeDownload($token, $userId);

        $this->assertFalse($auth['is_external']);
        $this->assertEmpty($auth['is_deliverable'] ?? false);
        $this->assertFileExists($auth['file_path']);
        $this->assertSame('Mastering_PHP.pdf', $auth['file_name']);
        $this->assertSame(strlen($content), $auth['file_size']);
    }

    /**
     * SCENARIO 2: Digital Product external URL works (no regression).
     * Existing digital product external resource with manual size remains unchanged.
     */
    public function test02_DigitalProductExternalUrlNoRegression(): void
    {
        $userId = 11;
        $productId = (int)$this->db->insert('favorite_digital_products', [
            'title'            => 'Cloud Software Archive',
            'slug'             => 'cloud-software-archive-' . bin2hex(random_bytes(3)),
            'product_type'     => ProductType::DIGITAL,
            'status'           => 'published',
            'original_price'   => '99.00',
            'discount_percent' => '0.00',
            'final_price'      => '99.00',
            'created_at'       => date('Y-m-d H:i:s'),
            'updated_at'       => date('Y-m-d H:i:s'),
        ]);

        $this->db->insert('favorite_digital_product_details', [
            'product_id'    => $productId,
            'resource_type' => 'url',
            'resource_url'  => 'https://cdn.example.com/software-release.zip',
            'file_size'     => 52428800, // 50 MB
            'file_name'     => 'Software Release v2.0',
        ]);

        $entId = $this->entitlementRepo->createEntitlement([
            'user_id'    => $userId,
            'product_id' => $productId,
            'status'     => 'active',
        ]);

        $token = bin2hex(random_bytes(32));
        $this->db->insert('favorite_digital_downloads', [
            'entitlement_id' => $entId,
            'product_id'     => $productId,
            'user_id'        => $userId,
            'download_token' => $token,
            'download_count' => 0,
            'created_at'     => date('Y-m-d H:i:s'),
        ]);

        // Authorize download
        $auth = $this->downloadService->authorizeDownload($token, $userId);

        $this->assertTrue($auth['is_external']);
        $this->assertSame('https://cdn.example.com/software-release.zip', $auth['resource_url']);
        $this->assertSame(52428800, $auth['file_size']);

        // Download controller handles external redirect
        $GLOBALS['_test_current_user']->id = $userId;
        $request = new Request([], [], ['REQUEST_METHOD' => 'GET']);
        $resp = $this->customerDownloadController->download($request, $token);

        $this->assertSame(302, $resp->getStatusCode());
        $this->assertSame('https://cdn.example.com/software-release.zip', $resp->getHeader('Location'));
    }

    /**
     * SCENARIO 3: Digital Service direct upload deliverable creation.
     */
    public function test03_DigitalServiceDirectUploadDeliverableCreation(): void
    {
        $userId = 12;
        $orderId = $this->createTestOrder(500.00, 500.00, $userId, ProductType::SERVICE, OrderLifecycleState::STATUS_PROCESSING);

        $pdfContent = "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF";
        $tmpFile = $this->createSampleFile('audit_report.pdf', $pdfContent);

        $_FILES['deliverable_file'] = [
            'name'     => 'audit_report.pdf',
            'tmp_name' => $tmpFile,
            'size'     => strlen($pdfContent),
            'error'    => UPLOAD_ERR_OK,
        ];

        $request = new Request([], [
            'action'            => 'add_deliverable',
            'id'                => (string)$orderId,
            'deliverable_title' => 'Final Security Audit Report',
            'description'       => 'Comprehensive penetration testing report.',
            'resource_type'     => 'file',
            'is_released'       => '1',
            '_token'            => 'test_csrf_token_123',
        ], ['REQUEST_METHOD' => 'POST']);

        $resp = $this->adminController->handle($request);
        $this->assertSame(302, $resp->getStatusCode());
        $this->assertNotEmpty($_SESSION['flash_success']);

        // Verify deliverable created in database
        $deliverables = $this->orderRepo->getDeliverablesByOrderId($orderId);
        $this->assertCount(1, $deliverables);

        $deliv = $deliverables[0];
        $this->assertSame('Final Security Audit Report', $deliv->title);
        $this->assertSame('Comprehensive penetration testing report.', $deliv->description);
        $this->assertSame('file', $deliv->resource_type);
        $this->assertSame('audit_report.pdf', $deliv->file_name);
        $this->assertSame(strlen($pdfContent), (int)$deliv->file_size);
        $this->assertSame(64, strlen($deliv->download_token));
        $this->assertSame(1, (int)$deliv->is_released);
        $this->assertNotEmpty($deliv->file_hash);
    }

    /**
     * SCENARIO 4: Uploaded file size automatic detection and formatting.
     */
    public function test04_UploadedFileSizeAutomaticDetectionAndFormatting(): void
    {
        $bytes = 2500000;
        $this->assertSame('2.38 MB', fdig_format_bytes($bytes));
        $this->assertSame('0 B', fdig_format_bytes(0));
        $this->assertSame('512 B', fdig_format_bytes(512));
        $this->assertSame('2 KB', fdig_format_bytes(2048));
        $this->assertSame('1 MB', fdig_format_bytes(1048576));
        $this->assertSame('1 GB', fdig_format_bytes(1073741824));
    }

    /**
     * SCENARIO 5: Digital Service external URL deliverable creation with URL validation.
     */
    public function test05_DigitalServiceExternalUrlDeliverableCreationWithValidation(): void
    {
        $userId = 13;
        $orderId = $this->createTestOrder(500.00, 500.00, $userId, ProductType::SERVICE, OrderLifecycleState::STATUS_PROCESSING);

        // 1. Valid External URL
        $validUrl = 'https://drive.google.com/file/d/1234567890/view';
        $request = new Request([], [
            'action'                => 'add_deliverable',
            'id'                    => (string)$orderId,
            'deliverable_title'     => 'Google Drive Deliverable Assets',
            'description'           => 'Link to cloud folder',
            'resource_type'         => 'url',
            'resource_url'          => $validUrl,
            'manual_file_size_val'  => '50',
            'manual_file_size_unit' => 'MB',
            'is_released'           => '1',
            '_token'                => 'test_csrf_token_123',
        ], ['REQUEST_METHOD' => 'POST']);

        $resp = $this->adminController->handle($request);
        $this->assertSame(302, $resp->getStatusCode());
        $this->assertNotEmpty($_SESSION['flash_success']);

        $deliverables = $this->orderRepo->getDeliverablesByOrderId($orderId);
        $this->assertCount(1, $deliverables);
        $this->assertSame('url', $deliverables[0]->resource_type);
        $this->assertSame($validUrl, $deliverables[0]->resource_url);

        // 2. Dangerous / Invalid URLs must be rejected by storageService and controller
        $dangerousUrls = [
            'javascript:alert(1)',
            'file:///etc/passwd',
            'data:text/plain;base64,SGVsbG8=',
            'ftp://ftp.example.com/file.zip',
        ];

        foreach ($dangerousUrls as $dUrl) {
            $invalidReq = new Request([], [
                'action'            => 'add_deliverable',
                'id'                => (string)$orderId,
                'deliverable_title' => 'Malicious Link',
                'resource_type'     => 'url',
                'resource_url'      => $dUrl,
                'is_released'       => '1',
                '_token'            => 'test_csrf_token_123',
            ], ['REQUEST_METHOD' => 'POST']);

            $this->adminController->handle($invalidReq);
            $this->assertNotEmpty($_SESSION['flash_error']);
            $this->assertTrue(
                str_contains($_SESSION['flash_error'], 'Invalid URL') ||
                str_contains($_SESSION['flash_error'], 'not allowed') ||
                str_contains($_SESSION['flash_error'], 'Failed to add deliverable')
            );
        }

        // Deliverable count should still be 1 (no malicious links added)
        $this->assertCount(1, $this->orderRepo->getDeliverablesByOrderId($orderId));
    }

    /**
     * SCENARIO 6: External URL deliverable manual size + KB/MB/GB unit calculation.
     */
    public function test06_ExternalUrlDeliverableManualSizeUnitCalculation(): void
    {
        $userId = 14;
        $orderId = $this->createTestOrder(500.00, 500.00, $userId, ProductType::SERVICE, OrderLifecycleState::STATUS_PROCESSING);

        $testCases = [
            ['val' => '1.5', 'unit' => 'GB', 'expected' => 1610612736],
            ['val' => '250', 'unit' => 'MB', 'expected' => 262144000],
            ['val' => '500', 'unit' => 'KB', 'expected' => 512000],
            ['val' => '1024', 'unit' => 'B', 'expected' => 1024],
        ];

        foreach ($testCases as $index => $tc) {
            $req = new Request([], [
                'action'                => 'add_deliverable',
                'id'                    => (string)$orderId,
                'deliverable_title'     => "Deliverable {$tc['unit']} Test",
                'resource_type'         => 'url',
                'resource_url'          => "https://example.com/asset-{$index}.zip",
                'manual_file_size_val'  => $tc['val'],
                'manual_file_size_unit' => $tc['unit'],
                'is_released'           => '1',
                '_token'                => 'test_csrf_token_123',
            ], ['REQUEST_METHOD' => 'POST']);

            $this->adminController->handle($req);
        }

        $deliverables = $this->orderRepo->getDeliverablesByOrderId($orderId);
        $this->assertCount(4, $deliverables);

        foreach ($testCases as $index => $tc) {
            $this->assertSame($tc['expected'], (int)$deliverables[$index]->file_size);
        }
    }

    /**
     * SCENARIO 7: Multiple deliverables on a single service order remain distinct and accessible.
     */
    public function test07_MultipleDeliverablesOnSingleServiceOrderRemainDistinct(): void
    {
        $userId = 15;
        $orderId = $this->createTestOrder(600.00, 600.00, $userId, ProductType::SERVICE, OrderLifecycleState::STATUS_PROCESSING);

        // Deliverable 1: PDF Specification
        $this->orderRepo->createDeliverable([
            'order_id'       => $orderId,
            'title'          => 'Technical Specification Document',
            'resource_type'  => 'url',
            'resource_url'   => 'https://example.com/docs/spec.pdf',
            'file_size'      => 1048576,
            'download_token' => bin2hex(random_bytes(32)),
            'is_released'    => 1,
        ]);

        // Deliverable 2: Figma Link
        $this->orderRepo->createDeliverable([
            'order_id'       => $orderId,
            'title'          => 'Figma Interactive Mockup',
            'resource_type'  => 'url',
            'resource_url'   => 'https://www.figma.com/design/mockup123',
            'file_size'      => 0,
            'download_token' => bin2hex(random_bytes(32)),
            'is_released'    => 1,
        ]);

        // Deliverable 3: Source Bundle
        $this->orderRepo->createDeliverable([
            'order_id'       => $orderId,
            'title'          => 'Release Candidate Source Bundle',
            'resource_type'  => 'url',
            'resource_url'   => 'https://example.com/releases/v1.0.tar.gz',
            'file_size'      => 20971520,
            'download_token' => bin2hex(random_bytes(32)),
            'is_released'    => 1,
        ]);

        $deliverables = $this->orderRepo->getDeliverablesByOrderId($orderId);
        $this->assertCount(3, $deliverables);

        $tokens = array_map(fn($d) => $d->download_token, $deliverables);
        $this->assertSame(3, count(array_unique($tokens)), 'All 3 tokens must be unique.');

        foreach ($deliverables as $deliv) {
            $auth = $this->downloadService->authorizeDownload($deliv->download_token, $userId);
            $this->assertTrue($auth['is_deliverable']);
            $this->assertSame($deliv->title, $auth['deliverable']->title);
        }
    }

    /**
     * SCENARIO 8: Partial service status with released deliverable is accessible to customer.
     */
    public function test08_PartialServiceStatusWithReleasedDeliverableIsAccessible(): void
    {
        $userId = 16;
        $orderId = $this->createTestOrder(600.00, 600.00, $userId, ProductType::SERVICE, OrderLifecycleState::STATUS_PROCESSING);

        // Partial settlement: 200 refunded to wallet, 400 retained, status becomes partial
        $this->refundService->processPartialRefund($orderId, 200.00, 'Milestone 1 completed; Milestone 2 cancelled', 'wallet', 'PART-REF-88');

        $order = $this->orderRepo->findOrder($orderId);
        $this->assertSame(OrderLifecycleState::STATUS_PARTIAL, $order->status);

        // Add released deliverable for Milestone 1
        $token = bin2hex(random_bytes(32));
        $this->orderRepo->createDeliverable([
            'order_id'       => $orderId,
            'title'          => 'Milestone 1 Deliverable Pack',
            'resource_type'  => 'url',
            'resource_url'   => 'https://example.com/milestone-1-files.zip',
            'file_size'      => 5242880,
            'download_token' => $token,
            'is_released'    => 1,
        ]);

        // Customer can authorize download
        $auth = $this->downloadService->authorizeDownload($token, $userId);
        $this->assertTrue($auth['is_deliverable']);
        $this->assertSame('https://example.com/milestone-1-files.zip', $auth['resource_url']);

        // Customer order view lists the released deliverable
        $released = $this->orderRepo->getDeliverablesByOrderId($orderId, true);
        $this->assertCount(1, $released);
        $this->assertSame('Milestone 1 Deliverable Pack', $released[0]->title);
    }

    /**
     * SCENARIO 9: Completed service status with multiple released deliverables.
     */
    public function test09_CompletedServiceStatusWithMultipleReleasedDeliverables(): void
    {
        $userId = 17;
        $orderId = $this->createTestOrder(800.00, 800.00, $userId, ProductType::SERVICE, OrderLifecycleState::STATUS_PROCESSING);

        // Admin marks order completed
        $updateReq = new Request([], [
            'action'        => 'update_service_status',
            'id'            => (string)$orderId,
            'chosen_status' => 'completed',
            '_token'        => 'test_csrf_token_123',
        ], ['REQUEST_METHOD' => 'POST']);
        $this->adminController->handle($updateReq);

        $order = $this->orderRepo->findOrder($orderId);
        $this->assertSame(OrderLifecycleState::STATUS_COMPLETED, $order->status);
        $this->assertSame(OrderLifecycleState::FULFILLMENT_FULFILLED, $order->fulfillment_status);

        // Add two released deliverables
        $t1 = bin2hex(random_bytes(32));
        $t2 = bin2hex(random_bytes(32));

        $this->orderRepo->createDeliverable([
            'order_id'       => $orderId,
            'title'          => 'Final Service Report',
            'resource_type'  => 'url',
            'resource_url'   => 'https://example.com/report.pdf',
            'file_size'      => 1024,
            'download_token' => $t1,
            'is_released'    => 1,
        ]);

        $this->orderRepo->createDeliverable([
            'order_id'       => $orderId,
            'title'          => 'Production Certificate',
            'resource_type'  => 'url',
            'resource_url'   => 'https://example.com/cert.pdf',
            'file_size'      => 2048,
            'download_token' => $t2,
            'is_released'    => 1,
        ]);

        $released = $this->orderRepo->getDeliverablesByOrderId($orderId, true);
        $this->assertCount(2, $released);

        $auth1 = $this->downloadService->authorizeDownload($t1, $userId);
        $auth2 = $this->downloadService->authorizeDownload($t2, $userId);

        $this->assertSame('Final Service Report', $auth1['deliverable']->title);
        $this->assertSame('Production Certificate', $auth2['deliverable']->title);
    }

    /**
     * SCENARIO 10: Customer access control: Customer can download their own released deliverables.
     */
    public function test10_CustomerCanDownloadOwnReleasedDeliverables(): void
    {
        $userId = 18;
        $orderId = $this->createTestOrder(500.00, 500.00, $userId, ProductType::SERVICE, OrderLifecycleState::STATUS_PROCESSING);

        $pdfContent = "%PDF-1.4 deliverables binary content";
        $tmpFile = $this->createSampleFile('final_invoice.pdf', $pdfContent);

        $stored = $this->storageService->storeUpload([
            'name'     => 'final_invoice.pdf',
            'tmp_name' => $tmpFile,
            'size'     => strlen($pdfContent),
            'error'    => UPLOAD_ERR_OK,
        ]);

        $token = bin2hex(random_bytes(32));
        $this->orderRepo->createDeliverable([
            'order_id'       => $orderId,
            'title'          => 'Service Invoice',
            'resource_type'  => 'file',
            'file_path'      => $stored['file_path'],
            'file_name'      => 'Service_Invoice.pdf',
            'file_hash'      => $stored['file_hash'],
            'file_size'      => $stored['file_size'],
            'mime_type'      => 'application/pdf',
            'download_token' => $token,
            'is_released'    => 1,
        ]);

        // Customer authorizes download
        $auth = $this->downloadService->authorizeDownload($token, $userId);
        $this->assertTrue($auth['is_deliverable']);
        $this->assertSame($orderId, (int)$auth['order']->id);
        $this->assertFileExists($auth['file_path']);

        // Test CustomerDownloadController download response
        $GLOBALS['_test_current_user']->id = $userId;
        $_SESSION['auth_user_id'] = $userId;
        $req = new Request([], [], ['REQUEST_METHOD' => 'GET']);
        $resp = $this->customerDownloadController->download($req, $token);

        $this->assertSame(200, $resp->getStatusCode());
    }

    /**
     * SCENARIO 11: Unreleased deliverables are blocked (403/unavailable).
     */
    public function test11_UnreleasedDeliverablesAreBlocked(): void
    {
        $userId = 19;
        $orderId = $this->createTestOrder(500.00, 500.00, $userId, ProductType::SERVICE, OrderLifecycleState::STATUS_PROCESSING);

        $token = bin2hex(random_bytes(32));
        $this->orderRepo->createDeliverable([
            'order_id'       => $orderId,
            'title'          => 'Internal Draft Audit Report',
            'resource_type'  => 'url',
            'resource_url'   => 'https://example.com/draft-report.pdf',
            'file_size'      => 1024,
            'download_token' => $token,
            'is_released'    => 0, // Unreleased / Draft
        ]);

        // Customer view filters out unreleased deliverables
        $released = $this->orderRepo->getDeliverablesByOrderId($orderId, true);
        $this->assertEmpty($released, 'Unreleased deliverables must not appear in customer released list.');

        // DownloadService throws exception
        $this->expectException(DownloadException::class);
        $this->expectExceptionMessage('This deliverable has not been released yet.');
        $this->downloadService->authorizeDownload($token, $userId);
    }

    /**
     * SCENARIO 12: Another customer cannot access deliverables (cross-customer access denied).
     */
    public function test12_CrossCustomerAccessDenied(): void
    {
        $legitimateOwnerId = 20;
        $attackerUserId = 99;

        $orderId = $this->createTestOrder(500.00, 500.00, $legitimateOwnerId, ProductType::SERVICE, OrderLifecycleState::STATUS_PROCESSING);

        $token = bin2hex(random_bytes(32));
        $this->orderRepo->createDeliverable([
            'order_id'       => $orderId,
            'title'          => 'Confidential Corporate Audit',
            'resource_type'  => 'url',
            'resource_url'   => 'https://example.com/confidential-audit.pdf',
            'file_size'      => 1024,
            'download_token' => $token,
            'is_released'    => 1,
        ]);

        // Attacker attempts to download legitimate customer's deliverable
        $this->expectException(DownloadException::class);
        $this->expectExceptionMessage('You are not authorized to access this deliverable.');
        $this->downloadService->authorizeDownload($token, $attackerUserId);
    }

    /**
     * SCENARIO 13: Existing digital product download via /download/{token} remains unchanged.
     */
    public function test13_ExistingDigitalProductDownloadByTokenRemainsUnchanged(): void
    {
        $userId = 21;
        $productId = (int)$this->db->insert('favorite_digital_products', [
            'title'            => 'Vector Icon Collection',
            'slug'             => 'vector-icon-collection-' . bin2hex(random_bytes(3)),
            'product_type'     => ProductType::DIGITAL,
            'status'           => 'published',
            'original_price'   => '29.00',
            'discount_percent' => '0.00',
            'final_price'      => '29.00',
            'created_at'       => date('Y-m-d H:i:s'),
            'updated_at'       => date('Y-m-d H:i:s'),
        ]);

        $this->db->insert('favorite_digital_product_details', [
            'product_id'    => $productId,
            'resource_type' => 'url',
            'resource_url'  => 'https://cdn.example.com/icons.zip',
            'file_size'     => 1048576,
            'file_name'     => 'Icons.zip',
        ]);

        $entId = $this->entitlementRepo->createEntitlement([
            'user_id'    => $userId,
            'product_id' => $productId,
            'status'     => 'active',
        ]);

        $token = bin2hex(random_bytes(32));
        $this->db->insert('favorite_digital_downloads', [
            'entitlement_id' => $entId,
            'product_id'     => $productId,
            'user_id'        => $userId,
            'download_token' => $token,
            'download_count' => 0,
            'created_at'     => date('Y-m-d H:i:s'),
        ]);

        $auth = $this->downloadService->authorizeDownload($token, $userId);
        $this->assertEmpty($auth['is_deliverable'] ?? false);
        $this->assertSame('Vector Icon Collection', $auth['product']->title);

        // Non-existent token fails cleanly
        $fakeToken = bin2hex(random_bytes(32));
        $this->expectException(DownloadException::class);
        $this->downloadService->authorizeDownload($fakeToken, $userId);
    }

    /**
     * SCENARIO 14: Admin can toggle release and delete deliverable.
     */
    public function test14_AdminToggleDeliverableReleaseAndDelete(): void
    {
        $userId = 22;
        $orderId = $this->createTestOrder(500.00, 500.00, $userId, ProductType::SERVICE, OrderLifecycleState::STATUS_PROCESSING);

        $token = bin2hex(random_bytes(32));
        $delivId = $this->orderRepo->createDeliverable([
            'order_id'       => $orderId,
            'title'          => 'Work in progress preview',
            'resource_type'  => 'url',
            'resource_url'   => 'https://example.com/preview.png',
            'file_size'      => 1024,
            'download_token' => $token,
            'is_released'    => 1,
        ]);

        // Toggle release: 1 -> 0
        $toggleReq1 = new Request([], [
            'action'         => 'toggle_deliverable_release',
            'id'             => (string)$orderId,
            'deliverable_id' => (string)$delivId,
            '_token'         => 'test_csrf_token_123',
        ], ['REQUEST_METHOD' => 'POST']);

        $this->adminController->handle($toggleReq1);
        $d = $this->orderRepo->findDeliverable($delivId);
        $this->assertSame(0, (int)$d->is_released);

        // Toggle release: 0 -> 1
        $toggleReq2 = new Request([], [
            'action'         => 'toggle_deliverable_release',
            'id'             => (string)$orderId,
            'deliverable_id' => (string)$delivId,
            '_token'         => 'test_csrf_token_123',
        ], ['REQUEST_METHOD' => 'POST']);

        $this->adminController->handle($toggleReq2);
        $d = $this->orderRepo->findDeliverable($delivId);
        $this->assertSame(1, (int)$d->is_released);

        // Delete deliverable
        $deleteReq = new Request([], [
            'action'         => 'delete_deliverable',
            'id'             => (string)$orderId,
            'deliverable_id' => (string)$delivId,
            '_token'         => 'test_csrf_token_123',
        ], ['REQUEST_METHOD' => 'POST']);

        $this->adminController->handle($deleteReq);
        $this->assertNull($this->orderRepo->findDeliverable($delivId));
    }

    /**
     * SCENARIO 15: Dark mode CSS tokens and views verification.
     */
    public function test15_DarkModeAndSemanticCssTokensInViews(): void
    {
        $userId = 23;
        $orderId = $this->createTestOrder(500.00, 500.00, $userId, ProductType::SERVICE, OrderLifecycleState::STATUS_PROCESSING);

        // Admin Order View contains deliverable management section and dark mode tokens
        $adminReq = new Request(['action' => 'view', 'id' => (string)$orderId], [], ['REQUEST_METHOD' => 'GET']);
        $adminHtml = $this->adminController->viewOrder($adminReq, $orderId);

        $this->assertIsString($adminHtml);
        $this->assertStringContainsString('Service Deliverables', $adminHtml);
        $this->assertStringContainsString('Add Deliverable', $adminHtml);
        $this->assertStringContainsString('var(--admin-surface', $adminHtml);
        $this->assertStringContainsString('var(--admin-border', $adminHtml);
        $this->assertStringContainsString('var(--admin-text', $adminHtml);
        $this->assertStringContainsString('.fd-deliverables-panel', $adminHtml);

        // Customer Order View contains deliverables section and empty state
        $customerViewPath = dirname(__DIR__) . '/views/customer/orders/view.php';
        $this->assertFileExists($customerViewPath);
        $customerViewSource = file_get_contents($customerViewPath);

        $this->assertStringContainsString('Service Deliverables', $customerViewSource);
        $this->assertStringContainsString('No deliverables available yet.', $customerViewSource);
        $this->assertStringContainsString('fdig_format_bytes', $customerViewSource);
        $this->assertStringContainsString('var(--surface-muted', $customerViewSource);
        $this->assertStringContainsString('var(--border', $customerViewSource);
        $this->assertStringContainsString('var(--heading', $customerViewSource);
        $this->assertStringContainsString('var(--accent', $customerViewSource);
        $this->assertStringContainsString('customer-refund-card', $customerViewSource);
        $this->assertStringContainsString('customer-refund-title', $customerViewSource);
        $this->assertStringContainsString('badge-refund-completed', $customerViewSource);
        $this->assertStringContainsString('[data-theme="dark"]', $customerViewSource);

        // Helper functions work as expected
        $this->assertSame('1.5 MB', fdig_format_bytes((int)(1.5 * 1024 * 1024)));
        $this->assertSame('', fdig_format_datetime(null));
        $this->assertNotEmpty(fdig_format_datetime('2026-09-20 10:30:00'));
    }

    /**
     * SCENARIO 16: Migration 018 creates favorite_digital_order_deliverables and indexes explicitly, down() drops cleanly.
     */
    public function test16_Migration018CreatesTableAndIndexesExplicitly(): void
    {
        $mig = new CreateFavoriteDigitalOrderDeliverablesTable($this->db);

        // Table already created in setUp(), test down()
        $mig->down();
        $this->assertFalse($this->db->tableExists('favorite_digital_order_deliverables'));

        // Test up()
        $mig->up();
        $this->assertTrue($this->db->tableExists('favorite_digital_order_deliverables'));

        // Verify columns
        $cols = $this->db->select("PRAGMA table_info(favorite_digital_order_deliverables)");
        $colNames = array_map(fn($c) => $c->name, $cols);
        $expectedCols = [
            'id', 'order_id', 'order_item_id', 'title', 'description',
            'resource_type', 'file_path', 'file_name', 'file_hash', 'file_size',
            'mime_type', 'resource_url', 'download_token', 'is_released',
            'created_at', 'updated_at',
        ];
        foreach ($expectedCols as $ec) {
            $this->assertContains($ec, $colNames, "Expected column {$ec} in deliverables table.");
        }

        // Verify indexes in SQLite
        $indexes = $this->db->select("PRAGMA index_list(favorite_digital_order_deliverables)");
        $indexNames = array_map(fn($i) => $i->name, $indexes);
        $this->assertContains('idx_fd_deliv_order', $indexNames);
        $this->assertContains('idx_fd_deliv_token', $indexNames);
        $this->assertContains('idx_fd_deliv_released', $indexNames);

        // Check idx_fd_deliv_token is unique
        $tokenIdx = null;
        foreach ($indexes as $idx) {
            if ($idx->name === 'idx_fd_deliv_token') {
                $tokenIdx = $idx;
                break;
            }
        }
        $this->assertNotNull($tokenIdx);
        $this->assertSame(1, (int)$tokenIdx->unique);
    }

    /**
     * SCENARIO 17: TABLES constant registration and ensureMigrations idempotency.
     */
    public function test17_EnsureMigrationsIdempotencyAndTableRegistration(): void
    {
        $this->assertContains('favorite_digital_order_deliverables', \FavoriteCMS\Digital\FavoriteDigitalPlugin::TABLES);

        // Test ensureMigrations when table exists
        \FavoriteCMS\Digital\FavoriteDigitalPlugin::reset();
        $plugin = \FavoriteCMS\Digital\FavoriteDigitalPlugin::bootstrap($this->app);
        $plugin->ensureMigrations();
        $this->assertTrue($this->db->tableExists('favorite_digital_order_deliverables'));

        // Calling again does not error or alter table
        $plugin->ensureMigrations();
        $this->assertTrue($this->db->tableExists('favorite_digital_order_deliverables'));
    }

    /**
     * SCENARIO 18: Download token uniqueness constraint enforcement.
     */
    public function test18_DownloadTokenUniquenessConstraint(): void
    {
        $userId = 24;
        $orderId = $this->createTestOrder(500.00, 500.00, $userId, ProductType::SERVICE, OrderLifecycleState::STATUS_PROCESSING);
        $token = bin2hex(random_bytes(32));

        $this->orderRepo->createDeliverable([
            'order_id'       => $orderId,
            'title'          => 'Deliverable A',
            'resource_type'  => 'url',
            'resource_url'   => 'https://example.com/a.pdf',
            'download_token' => $token,
            'is_released'    => 1,
        ]);

        $this->expectException(\Throwable::class);
        $this->orderRepo->createDeliverable([
            'order_id'       => $orderId,
            'title'          => 'Deliverable B (Duplicate Token)',
            'resource_type'  => 'url',
            'resource_url'   => 'https://example.com/b.pdf',
            'download_token' => $token,
            'is_released'    => 1,
        ]);
    }

    /**
     * SCENARIO 19: Revoked or fully refunded order blocks access to deliverables.
     */
    public function test19_RevokedOrRefundedOrderBlocksDeliverableAccess(): void
    {
        $userId = 25;
        $orderId = $this->createTestOrder(500.00, 500.00, $userId, ProductType::SERVICE, OrderLifecycleState::STATUS_PROCESSING);
        $token = bin2hex(random_bytes(32));

        $this->orderRepo->createDeliverable([
            'order_id'       => $orderId,
            'title'          => 'Final Service Package',
            'resource_type'  => 'url',
            'resource_url'   => 'https://example.com/package.zip',
            'download_token' => $token,
            'is_released'    => 1,
        ]);

        // Customer can access while processing
        $auth = $this->downloadService->authorizeDownload($token, $userId);
        $this->assertTrue($auth['is_deliverable']);

        // Set fulfillment to revoked
        $this->db->execute("UPDATE favorite_digital_orders SET fulfillment_status = ? WHERE id = ?", [
            OrderLifecycleState::FULFILLMENT_REVOKED,
            $orderId,
        ]);

        $this->expectException(DownloadException::class);
        $this->expectExceptionMessage('Access to deliverables for refunded orders has been revoked.');
        $this->downloadService->authorizeDownload($token, $userId);
    }

    /**
     * SCENARIO A: Service Deliverable file download works when order = processing.
     */
    public function testScenarioA_ServiceDeliverableFileDownloadWhenOrderProcessing(): void
    {
        $userId = 31;
        $orderId = $this->createTestOrder(650.00, 650.00, $userId, ProductType::SERVICE, OrderLifecycleState::STATUS_PROCESSING);

        $fileContent = "ZIP-SAMPLE-FILE-DELIVERABLE-CONTENT";
        $tmpPath = $this->createSampleFile('final_brand_package.zip', $fileContent);

        $stored = $this->storageService->storeUpload([
            'name'     => 'final_brand_package.zip',
            'tmp_name' => $tmpPath,
            'size'     => strlen($fileContent),
            'error'    => UPLOAD_ERR_OK,
        ]);

        $token = bin2hex(random_bytes(32));
        $this->orderRepo->createDeliverable([
            'order_id'       => $orderId,
            'title'          => 'Final Logo Design (ZIP)',
            'description'    => 'Complete high-resolution vector and raster assets.',
            'resource_type'  => 'file',
            'file_path'      => $stored['file_path'],
            'file_name'      => 'Final_Logo_Design.zip',
            'file_hash'      => $stored['file_hash'],
            'file_size'      => $stored['file_size'],
            'mime_type'      => 'application/zip',
            'download_token' => $token,
            'is_released'    => 1,
        ]);

        $auth = $this->downloadService->authorizeDownload($token, $userId);
        $this->assertTrue($auth['is_deliverable']);
        $this->assertFalse($auth['is_external']);
        $this->assertSame('Final_Logo_Design.zip', $auth['file_name']);
        $this->assertSame(strlen($fileContent), (int)$auth['file_size']);
        $this->assertFileExists($auth['file_path']);

        // Controller stream response
        $GLOBALS['_test_current_user']->id = $userId;
        $_SESSION['auth_user_id'] = $userId;
        $req = new Request([], [], ['REQUEST_METHOD' => 'GET']);
        $resp = $this->customerDownloadController->download($req, $token);
        $this->assertSame(200, $resp->getStatusCode());
    }

    /**
     * SCENARIO B: Service Deliverable URL deliverable works when order = processing.
     */
    public function testScenarioB_ServiceDeliverableUrlDeliverableWhenOrderProcessing(): void
    {
        $userId = 32;
        $orderId = $this->createTestOrder(750.00, 750.00, $userId, ProductType::SERVICE, OrderLifecycleState::STATUS_PROCESSING);

        $token = bin2hex(random_bytes(32));
        $externalUrl = 'https://www.figma.com/design/project-uuid-12345';

        $this->orderRepo->createDeliverable([
            'order_id'       => $orderId,
            'title'          => 'Figma Project Link',
            'description'    => 'Editable interactive UI workspace.',
            'resource_type'  => 'url',
            'resource_url'   => $externalUrl,
            'file_size'      => 0,
            'download_token' => $token,
            'is_released'    => 1,
        ]);

        $auth = $this->downloadService->authorizeDownload($token, $userId);
        $this->assertTrue($auth['is_deliverable']);
        $this->assertTrue($auth['is_external']);
        $this->assertSame($externalUrl, $auth['resource_url']);

        // Controller handles 302 safe redirect
        $GLOBALS['_test_current_user']->id = $userId;
        $_SESSION['auth_user_id'] = $userId;
        $req = new Request([], [], ['REQUEST_METHOD' => 'GET']);
        $resp = $this->customerDownloadController->download($req, $token);
        $this->assertSame(302, $resp->getStatusCode());
        $this->assertSame($externalUrl, $resp->getHeader('Location'));
    }

    /**
     * SCENARIO C: Service Deliverables remain downloadable when order = partial.
     */
    public function testScenarioC_ServiceDeliverablesRemainDownloadableWhenOrderPartial(): void
    {
        $userId = 33;
        $orderId = $this->createTestOrder(1000.00, 1000.00, $userId, ProductType::SERVICE, OrderLifecycleState::STATUS_PROCESSING);

        $token = bin2hex(random_bytes(32));
        $this->orderRepo->createDeliverable([
            'order_id'       => $orderId,
            'title'          => 'Milestone 1 Completed Codebase',
            'resource_type'  => 'url',
            'resource_url'   => 'https://example.com/milestone1.tar.gz',
            'download_token' => $token,
            'is_released'    => 1,
        ]);

        // Process partial refund: 400 refunded, 600 retained
        $this->refundService->processPartialRefund($orderId, 400.00, 'Partial refund for canceled Milestone 2', 'wallet', 'TX-PART-C');

        $order = $this->orderRepo->findOrder($orderId);
        $this->assertSame(OrderLifecycleState::STATUS_PARTIAL, $order->status);

        // Released deliverable MUST remain downloadable
        $auth = $this->downloadService->authorizeDownload($token, $userId);
        $this->assertTrue($auth['is_deliverable']);
        $this->assertSame('Milestone 1 Completed Codebase', $auth['deliverable']->title);

        // Customer order view displays deliverable with active Download button
        $GLOBALS['_test_current_user']->id = $userId;
        $_SESSION['auth_user_id'] = $userId;
        $req = new Request([], [], ['REQUEST_METHOD' => 'GET']);
        $viewHtml = $this->customerOrderController->view($req, (string)$order->order_number);

        $this->assertIsString($viewHtml);
        $this->assertStringContainsString('id="service-deliverables"', $viewHtml);
        $this->assertStringContainsString('Order Status: PARTIAL', $viewHtml);
        $this->assertStringContainsString('Milestone 1 Completed Codebase', $viewHtml);
        $this->assertStringContainsString('Open / Download &nearr;', $viewHtml);
        $this->assertStringContainsString('Released', $viewHtml);
    }

    /**
     * SCENARIO D: Service Deliverables downloadable when order = completed.
     */
    public function testScenarioD_ServiceDeliverablesDownloadableWhenOrderCompleted(): void
    {
        $userId = 34;
        $orderId = $this->createTestOrder(500.00, 500.00, $userId, ProductType::SERVICE, OrderLifecycleState::STATUS_COMPLETED);

        $token = bin2hex(random_bytes(32));
        $this->orderRepo->createDeliverable([
            'order_id'       => $orderId,
            'title'          => 'Final Service Delivery Documentation',
            'resource_type'  => 'url',
            'resource_url'   => 'https://example.com/handover-docs.pdf',
            'download_token' => $token,
            'is_released'    => 1,
        ]);

        $auth = $this->downloadService->authorizeDownload($token, $userId);
        $this->assertTrue($auth['is_deliverable']);
        $this->assertSame('Final Service Delivery Documentation', $auth['deliverable']->title);
    }

    /**
     * SCENARIO E: Service Deliverables BLOCKED when order = refunded.
     */
    public function testScenarioE_ServiceDeliverablesBlockedWhenOrderRefunded(): void
    {
        $userId = 35;
        $orderId = $this->createTestOrder(500.00, 500.00, $userId, ProductType::SERVICE, OrderLifecycleState::STATUS_REFUNDED);

        $token = bin2hex(random_bytes(32));
        $this->orderRepo->createDeliverable([
            'order_id'       => $orderId,
            'title'          => 'Service Deliverable On Refunded Order',
            'resource_type'  => 'url',
            'resource_url'   => 'https://example.com/deliverable.pdf',
            'download_token' => $token,
            'is_released'    => 1,
        ]);

        // Customer view renders Access Revoked state
        $order = $this->orderRepo->findOrder($orderId);
        $GLOBALS['_test_current_user']->id = $userId;
        $_SESSION['auth_user_id'] = $userId;
        $req = new Request([], [], ['REQUEST_METHOD' => 'GET']);
        $viewHtml = $this->customerOrderController->view($req, (string)$order->order_number);

        $this->assertIsString($viewHtml);
        $this->assertStringContainsString('Access to deliverables has been revoked', $viewHtml);
        $this->assertStringContainsString('Access Revoked', $viewHtml);

        // Backend authorization strictly rejects download
        $this->expectException(DownloadException::class);
        $this->expectExceptionMessage('Access to deliverables for refunded orders has been revoked.');
        $this->downloadService->authorizeDownload($token, $userId);
    }

    /**
     * SCENARIO F: Service Deliverables BLOCKED when order = revoked.
     */
    public function testScenarioF_ServiceDeliverablesBlockedWhenOrderRevoked(): void
    {
        $userId = 36;
        $orderId = $this->createTestOrder(500.00, 500.00, $userId, ProductType::SERVICE, OrderLifecycleState::STATUS_PROCESSING);

        $token = bin2hex(random_bytes(32));
        $this->orderRepo->createDeliverable([
            'order_id'       => $orderId,
            'title'          => 'Confidential Asset',
            'resource_type'  => 'url',
            'resource_url'   => 'https://example.com/confidential.zip',
            'download_token' => $token,
            'is_released'    => 1,
        ]);

        // Revoke fulfillment
        $this->orderRepo->updateFulfillmentStatus($orderId, OrderLifecycleState::FULFILLMENT_REVOKED);

        $this->expectException(DownloadException::class);
        $this->expectExceptionMessage('Access to deliverables for refunded orders has been revoked.');
        $this->downloadService->authorizeDownload($token, $userId);
    }

    /**
     * SCENARIO G: Unreleased deliverables CANNOT be downloaded.
     */
    public function testScenarioG_UnreleasedDeliverablesCannotBeDownloaded(): void
    {
        $userId = 37;
        $orderId = $this->createTestOrder(500.00, 500.00, $userId, ProductType::SERVICE, OrderLifecycleState::STATUS_PROCESSING);

        $token = bin2hex(random_bytes(32));
        $this->orderRepo->createDeliverable([
            'order_id'       => $orderId,
            'title'          => 'Unreleased Draft Deliverable',
            'description'    => 'Internal work in progress not yet approved for release.',
            'resource_type'  => 'url',
            'resource_url'   => 'https://example.com/draft.zip',
            'download_token' => $token,
            'is_released'    => 0, // Unreleased
        ]);

        // Customer view shows "Not released yet" badge and disabled button without usable link
        $order = $this->orderRepo->findOrder($orderId);
        $GLOBALS['_test_current_user']->id = $userId;
        $_SESSION['auth_user_id'] = $userId;
        $req = new Request([], [], ['REQUEST_METHOD' => 'GET']);
        $viewHtml = $this->customerOrderController->view($req, (string)$order->order_number);

        $this->assertIsString($viewHtml);
        $this->assertStringContainsString('Not released yet', $viewHtml);
        $this->assertStringNotContainsString("/download/{$token}", $viewHtml);

        // Backend authorization strictly rejects download
        $this->expectException(DownloadException::class);
        $this->expectExceptionMessage('This deliverable has not been released yet.');
        $this->downloadService->authorizeDownload($token, $userId);
    }

    /**
     * SCENARIO H: Download button on order list opens #service-deliverables immediately before View Receipt.
     */
    public function testScenarioH_DownloadButtonOnOrderListOpensServiceDeliverables(): void
    {
        $userId = 38;
        $orderId = $this->createTestOrder(500.00, 500.00, $userId, ProductType::SERVICE, OrderLifecycleState::STATUS_PROCESSING);

        $token = bin2hex(random_bytes(32));
        $this->orderRepo->createDeliverable([
            'order_id'       => $orderId,
            'title'          => 'Released Deliverable File',
            'resource_type'  => 'url',
            'resource_url'   => 'https://example.com/asset.zip',
            'download_token' => $token,
            'is_released'    => 1,
        ]);

        $order = $this->orderRepo->findOrder($orderId);
        $GLOBALS['_test_current_user']->id = $userId;
        $_SESSION['auth_user_id'] = $userId;

        $req = new Request([], [], ['REQUEST_METHOD' => 'GET']);
        $indexHtml = $this->customerOrderController->index($req);

        $this->assertIsString($indexHtml);
        $expectedLink = '/account/orders/' . urlencode((string)$order->order_number) . '#service-deliverables';
        $this->assertStringContainsString($expectedLink, $indexHtml);

        // Verify Download button appears immediately before View Receipt
        $receiptLink = '/account/orders/' . urlencode((string)$order->order_number);
        $posDownload = strpos($indexHtml, $expectedLink);
        $posReceipt = strpos($indexHtml, "class=\"btn-receipt\"");

        $this->assertNotFalse($posDownload);
        $this->assertNotFalse($posReceipt);
        $this->assertLessThan($posReceipt, $posDownload, "Download button must precede View Receipt");
    }

    /**
     * SCENARIO I: If no released deliverables exist, Download button on order list is disabled/inactive.
     */
    public function testScenarioI_IfNoReleasedDeliverablesExistDownloadButtonDisabled(): void
    {
        $userId = 39;
        // Service order with no deliverables added yet
        $orderId = $this->createTestOrder(500.00, 500.00, $userId, ProductType::SERVICE, OrderLifecycleState::STATUS_PROCESSING);
        $order = $this->orderRepo->findOrder($orderId);

        $GLOBALS['_test_current_user']->id = $userId;
        $_SESSION['auth_user_id'] = $userId;

        $req = new Request([], [], ['REQUEST_METHOD' => 'GET']);
        $indexHtml = $this->customerOrderController->index($req);

        $this->assertIsString($indexHtml);
        $serviceDeliverablesAnchor = '/account/orders/' . urlencode((string)$order->order_number) . '#service-deliverables';
        $this->assertStringNotContainsString($serviceDeliverablesAnchor, $indexHtml, "Disabled order must not have active #service-deliverables link");
        $this->assertStringContainsString('btn-download btn-disabled', $indexHtml);
        $this->assertStringContainsString('disabled', $indexHtml);
        $this->assertStringContainsString('No released deliverables available', $indexHtml);
    }

    /**
     * SCENARIO J: Digital Product downloads still work normally.
     */
    public function testScenarioJ_DigitalProductDownloadsStillWorkNormally(): void
    {
        $userId = 40;
        $productId = (int)$this->db->insert('favorite_digital_products', [
            'title'            => 'Standard Digital Template',
            'slug'             => 'standard-digital-template-' . bin2hex(random_bytes(3)),
            'product_type'     => ProductType::DIGITAL,
            'status'           => 'published',
            'original_price'   => '35.00',
            'discount_percent' => '0.00',
            'final_price'      => '35.00',
            'created_at'       => date('Y-m-d H:i:s'),
            'updated_at'       => date('Y-m-d H:i:s'),
        ]);

        $this->db->insert('favorite_digital_product_details', [
            'product_id'    => $productId,
            'resource_type' => 'url',
            'resource_url'  => 'https://cdn.example.com/template.zip',
            'file_size'     => 1048576,
            'file_name'     => 'template.zip',
        ]);

        $entId = $this->entitlementRepo->createEntitlement([
            'user_id'    => $userId,
            'product_id' => $productId,
            'status'     => 'active',
        ]);

        $tokenRecord = $this->downloadService->getOrCreateDownloadToken($userId, $productId, $entId);
        $auth = $this->downloadService->authorizeDownload((string)$tokenRecord->download_token, $userId);

        $this->assertEmpty($auth['is_deliverable'] ?? false);
        $this->assertSame('Standard Digital Template', $auth['product']->title);
        $this->assertSame('https://cdn.example.com/template.zip', $auth['resource_url']);
    }

    /**
     * SCENARIO K: Digital Product partial refund still blocked.
     */
    public function testScenarioK_DigitalProductPartialRefundStillBlocked(): void
    {
        $userId = 41;
        $orderId = $this->createTestOrder(200.00, 200.00, $userId, ProductType::DIGITAL, OrderLifecycleState::STATUS_COMPLETED);

        $walletBefore = (float)$this->walletService->getBalance($userId);
        $refundsBefore = count($this->refundRepo->findRefundsByOrderId($orderId));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Partial refund is not allowed for Digital Product orders.');

        try {
            $this->refundService->processPartialRefund($orderId, 50.00, 'Attempt partial refund on digital product');
        } finally {
            // Verify wallet and refund records were not mutated
            $walletAfter = (float)$this->walletService->getBalance($userId);
            $this->assertSame($walletBefore, $walletAfter);
            $this->assertSame($refundsBefore, count($this->refundRepo->findRefundsByOrderId($orderId)));
        }
    }

    /**
     * SCENARIO L: Digital Product full refund revokes access.
     */
    public function testScenarioL_DigitalProductFullRefundRevokesAccess(): void
    {
        $userId = 42;
        $orderId = $this->createTestOrder(150.00, 150.00, $userId, ProductType::DIGITAL, OrderLifecycleState::STATUS_COMPLETED);

        $productId = 101;
        $this->db->insert('favorite_digital_products', [
            'id'               => $productId,
            'title'            => 'Refundable Digital Product',
            'slug'             => 'refundable-product-' . bin2hex(random_bytes(3)),
            'product_type'     => ProductType::DIGITAL,
            'status'           => 'published',
            'original_price'   => '150.00',
            'discount_percent' => '0.00',
            'final_price'      => '150.00',
            'created_at'       => date('Y-m-d H:i:s'),
            'updated_at'       => date('Y-m-d H:i:s'),
        ]);

        $this->db->insert('favorite_digital_product_details', [
            'product_id'    => $productId,
            'resource_type' => 'url',
            'resource_url'  => 'https://cdn.example.com/asset.zip',
            'file_name'     => 'asset.zip',
        ]);

        $order = $this->orderRepo->findOrderWithItems($orderId);
        $orderItemId = (int)$order->items[0]->id;

        $entId = $this->entitlementRepo->createEntitlement([
            'user_id'     => $userId,
            'product_id'  => $productId,
            'source_type' => 'purchase',
            'source_id'   => $orderItemId,
            'status'      => 'active',
        ]);

        $tokenRecord = $this->downloadService->getOrCreateDownloadToken($userId, $productId, $entId);

        // Verify active before refund
        $auth = $this->downloadService->authorizeDownload((string)$tokenRecord->download_token, $userId);
        $this->assertSame('Refundable Digital Product', $auth['product']->title);

        // Process full refund
        $this->refundService->processRefund($orderId, 'Customer requested return', $userId, true);

        // Entitlement status must now be revoked
        $ent = $this->entitlementRepo->findEntitlement($entId);
        $this->assertSame('revoked', $ent->status);

        // Downloading with existing token must now be blocked
        $this->expectException(DownloadException::class);
        $this->expectExceptionMessage('Entitlement for this product has been revoked.');
        $this->downloadService->authorizeDownload((string)$tokenRecord->download_token, $userId);
    }
}
