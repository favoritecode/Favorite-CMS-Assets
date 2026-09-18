<?php

declare(strict_types=1);

namespace FavoriteCMS\Tests\Unit\Plugins\FavoritePay;

use CreateFavoritePayTables;
use CreateSettingsTable;
use FavoriteCMS\Core\Application;
use FavoriteCMS\Core\Currency;
use FavoriteCMS\Core\Database;
use FavoriteCMS\Core\Request;
use FavoriteCMS\Http\Controllers\Admin\SettingController;
use FavoriteCMS\Models\Setting;
use FavoriteCMS\Pay\Domain\Money;
use FavoriteCMS\Pay\Domain\PaymentMethodType;
use FavoriteCMS\Pay\Domain\PaymentStatus;
use FavoriteCMS\Pay\FavoritePayPlugin;
use FavoriteCMS\Pay\Gateways\ManualBangladeshGateway;
use FavoriteCMS\Pay\Services\CurrencyService;
use FavoriteCMS\Pay\Services\GatewayRegistry;
use FavoriteCMS\Pay\Services\PaymentService;
use FavoriteCMS\Pay\Services\RefundService;
use FavoriteCMS\Pay\Services\WalletService;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Global CMS Primary Currency Architecture & Denomination Safety in Favorite Pay.
 * Verifies that:
 * 1. Primary Currency can be changed after financial activity has started.
 * 2. Changing Primary Currency updates active wallet denomination without altering numeric balances.
 * 3. Historical transaction records preserve their explicitly stored currencies.
 * 4. Manual gateways support the configured Primary Currency directly without FX conversion.
 */
class PrimaryCurrencyFinancialSafetyTest extends TestCase
{
    private Database $db;
    private PDO $pdo;
    private Application $app;
    private CurrencyService $currencyService;
    private GatewayRegistry $registry;
    private PaymentService $paymentService;
    private WalletService $walletService;
    private RefundService $refundService;
    private FavoritePayPlugin $plugin;

    protected function setUp(): void
    {
        $_SESSION = [];
        Setting::clearCache();
        FavoritePayPlugin::reset();

        // 1. In-memory SQLite for complete relational isolation
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
            public function getConnection(): PDO
            {
                return $this->pdo;
            }
        };

        // Run migrations
        require_once APP_ROOT . '/database/migrations/009_create_settings_table.php';
        $coreMigration = new CreateSettingsTable($this->db);
        $coreMigration->up();

        require_once (is_dir(dirname(__DIR__) . '/database') ? dirname(__DIR__) : APP_ROOT . '/plugins/favorite-pay') . '/database/migrations/001_create_favorite_pay_tables.php';
        $payMigration = new CreateFavoritePayTables($this->db);
        $payMigration->up();

        // 2. Setup Application container & services
        $this->app = Application::getInstance();
        $this->app->instance(Database::class, $this->db);

        $this->currencyService = new CurrencyService(null, $this->db);
        $this->registry = new GatewayRegistry();

        $gateway = new ManualBangladeshGateway(
            'manual_bkash',
            'bKash Manual Payment',
            PaymentMethodType::MANUAL_BKASH,
            [
                'channel'        => 'bkash',
                'account_number' => '01700000000',
            ]
        );
        $this->registry->register($gateway);

        $this->paymentService = new PaymentService(
            $this->currencyService,
            $this->registry,
            $this->db
        );

        $this->walletService = new WalletService(
            $this->currencyService,
            $this->paymentService,
            $this->db
        );

        $this->refundService = new RefundService($this->paymentService, $this->registry, $this->db);

        $this->app->instance(PaymentService::class, $this->paymentService);
        $this->app->instance(WalletService::class, $this->walletService);
        $this->app->instance(\FavoriteCMS\Pay\Contracts\CurrencyServiceInterface::class, $this->currencyService);
        $this->app->instance(\FavoriteCMS\Pay\Contracts\WalletServiceInterface::class, $this->walletService);
        $this->app->instance(\FavoriteCMS\Pay\Contracts\PaymentServiceInterface::class, $this->paymentService);
        $this->app->instance(\FavoriteCMS\Pay\Contracts\RefundServiceInterface::class, $this->refundService);

        $this->plugin = new FavoritePayPlugin($this->app);
        $this->plugin->boot();

