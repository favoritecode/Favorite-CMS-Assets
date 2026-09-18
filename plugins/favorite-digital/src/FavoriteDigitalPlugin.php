<?php

declare(strict_types=1);

namespace FavoriteCMS\Digital;

use FavoriteCMS\Core\Application;
use FavoriteCMS\Core\Database;
use FavoriteCMS\Core\Migrator;
use FavoriteCMS\Core\Request;
use FavoriteCMS\Digital\Controllers\AdminMembershipController;
use FavoriteCMS\Digital\Controllers\AdminOrderController;
use FavoriteCMS\Digital\Controllers\AdminPackageController;
use FavoriteCMS\Digital\Controllers\AdminProductController;
use FavoriteCMS\Digital\Controllers\AdminServiceController;
use FavoriteCMS\Digital\Controllers\CustomerOrderController;
use FavoriteCMS\Digital\Contracts\EntitlementCheckerInterface;
use FavoriteCMS\Digital\Repositories\OrderRepository;
use FavoriteCMS\Digital\Repositories\ProductRepository;
use FavoriteCMS\Digital\Services\DefaultEntitlementChecker;
use FavoriteCMS\Digital\Services\DigitalFileStorageService;
use FavoriteCMS\Digital\Services\MembershipLifecycleService;
use FavoriteCMS\Digital\Services\OrderService;
use FavoriteCMS\Digital\Services\ProductManagementService;

final class FavoriteDigitalPlugin
{
    public const TABLES = [
        'favorite_digital_products',
        'favorite_digital_product_details',
        'favorite_digital_service_details',
        'favorite_digital_packages',
        'favorite_digital_package_items',
        'favorite_digital_membership_plans',
        'favorite_digital_memberships',
        'favorite_digital_orders',
        'favorite_digital_order_items',
        'favorite_digital_order_payments',
        'favorite_digital_entitlements',
        'favorite_digital_downloads',
        'favorite_digital_wallets',
        'favorite_digital_wallet_transactions',
        'favorite_digital_refunds',
    ];

    private static ?self $instance = null;
    private Application $app;
    private bool $booted = false;
    public static bool $walletPillRenderedByTheme = false;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public static function getInstance(?Application $app = null): ?self
    {
        if (self::$instance === null && $app !== null) {
            self::$instance = new self($app);
        }
        return self::$instance;
    }

    public static function reset(): void
    {
        self::$instance = null;
        self::$walletPillRenderedByTheme = false;
    }

    public static function bootstrap(Application $app): self
    {
        $plugin = new self($app);
        $plugin->register();
        $plugin->boot();
        self::$instance = $plugin;
        return $plugin;
    }