        // Ensure default currency is BDT initially
        Setting::set('general', 'primary_currency', 'BDT', 'string');
    }

    protected function tearDown(): void
    {
        FavoritePayPlugin::reset();
        Setting::clearCache();
    }

    /**
     * 1. Primary Currency can change when there is NO financial activity.
     */
    public function testPrimaryCurrencyCanChangeWhenNoFinancialActivity(): void
    {
        $this->assertFalse($this->plugin->hasFinancialActivity());
        $this->assertFalse(Currency::isPrimaryCurrencyLocked());

        $reason = null;
        $this->assertTrue(Currency::canChangePrimaryCurrency('USD', $reason));
        $this->assertNull($reason);

        // Change from BDT to USD
        Currency::setPrimaryCurrency('USD');
        $this->assertSame('USD', Currency::getPrimaryCurrency());
        $this->assertSame('USD', $this->currencyService->getBaseCurrency());
        $this->assertSame('USD', $this->walletService->getPrimaryCurrency());

        // Change from USD to EUR
        Currency::setPrimaryCurrency('EUR');
        $this->assertSame('EUR', Currency::getPrimaryCurrency());
    }

    /**
     * Gateway configuration alone must NOT block currency changes.
     */
    public function testGatewayConfigurationAloneDoesNotBlockCurrencyChange(): void
    {
        $nagad = new ManualBangladeshGateway('manual_nagad', 'Nagad', PaymentMethodType::MANUAL_NAGAD, []);
        $bank = new ManualBangladeshGateway('manual_bank', 'Bank', PaymentMethodType::MANUAL_BANK, []);
        $this->registry->register($nagad);
        $this->registry->register($bank);

        $this->assertFalse($this->plugin->hasFinancialActivity());
        $this->assertFalse(Currency::isPrimaryCurrencyLocked());

        Currency::setPrimaryCurrency('USD');
        $this->assertSame('USD', Currency::getPrimaryCurrency());
    }

    /**
     * 2. Primary Currency CAN be changed even after payment transaction exists.
     * Preserves historical transaction currency and does not perform mathematical FX.
     */
    public function testPrimaryCurrencyCanChangeEvenWhenPaymentTransactionExists(): void
    {
        $intent = $this->paymentService->createIntent(
            'favorite_shop',
            'order_101',
            Money::bdt(25000),
            ['customer_id' => 1]
        );

        $this->assertTrue($this->plugin->hasFinancialActivity());
        $this->assertFalse(Currency::isPrimaryCurrencyLocked());

        $reason = null;
        $this->assertTrue(Currency::canChangePrimaryCurrency('INR', $reason));

        // Change from BDT to INR
        Currency::setPrimaryCurrency('INR');
        $this->assertSame('INR', Currency::getPrimaryCurrency());
        $this->assertSame('INR', $this->currencyService->getBaseCurrency());
        $this->assertSame('INR', $this->walletService->getPrimaryCurrency());

        // Historical transaction MUST preserve original BDT currency and amount
        $tx = $this->db->selectOne("SELECT * FROM favorite_pay_transactions WHERE transaction_id = ?", [$intent->getId()]);
        $this->assertSame('BDT', $tx->base_currency);
        $this->assertSame(25000, (int)$tx->base_amount);
    }

    /**
     * 3. Primary Currency CAN be changed when a payment attempt exists.
     */
    public function testPrimaryCurrencyCanChangeWhenPaymentAttemptExists(): void
    {
        $intent = $this->paymentService->createIntent(
            'favorite_shop',
            'order_102',
            Money::bdt(15000),
            ['customer_id' => 1]
        );

        $this->paymentService->submitManualVerification(
            $intent->getId(),
            'manual_bkash',
            'TRX_ATTEMPT_001'
        );

        $this->assertTrue($this->plugin->hasFinancialActivity());

        // Change from BDT to EUR
        Currency::setPrimaryCurrency('EUR');
        $this->assertSame('EUR', Currency::getPrimaryCurrency());

        // Attempt retains historical currency
        $attempt = $this->db->selectOne("SELECT * FROM favorite_pay_attempts WHERE transaction_id = ?", [$intent->getId()]);
        $this->assertSame('BDT', $attempt->currency);
        $this->assertSame(15000, (int)$attempt->amount);
    }

    /**
     * 4. Primary Currency CAN be changed when a refund exists.
     */
    public function testPrimaryCurrencyCanChangeWhenRefundExists(): void
    {
        $intent = $this->paymentService->createIntent(
            'favorite_shop',
            'order_103',
            Money::bdt(10000),
            ['customer_id' => 1]
        );
        $this->paymentService->updateIntentStatus($intent->getId(), PaymentStatus::SUCCEEDED);
        $this->refundService->createRefund($intent->getId(), Money::bdt(5000), 'Customer requested partial refund');

        $this->assertTrue($this->plugin->hasFinancialActivity());

        // Change from BDT to USD
        Currency::setPrimaryCurrency('USD');
        $this->assertSame('USD', Currency::getPrimaryCurrency());

        // Refund retains historical currency
        $refund = $this->db->selectOne("SELECT * FROM favorite_pay_refunds WHERE transaction_id = ?", [$intent->getId()]);
        $this->assertSame('BDT', $refund->currency);
        $this->assertSame(5000, (int)$refund->amount);
    }

    /**
     * 5. Changing Primary Currency updates active wallet denomination WITHOUT altering numeric balance.
     * Example: BDT 500 becomes INR 500 (denomination updated, numeric 500 preserved, NO FX multiplication).
     */
    public function testPrimaryCurrencyChangeUpdatesActiveWalletDenominationWithoutAlteringNumericBalance(): void
    {
        $userId = 201;
        $this->walletService->deposit($userId, Money::bdt(50000), 'dep_bdt_201'); // 500.00 BDT

        $wallet = $this->db->selectOne("SELECT * FROM favorite_pay_wallets WHERE user_id = ?", [$userId]);
        $this->assertSame('BDT', $wallet->currency);
        $this->assertSame(50000, (int)$wallet->balance);

        // Administrator changes site Primary Currency to INR
        Currency::setPrimaryCurrency('INR');

        // Database wallet record updated to new denomination without changing numeric balance
        $walletAfter = $this->db->selectOne("SELECT * FROM favorite_pay_wallets WHERE user_id = ?", [$userId]);
        $this->assertSame('INR', $walletAfter->currency);
        $this->assertSame(50000, (int)$walletAfter->balance);

        // Wallet service returns balance in the new denomination
        $balance = $this->walletService->getBalance($userId);
        $this->assertSame('INR', $balance->getCurrency());
        $this->assertSame(50000, $balance->getAmount());
    }

    /**
     * 6. Historical ledger entries retain their original recorded currency.
     */
    public function testHistoricalLedgerEntriesRetainTheirOriginalRecordedCurrency(): void
    {
        $userId = 401;
        $intent = $this->paymentService->createIntent(
            'favorite_shop',
            'order_ledger_check',
            Money::bdt(60000),
            ['customer_id' => $userId]
        );
        $this->paymentService->updateIntentStatus($intent->getId(), PaymentStatus::SUCCEEDED);
        $this->walletService->settleSuccessfulPayment($intent->getId());

        // Change Primary Currency to GBP
        Currency::setPrimaryCurrency('GBP');

        // Ledger entry retains original recorded amount
        $ledger = $this->db->selectOne("SELECT * FROM favorite_pay_wallet_entries WHERE reference_id = ?", [$intent->getId()]);
        $this->assertSame(60000, (int)$ledger->amount);
        $this->assertSame(60000, (int)$ledger->balance_after);
        $meta = json_decode((string)$ledger->metadata, true);
        $this->assertSame('BDT', $meta['base_currency'] ?? $meta['currency'] ?? null);
    }

    /**
     * 7. Manual gateways dynamically support the site's primary currency without foreign exchange conversion.
     */
    public function testManualGatewaysSupportPrimaryCurrencyDynamically(): void
    {
        Currency::setPrimaryCurrency('INR');

        $gateway = $this->registry->get('manual_bkash');
        $this->assertInstanceOf(ManualBangladeshGateway::class, $gateway);

        // Supported currencies must include active primary currency 'INR'
        $this->assertContains('INR', $gateway->getSupportedCurrencies());

        // Payment intent in INR can create an attempt directly without error
        $intent = $this->paymentService->createIntent(
            'favorite_digital',
            'fd_ord_inr_300',
            new Money(30000, 'INR'), // 300.00 INR
            ['customer_id' => 99]
        );

        $attempt = $gateway->createAttempt($intent, [
            'trx_id'         => 'MANUAL_TRX_INR_123',
            'sender_account' => '01711111111',
        ]);

        $this->assertSame(PaymentStatus::AWAITING_VERIFICATION, $attempt->getStatus());
        $this->assertSame('INR', $attempt->getAmount()->getCurrency());
        $this->assertSame(30000, $attempt->getAmount()->getAmount());
    }

    /**
     * 8. Super-admin setting controller updates Primary Currency successfully even with existing financial activity.
     */
    public function testSuperAdminSettingControllerUpdatesPrimaryCurrencyWithFinancialActivity(): void
    {
        // 1. Establish financial activity
        $this->paymentService->createIntent(
            'favorite_shop',
            'admin_activity_exists',
            Money::bdt(10000),
            ['customer_id' => 1]
        );

        $this->assertTrue($this->plugin->hasFinancialActivity());

        $controller = new SettingController($this->app);

        // 2. Admin submits form with primary_currency = INR
        $_SESSION['_token'] = 'csrf_token_test_abc';
        $request = new Request([], [
            '_token'           => 'csrf_token_test_abc',
            'site_name'        => 'My Store',
            'primary_currency' => 'INR',
        ], [], [], [], ['REQUEST_METHOD' => 'POST']);

        $response = $controller->update($request);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('Settings saved successfully.', $_SESSION['flash_success'] ?? null);

        // Site primary currency MUST now be INR
        $this->assertSame('INR', Currency::getPrimaryCurrency());
        $this->assertSame('INR', Setting::get('general', 'primary_currency'));
        $this->assertSame('INR', $this->currencyService->getBaseCurrency());
    }

    /**
     * 9. Settlement with matching currencies succeeds.
     */
    public function testSettlementWithMatchingCurrenciesSucceeds(): void
    {
        $userId = 601;
        $intent = $this->paymentService->createIntent(
            'favorite_shop',
            'order_matching',
            Money::bdt(30000),
            ['customer_id' => $userId]
        );
        $this->paymentService->updateIntentStatus($intent->getId(), PaymentStatus::SUCCEEDED);

        $entry = $this->walletService->settleSuccessfulPayment($intent->getId());
        $this->assertSame('BDT', $entry->getAmount()->getCurrency());
        $this->assertSame(30000, $entry->getAmount()->getAmount());

        $balance = $this->walletService->getBalance($userId);
        $this->assertSame('BDT', $balance->getCurrency());
        $this->assertSame(30000, $balance->getAmount());
    }
}