    public function register(): void
    {
        // 1. Register prefixable tables with Database
        if ($this->app->has(Database::class)) {
            $db = $this->app->make(Database::class);
            if (method_exists($db, 'registerPrefixableTables')) {
                $db->registerPrefixableTables(self::TABLES);
            }
        }

        // 2. Bind Services and Repositories in Container
        $this->app->singleton(ProductRepository::class, function ($app): ProductRepository {
            return new ProductRepository($app->make(Database::class));
        });

        $this->app->singleton(DigitalFileStorageService::class, function (): DigitalFileStorageService {
            return new DigitalFileStorageService();
        });

        $this->app->singleton(ProductManagementService::class, function ($app): ProductManagementService {
            return new ProductManagementService(
                $app->make(ProductRepository::class),
                $app->make(DigitalFileStorageService::class)
            );
        });

        $this->app->singleton(AdminProductController::class, function ($app): AdminProductController {
            return new AdminProductController(
                $app,
                $app->make(ProductManagementService::class)
            );
        });

        $this->app->singleton(AdminServiceController::class, function ($app): AdminServiceController {
            return new AdminServiceController(
                $app,
                $app->make(ProductManagementService::class)
            );
        });

        $this->app->singleton(AdminPackageController::class, function ($app): AdminPackageController {
            return new AdminPackageController(
                $app,
                $app->make(ProductManagementService::class)
            );
        });

        $this->app->singleton(MembershipLifecycleService::class, function ($app): MembershipLifecycleService {
            return new MembershipLifecycleService(
                $app->make(ProductRepository::class)
            );
        });

        $this->app->singleton(AdminMembershipController::class, function ($app): AdminMembershipController {
            return new AdminMembershipController(
                $app,
                $app->make(MembershipLifecycleService::class),
                $app->make(ProductManagementService::class)
            );
        });

        $this->app->singleton(OrderRepository::class, function ($app): OrderRepository {
            return new OrderRepository($app->make(Database::class));
        });

        $this->app->singleton(Repositories\EntitlementRepository::class, function ($app): Repositories\EntitlementRepository {
            return new Repositories\EntitlementRepository($app->make(Database::class));
        });

        $this->app->singleton(Services\FulfillmentService::class, function ($app): Services\FulfillmentService {
            return new Services\FulfillmentService(
                $app->make(OrderRepository::class),
                $app->make(Repositories\EntitlementRepository::class),
                $app->make(ProductRepository::class),
                $app->make(MembershipLifecycleService::class),
                $app->has(Database::class) ? $app->make(Database::class) : null
            );
        });

        $this->app->singleton(EntitlementCheckerInterface::class, function ($app): EntitlementCheckerInterface {
            $db = $app->has(Database::class) ? $app->make(Database::class) : null;
            $entRepo = $app->has(Repositories\EntitlementRepository::class) ? $app->make(Repositories\EntitlementRepository::class) : null;
            $memSvc = $app->has(MembershipLifecycleService::class) ? $app->make(MembershipLifecycleService::class) : null;
            $prodRepo = $app->has(ProductRepository::class) ? $app->make(ProductRepository::class) : null;
            return new DefaultEntitlementChecker($db, $entRepo, $memSvc, $prodRepo);
        });

        $this->app->singleton(OrderService::class, function ($app): OrderService {
            return new OrderService(
                $app->make(OrderRepository::class),
                $app->make(ProductRepository::class),
                $app->make(MembershipLifecycleService::class),
                $app->make(EntitlementCheckerInterface::class),
                $app->has(Database::class) ? $app->make(Database::class) : null
            );
        });

        $this->app->singleton(AdminOrderController::class, function ($app): AdminOrderController {
            return new AdminOrderController(
                $app,
                $app->make(OrderService::class),
                $app->has(Services\FulfillmentService::class) ? $app->make(Services\FulfillmentService::class) : null,
                $app->has(Repositories\EntitlementRepository::class) ? $app->make(Repositories\EntitlementRepository::class) : null,
                $app->has(Services\RefundService::class) ? $app->make(Services\RefundService::class) : null
            );
        });

        $this->app->singleton(CustomerOrderController::class, function ($app): CustomerOrderController {
            return new CustomerOrderController(
                $app,
                $app->make(OrderService::class),
                $app->has(Repositories\RefundRepository::class) ? $app->make(Repositories\RefundRepository::class) : null
            );
        });

        $this->app->singleton(Repositories\WalletRepository::class, function ($app): Repositories\WalletRepository {
            return new Repositories\WalletRepository($app->make(Database::class));
        });

        $this->app->singleton(Services\WalletReconciliationService::class, function ($app): Services\WalletReconciliationService {
            return new Services\WalletReconciliationService(
                $app->has(Database::class) ? $app->make(Database::class) : null
            );
        });

        $this->app->singleton(Services\WalletService::class, function ($app): Services\WalletService {
            $favPayWallet = null;
            if ($app->has(\FavoriteCMS\Pay\Contracts\WalletServiceInterface::class)) {
                $favPayWallet = $app->make(\FavoriteCMS\Pay\Contracts\WalletServiceInterface::class);
            }
            return new Services\WalletService(
                $app->make(Repositories\WalletRepository::class),
                $app->has(Database::class) ? $app->make(Database::class) : null,
                $favPayWallet
            );
        });

        $this->app->singleton(Services\CheckoutService::class, function ($app): Services\CheckoutService {
            $favPay = null;
            if ($app->has(\FavoriteCMS\Pay\Contracts\PaymentServiceInterface::class)) {
                $favPay = $app->make(\FavoriteCMS\Pay\Contracts\PaymentServiceInterface::class);
            }

            return new Services\CheckoutService(
                $app->make(Repositories\OrderRepository::class),
                $app->make(Services\WalletService::class),
                $favPay,
                $app->has(Database::class) ? $app->make(Database::class) : null,
                $app->has(Services\FulfillmentService::class) ? $app->make(Services\FulfillmentService::class) : null
            );
        });

        $this->app->singleton(Controllers\CustomerCheckoutController::class, function ($app): Controllers\CustomerCheckoutController {
            return new Controllers\CustomerCheckoutController(
                $app,
                $app->make(Services\CheckoutService::class)
            );
        });

        $this->app->singleton(Repositories\DownloadRepository::class, function ($app): Repositories\DownloadRepository {
            return new Repositories\DownloadRepository($app->make(Database::class));
        });

        $this->app->singleton(Services\DownloadService::class, function ($app): Services\DownloadService {
            return new Services\DownloadService(
                $app->make(Repositories\DownloadRepository::class),
                $app->make(Repositories\EntitlementRepository::class),
                $app->make(Repositories\ProductRepository::class),
                $app->make(Services\MembershipLifecycleService::class),
                $app->make(Services\DefaultEntitlementChecker::class),
                $app->make(Services\DigitalFileStorageService::class),
                $app->has(Database::class) ? $app->make(Database::class) : null
            );
        });

        $this->app->singleton(Controllers\CustomerDownloadController::class, function ($app): Controllers\CustomerDownloadController {
            return new Controllers\CustomerDownloadController(
                $app,
                $app->make(Services\DownloadService::class),
                $app->make(Repositories\EntitlementRepository::class),
                $app->make(Repositories\ProductRepository::class),
                $app->make(Services\MembershipLifecycleService::class)
            );
        });

        $this->app->singleton(Repositories\RefundRepository::class, function ($app): Repositories\RefundRepository {
            return new Repositories\RefundRepository($app->make(Database::class));
        });

        $this->app->singleton(Services\RefundService::class, function ($app): Services\RefundService {
            return new Services\RefundService(
                $app->make(Repositories\OrderRepository::class),
                $app->make(Repositories\RefundRepository::class),
                $app->make(Services\WalletService::class),
                $app->make(Repositories\EntitlementRepository::class),
                $app->make(Services\MembershipLifecycleService::class),
                $app->has(Database::class) ? $app->make(Database::class) : null
            );
        });

        $this->app->singleton(Services\StorefrontService::class, function ($app): Services\StorefrontService {
            return new Services\StorefrontService(
                $app->make(Repositories\ProductRepository::class),
                $app->make(Contracts\EntitlementCheckerInterface::class),
                $app->make(Services\MembershipLifecycleService::class),
                $app->make(Services\OrderService::class),
                $app->make(Services\CheckoutService::class)
            );
        });

        $this->app->singleton(Controllers\CustomerStorefrontController::class, function ($app): Controllers\CustomerStorefrontController {
            return new Controllers\CustomerStorefrontController(
                $app,
                $app->make(Services\StorefrontService::class)
            );
        });

        $this->app->singleton(Services\CustomerAccountService::class, function ($app): Services\CustomerAccountService {
            return new Services\CustomerAccountService(
                $app->make(Repositories\EntitlementRepository::class),
                $app->make(Repositories\ProductRepository::class),
                $app->make(Repositories\OrderRepository::class),
                $app->make(Services\OrderService::class),
                $app->make(Services\MembershipLifecycleService::class),
                $app->make(Services\DownloadService::class),
                $app->make(Repositories\RefundRepository::class),
                $app->make(Services\WalletService::class),
                $app->has(Database::class) ? $app->make(Database::class) : null
            );
        });

        $this->app->singleton(Controllers\CustomerAccountController::class, function ($app): Controllers\CustomerAccountController {
            return new Controllers\CustomerAccountController(
                $app,
                $app->make(Services\CustomerAccountService::class)
            );
        });

        $this->app->singleton(Services\WalletRechargeService::class, function ($app): Services\WalletRechargeService {
            return new Services\WalletRechargeService(
                $app->make(Repositories\WalletRepository::class),
                $app->make(Services\WalletService::class),
                $app->has(\FavoriteCMS\Pay\Contracts\PaymentServiceInterface::class) ? $app->make(\FavoriteCMS\Pay\Contracts\PaymentServiceInterface::class) : null,
                $app->has(\FavoriteCMS\Pay\Contracts\CurrencyServiceInterface::class) ? $app->make(\FavoriteCMS\Pay\Contracts\CurrencyServiceInterface::class) : null,
                $app->has(Database::class) ? $app->make(Database::class) : null
            );
        });

        $this->app->singleton(Controllers\CustomerWalletController::class, function ($app): Controllers\CustomerWalletController {
            return new Controllers\CustomerWalletController(
                $app,
                $app->make(Services\WalletService::class),
                $app->make(Services\WalletRechargeService::class),
                $app->has(\FavoriteCMS\Pay\Contracts\PaymentServiceInterface::class) ? $app->make(\FavoriteCMS\Pay\Contracts\PaymentServiceInterface::class) : null
            );
        });
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        // Register Admin Menus
        if (function_exists('add_admin_menu')) {
            $productHandler = function (Request $request) {
                $controller = $this->app->make(AdminProductController::class);
                return $controller->handle($request);
            };

            $serviceHandler = function (Request $request) {
                $controller = $this->app->make(AdminServiceController::class);
                return $controller->handle($request);
            };

            $packageHandler = function (Request $request) {
                $controller = $this->app->make(AdminPackageController::class);
                return $controller->handle($request);
            };

            $membershipHandler = function (Request $request) {
                $controller = $this->app->make(AdminMembershipController::class);
                return $controller->handle($request);
            };

            $orderHandler = function (Request $request) {
                $controller = $this->app->make(AdminOrderController::class);
                return $controller->handle($request);
            };

            add_admin_menu(
                'favorite-digital',
                'Digital Store',
                '📦',
                $productHandler,
                'manage_options',
                56
            );

            if (function_exists('add_admin_submenu')) {
                add_admin_submenu(
                    'favorite-digital',
                    'favorite-digital',
                    'Digital Products',
                    $productHandler,
                    'manage_options'
                );

                add_admin_submenu(
                    'favorite-digital',
                    'favorite-digital-services',
                    'Services',
                    $serviceHandler,
                    'manage_options'
                );

                add_admin_submenu(
                    'favorite-digital',
                    'favorite-digital-packages',
                    'Packages',
                    $packageHandler,
                    'manage_options'
                );

                add_admin_submenu(
                    'favorite-digital',
                    'favorite-digital-memberships',
                    'Memberships',
                    $membershipHandler,
                    'manage_options'
                );

                add_admin_submenu(
                    'favorite-digital',
                    'favorite-digital-orders',
                    'Orders',
                    $orderHandler,
                    'manage_options'
                );
            }
        }

        // Backward compatibility redirect for legacy /admin/page/favorite-digital-products URL
        if (function_exists('add_route')) {
            add_route(['GET', 'POST'], '/admin/page/favorite-digital-products', function (Request $request) {
                $qs = $request->server('QUERY_STRING', '');
                return Response::redirect('/admin/page/favorite-digital' . ($qs !== '' ? '?' . $qs : ''));
            });
        }

        // Register Customer Frontend Routes
        if (function_exists('add_route')) {
            add_route('GET', '/account/orders', function (Request $request) {
                $controller = $this->app->make(CustomerOrderController::class);
                return $controller->index($request);
            });

            add_route('GET', '/account/orders/{orderNumber}', function (Request $request, string $orderNumber) {
                $controller = $this->app->make(CustomerOrderController::class);
                return $controller->view($request, $orderNumber);
            });

            add_route('GET', '/checkout/{orderNumber}', function (Request $request, string $orderNumber) {
                $controller = $this->app->make(Controllers\CustomerCheckoutController::class);
                return $controller->handle($request, $orderNumber);
            });

            add_route('POST', '/checkout/{orderNumber}', function (Request $request, string $orderNumber) {
                $controller = $this->app->make(Controllers\CustomerCheckoutController::class);
                return $controller->handle($request, $orderNumber);
            });

            add_route('GET', '/download/{token}', function (Request $request, string $token) {
                $controller = $this->app->make(Controllers\CustomerDownloadController::class);
                return $controller->download($request, $token);
            });

            add_route('GET', '/account/downloads', function (Request $request) {
                $controller = $this->app->make(Controllers\CustomerDownloadController::class);
                return $controller->index($request);
            });

            // Customer Storefront Routes
            add_route('GET', '/store', function (Request $request) {
                $controller = $this->app->make(Controllers\CustomerStorefrontController::class);
                return $controller->index($request);
            });

            add_route('GET', '/digital-store', function (Request $request) {
                $controller = $this->app->make(Controllers\CustomerStorefrontController::class);
                return $controller->index($request);
            });

            add_route('GET', '/store/{slug}', function (Request $request, string $slug) {
                $controller = $this->app->make(Controllers\CustomerStorefrontController::class);
                return $controller->show($request, $slug);
            });

            add_route('GET', '/digital-store/{slug}', function (Request $request, string $slug) {
                $controller = $this->app->make(Controllers\CustomerStorefrontController::class);
                return $controller->show($request, $slug);
            });

            add_route('POST', '/store/{slug}/buy', function (Request $request, string $slug) {
                $controller = $this->app->make(Controllers\CustomerStorefrontController::class);
                return $controller->buy($request, $slug);
            });

            // Customer Digital Account & Library Routes
            add_route('GET', '/account/digital', function (Request $request) {
                $controller = $this->app->make(Controllers\CustomerAccountController::class);
                return $controller->library($request);
            });

            add_route('GET', '/account/library', function (Request $request) {
                $controller = $this->app->make(Controllers\CustomerAccountController::class);
                return $controller->library($request);
            });

            add_route('GET', '/account/resource/{productId}', function (Request $request, string $productId) {
                $controller = $this->app->make(Controllers\CustomerAccountController::class);
                return $controller->accessResource($request, (int)$productId);
            });

            add_route('GET', '/account/membership', function (Request $request) {
                $controller = $this->app->make(Controllers\CustomerAccountController::class);
                return $controller->membership($request);
            });

            add_route('GET', '/account/memberships', function (Request $request) {
                $controller = $this->app->make(Controllers\CustomerAccountController::class);
                return $controller->membership($request);
            });

            add_route('POST', '/account/membership/toggle-auto-renew', function (Request $request) {
                $controller = $this->app->make(Controllers\CustomerAccountController::class);
                return $controller->toggleAutoRenew($request);
            });

            add_route('GET', '/account/refunds', function (Request $request) {
                $controller = $this->app->make(Controllers\CustomerAccountController::class);
                return $controller->refunds($request);
            });

            add_route('GET', '/account/digital/refunds', function (Request $request) {
                $controller = $this->app->make(Controllers\CustomerAccountController::class);
                return $controller->refunds($request);
            });

            add_route('GET', '/account/wallet', function (Request $request) {
                $controller = $this->app->make(Controllers\CustomerWalletController::class);
                return $controller->index($request);
            });

            add_route('POST', '/account/wallet/recharge', function (Request $request) {
                $controller = $this->app->make(Controllers\CustomerWalletController::class);
                return $controller->recharge($request);
            });

            add_route('GET', '/account/wallet/recharge/manual', function (Request $request) {
                $controller = $this->app->make(Controllers\CustomerWalletController::class);
                return $controller->showManual($request);
            });

            add_route('POST', '/account/wallet/recharge/manual', function (Request $request) {
                $controller = $this->app->make(Controllers\CustomerWalletController::class);
                return $controller->submitManual($request);
            });

            add_route('GET', '/account/wallet/recharge/callback', function (Request $request) {
                $controller = $this->app->make(Controllers\CustomerWalletController::class);
                return $controller->callback($request);
            });

            add_route('POST', '/account/wallet/recharge/retry', function (Request $request) {
                $controller = $this->app->make(Controllers\CustomerWalletController::class);
                return $controller->retry($request);
            });
        }

        // Lifecycle and Payment hooks
        if (function_exists('add_action')) {
            add_action('favorite.pay.payment.succeeded', function (array $data): void {
                if (($data['source_plugin'] ?? '') === 'favorite-digital') {
                    $txId = (string)($data['transaction_id'] ?? '');
                    $ref = (string)($data['source_reference'] ?? '');

                    if (str_starts_with($ref, 'wrc_') && $this->app->has(Services\WalletRechargeService::class)) {
                        try {
                            $this->app->make(Services\WalletRechargeService::class)->settleRecharge($txId);
                        } catch (\Throwable) {
                        }
                    } elseif (is_numeric($ref) && (int)$ref > 0 && $this->app->has(Services\CheckoutService::class)) {
                        try {
                            $this->app->make(Services\CheckoutService::class)->verifyAndSettlePayment((int)$ref, $txId);
                        } catch (\Throwable) {
                        }
                    }
                }
            });

            add_action('favorite.pay.manual.approved', function (array $data): void {
                $attemptId = (string)($data['attempt_id'] ?? '');
                if ($attemptId !== '' && $this->app->has(\FavoriteCMS\Pay\Contracts\PaymentServiceInterface::class)) {
                    try {
                        $payService = $this->app->make(\FavoriteCMS\Pay\Contracts\PaymentServiceInterface::class);
                        if (method_exists($payService, 'getAttempt')) {
                            $attempt = $payService->getAttempt($attemptId);
                            if ($attempt) {
                                $intent = $payService->getIntent($attempt->getIntentId());
                                if ($intent && $intent->getSourcePlugin() === 'favorite-digital') {
                                    $ref = (string)$intent->getSourceReference();
                                    if (str_starts_with($ref, 'wrc_') && $this->app->has(Services\WalletRechargeService::class)) {
                                        $this->app->make(Services\WalletRechargeService::class)->settleRecharge($intent->getId());
                                    } elseif (is_numeric($ref) && (int)$ref > 0 && $this->app->has(Services\CheckoutService::class)) {
                                        $orderId = (int)$ref;
                                        $this->app->make(Services\CheckoutService::class)->verifyAndSettlePayment($orderId, $intent->getId());
                                    }
                                }
                            }
                        }
                    } catch (\Throwable) {
                    }
                }
            });

            add_action('account_menu_init', function (): void {
                $this->registerAccountMenuItems();
            });

            add_action('plugins.loaded', function (): void {
                $this->interceptFavoritePayWallet();
            });

            add_action('favorite.pay.payment.succeeded', function (array $data): void {
                $this->interceptFavoritePayWallet();
            }, 5);

            add_action('plugin.activated', function (string $pluginId): void {
                if ($pluginId === 'favorite-digital') {
                    $this->onActivate();
                }
            });

            add_action('plugin.deactivated', function (string $pluginId): void {
                if ($pluginId === 'favorite-digital') {
                    $this->onDeactivate();
                }
            });

            add_action('currency.primary_changed', function (array $data): void {
                $newCurrency = $data['new'] ?? null;
                if (!empty($newCurrency) && $this->app->has(Database::class)) {
                    try {
                        $db = $this->app->make(Database::class);
                        if (method_exists($db, 'registerPrefixableTables')) {
                            $db->registerPrefixableTables(self::TABLES);
                        }
                        $clean = strtoupper(trim((string)$newCurrency));
                        if ($db->tableExists('favorite_digital_products')) {
                            $db->execute("UPDATE favorite_digital_products SET currency = ?", [$clean]);
                        }
                        if ($db->tableExists('favorite_digital_wallets')) {
                            $db->execute("UPDATE favorite_digital_wallets SET currency = ?", [$clean]);
                        }
                    } catch (\Throwable) {
                    }
                }
            });
        }

        $this->registerAccountMenuItems();
        $this->interceptFavoritePayWallet();

        // Run safe narrow reconciliation for historical membership credits
        if ($this->app->has(Services\WalletReconciliationService::class)) {
            try {
                $this->app->make(Services\WalletReconciliationService::class)->reconcile();
            } catch (\Throwable) {
            }
        }

        // Listen for global primary currency denomination changes
        if (function_exists('add_action')) {
            add_action('currency.primary_changed', [self::class, 'handlePrimaryCurrencyChanged']);
        } elseif (class_exists(\FavoriteCMS\Core\Hook::class)) {
            \FavoriteCMS\Core\Hook::addAction('currency.primary_changed', [self::class, 'handlePrimaryCurrencyChanged']);
        }

        // Listen for account menu render to inject Premium indicator and wallet fallback
        if (function_exists('add_filter')) {
            add_filter('render_account_menu', [$this, 'filterRenderAccountMenu'], 10, 4);
        } elseif (class_exists(\FavoriteCMS\Core\Hook::class)) {
            \FavoriteCMS\Core\Hook::addFilter('render_account_menu', [$this, 'filterRenderAccountMenu'], 10, 4);
        }

        $this->booted = true;
    }

    public static function handlePrimaryCurrencyChanged(array $data): void
    {
        $newCurrency = $data['new'] ?? 'BDT';
        $db = null;
        if (function_exists('app')) {
            try {
                $app = app();
                if ($app instanceof Application && $app->has(Database::class)) {
                    $db = $app->make(Database::class);
                }
            } catch (\Throwable) {}
        }
        if ($db === null && self::$instance !== null && self::$instance->app->has(Database::class)) {
            $db = self::$instance->app->make(Database::class);
        }

        if ($db instanceof Database) {
            try {
                $db->execute("UPDATE `favorite_digital_products` SET `currency` = :currency WHERE `status` = 'published'", [
                    'currency' => $newCurrency,
                ]);
            } catch (\Throwable) {}
            try {
                $db->execute("UPDATE `favorite_digital_wallets` SET `currency` = :currency WHERE `status` = 'active'", [
                    'currency' => $newCurrency,
                ]);
            } catch (\Throwable) {}
        }
    }

    public function registerAccountMenuItems(): void
    {
        if (!function_exists('register_account_menu_item')) {
            return;
        }

        register_account_menu_item([
            'id'     => 'digital_membership',
            'label'  => 'Membership',
            'url'    => '/account/membership',
            'icon'   => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3l3 6 3-6 3 6 3-6v14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V3z"></path></svg>',
            'order'  => 12,
            'plugin' => 'favorite-digital',
        ]);
    }

    /**
     * Filter render_account_menu HTML to inject:
     * 1. Small diamond indicator beside profile icon if user has ACTIVE Premium Membership.
     * 2. Wallet balance indicator immediately left of profile menu if not already rendered by theme.
     *
     * @param string $html
     * @param array $items
     * @param object|null $user
     * @param array $options
     * @return string
     */
    public function filterRenderAccountMenu(string $html, array $items, ?object $user, array $options = []): string
    {
        if ($user === null || empty($user->id)) {
            return $html;
        }

        $userId = (int)$user->id;

        // 1. Fallback wallet pill if theme didn't render it directly
        if (!self::$walletPillRenderedByTheme) {
            $formattedBalance = function_exists('fdig_get_wallet_balance')
                ? fdig_get_wallet_balance($userId)
                : null;

            if ($formattedBalance !== null) {
                $walletUrl = function_exists('site_path')
                    ? site_path('/account/wallet')
                    : (function_exists('fw_url') ? fw_url('/account/wallet') : '/account/wallet');

                $walletPill = '<a class="header-wallet-pill" href="' . htmlspecialchars($walletUrl, ENT_QUOTES, 'UTF-8') . '" title="My Wallet Balance">'
                    . '<svg class="icon" viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/><circle cx="16" cy="15" r="1"/></svg>'
                    . '<span class="header-wallet-amount">' . htmlspecialchars($formattedBalance, ENT_QUOTES, 'UTF-8') . '</span>'
                    . '</a>';

                $html = $walletPill . "\n" . $html;
            }
        }

        // Reset theme render flag
        self::$walletPillRenderedByTheme = false;

        // 2. Active Premium Membership diamond indicator
        $isPremium = function_exists('fdig_is_premium_active')
            ? fdig_is_premium_active($userId)
            : false;

        if ($isPremium) {
            $diamondIcon = '<span class="cms-premium-badge" title="Active Premium Member" aria-label="Active Premium Member" style="display:inline-flex;align-items:center;justify-content:center;color:#f59e0b;margin-left:0.25rem;vertical-align:middle;">'
                . '<svg class="icon icon-premium-diamond" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
                . '<path d="M6 3h12l4 6-10 12L2 9l4-6z" fill="#f59e0b" fill-opacity="0.25"/>'
                . '<path d="M11 3 8 9l4 13 4-13-3-6"/>'
                . '<path d="M2 9h20"/>'
                . '</svg>'
                . '</span>';

            $chevronPos = strpos($html, '<svg class="cms-account-chevron"');
            if ($chevronPos !== false) {
                $html = substr_replace($html, $diamondIcon . "\n                ", $chevronPos, 0);
            } else {
                $btnClosePos = strpos($html, '</button>');
                if ($btnClosePos !== false) {
                    $html = substr_replace($html, "\n                " . $diamondIcon, $btnClosePos, 0);
                }
            }
        }

        return $html;
    }

    public function interceptFavoritePayWallet(): void
    {
        if (!interface_exists(\FavoriteCMS\Pay\Contracts\WalletServiceInterface::class)) {
            return;
        }

        $abstract = \FavoriteCMS\Pay\Contracts\WalletServiceInterface::class;
        if ($this->app->has($abstract)) {
            $inner = $this->app->make($abstract);
            if ($inner instanceof \FavoriteCMS\Pay\Contracts\WalletServiceInterface && !($inner instanceof Services\FavoritePayWalletInterceptor)) {
                $interceptor = new Services\FavoritePayWalletInterceptor($inner, $this->app);
                $this->app->instance($abstract, $interceptor);
            }
        }
    }

    public function isFavoritePayAvailable(): bool
    {
        return class_exists(\FavoriteCMS\Pay\FavoritePayPlugin::class);
    }

    public function onActivate(): void
    {
        try {
            $this->runMigrations();
        } catch (\Throwable $e) {
            if (function_exists('cms_log')) {
                cms_log("Favorite Digital migration failed on activation: " . $e->getMessage(), 'error', ['plugin' => 'favorite-digital']);
            }
        }

        if (function_exists('cms_log')) {
            cms_log('Favorite Digital plugin activated successfully.', 'info', ['plugin' => 'favorite-digital']);
        }
    }

    public function onDeactivate(): void
    {
        if (function_exists('cms_log')) {
            cms_log('Favorite Digital plugin deactivated.', 'info', ['plugin' => 'favorite-digital']);
        }
    }

    public function runMigrations(): array
    {
        if (!$this->app->has(Database::class)) {
            return [];
        }

        $db = $this->app->make(Database::class);
        if (method_exists($db, 'registerPrefixableTables')) {
            $db->registerPrefixableTables(self::TABLES);
        }

        $migrator = new Migrator($db);
        $migrationsPath = __DIR__ . '/../database/migrations';
        return $migrator->migrate($migrationsPath);
    }
}
