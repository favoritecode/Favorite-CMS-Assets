<?php
declare(strict_types=1);
namespace FavoriteCMS\Shop;
use FavoriteCMS\Core\Application;
use FavoriteCMS\Core\Database;
use FavoriteCMS\Core\Request;
use FavoriteCMS\Shop\Controllers\AdminProductController;
use FavoriteCMS\Shop\Controllers\AdminCategoryController;
use FavoriteCMS\Shop\Controllers\AdminPromotionsController;
use FavoriteCMS\Shop\Controllers\AdminOrderController;
use FavoriteCMS\Shop\Controllers\AdminDeliveryZoneController;
use Throwable;
final class FavoriteShopPlugin
{
    public const VERSION = '1.0.0';
    public const TABLES = [
        'favorite_shop_products','favorite_shop_product_categories','favorite_shop_product_category_map',
        'favorite_shop_product_variants','favorite_shop_inventory_movements','favorite_shop_carts',
        'favorite_shop_cart_items','favorite_shop_orders','favorite_shop_order_items',
        'favorite_shop_order_addresses','favorite_shop_shipments','favorite_shop_order_events','favorite_shop_offers','favorite_shop_offer_redemptions','favorite_shop_coupons','favorite_shop_coupon_redemptions','favorite_shop_delivery_zones','favorite_shop_settings'
    ];
    private static ?self $instance = null;
    private function __construct(private Application $app) {}
    public static function bootstrap(Application $app): self {
        if (self::$instance !== null) return self::$instance;
        $plugin = new self($app); $plugin->register(); $plugin->boot(); self::$instance = $plugin; return $plugin;
    }
    public function register(): void {
        if ($this->app->has(Database::class)) {
            $db = $this->app->make(Database::class);
            if (method_exists($db, 'registerPrefixableTables')) $db->registerPrefixableTables(self::TABLES);
        }
    }
    public function boot(): void {
        try { Installer::register($this->app); }
        catch (Throwable $e) { error_log('[Favorite Shop] Schema initialization failed: ' . $e->getMessage()); }
        if (class_exists(\FavoriteCMS\Core\Hook::class)) {
            \FavoriteCMS\Core\Hook::addAction('favorite.pay.intent.status_updated', function (array $event): void {
                if (($event['source_plugin'] ?? '') !== 'favorite-shop') return;
                $orderNumber = (string)($event['source_reference'] ?? '');
                $intentId = (string)($event['intent_id'] ?? '');
                $status = (string)($event['status'] ?? '');
                if ($orderNumber === '' || $intentId === '' || !in_array($status, ['awaiting_verification','pending','processing','succeeded','failed','cancelled'], true)) return;
                try {
                    $db = $this->app->make(Database::class);
                    if (!method_exists($db, 'getConnection') || !($pdo = $db->getConnection()) instanceof \PDO) return;
                    $query = $pdo->prepare('SELECT id,status,payment_status FROM favorite_shop_orders WHERE order_number=? AND payment_intent_id=? AND payment_method<>? LIMIT 1');
                    $query->execute([$orderNumber, $intentId, 'cash_on_delivery']);
                    $order = $query->fetch(\PDO::FETCH_ASSOC);
                    if (!$order || in_array((string)$order['status'], ['cancelled','returned'], true)) return;
                    if ($status === 'succeeded') {
                        $pdo->prepare("UPDATE favorite_shop_orders SET payment_status='paid',status=CASE WHEN status='pending' THEN 'processing' ELSE status END WHERE id=? AND payment_intent_id=?")->execute([(int)$order['id'], $intentId]);
                    } elseif ($status === 'failed' || $status === 'cancelled') {
                        $pdo->prepare("UPDATE favorite_shop_orders SET payment_status='failed' WHERE id=? AND payment_intent_id=? AND payment_status<>'paid'")->execute([(int)$order['id'], $intentId]);
                    } elseif ($status === 'awaiting_verification') {
                        $pdo->prepare("UPDATE favorite_shop_orders SET payment_status='awaiting_verification' WHERE id=? AND payment_intent_id=? AND payment_status<>'paid'")->execute([(int)$order['id'], $intentId]);
                    } elseif ($status === 'processing' || $status === 'pending') {
                        $pdo->prepare("UPDATE favorite_shop_orders SET payment_status='pending' WHERE id=? AND payment_intent_id=? AND payment_status NOT IN ('paid','awaiting_verification')")->execute([(int)$order['id'], $intentId]);
                    }
                } catch (Throwable $e) {
                    error_log('[Favorite Shop payment sync] '.$e->getMessage());
                }
            });
        }
        if (function_exists('add_route')) {
            $shop = fn(Request $request) => (new \FavoriteCMS\Shop\Controllers\CustomerShopController($this->app))->index($request);
            add_route('GET', '/shop', $shop);
            add_route('GET', '/shop/cart', fn(Request $request) => (new \FavoriteCMS\Shop\Controllers\CustomerShopController($this->app))->cart($request));
            add_route('POST', '/shop/cart/add/{id}', fn(Request $request, string $id) => (new \FavoriteCMS\Shop\Controllers\CustomerShopController($this->app))->add($request, $id));
            add_route('POST', '/shop/cart/remove/{id}', fn(Request $request, string $id) => (new \FavoriteCMS\Shop\Controllers\CustomerShopController($this->app))->remove($request, $id));
            add_route(['GET','POST'], '/shop/checkout', fn(Request $request) => (new \FavoriteCMS\Shop\Controllers\CustomerShopController($this->app))->checkout($request));
            add_route('GET', '/shop/order/{orderNumber}', fn(Request $request, string $orderNumber) => (new \FavoriteCMS\Shop\Controllers\CustomerShopController($this->app))->order($request, $orderNumber));
            add_route(['GET','POST'], '/shop/pay/{orderNumber}', fn(Request $request, string $orderNumber) => (new \FavoriteCMS\Shop\Controllers\CustomerShopController($this->app))->payment($request, $orderNumber));
        }
        if (function_exists('add_route')) {
            add_route(['GET','POST'], '/admin/page/favorite-shop-orders', fn(Request $request) => (new AdminOrderController($this->app))->handle($request));
            add_route(['GET','POST'], '/admin/page/favorite-shop-delivery', fn(Request $request) => (new AdminDeliveryZoneController($this->app))->handle($request));

        }
        if (function_exists('add_admin_menu')) {
            $categories = function (Request $request) { return (new AdminCategoryController($this->app))->handle($request); };
            $products = function (Request $request) {
                return (new AdminProductController($this->app))->handle($request);
            };
            add_admin_menu('favorite-shop', 'Favorite Shop', '🛍️', $products, 'manage_options', 57);
            $offers = function (Request $request) { return (new AdminPromotionsController($this->app, 'offers'))->handle($request); };
            $coupons = function (Request $request) { return (new AdminPromotionsController($this->app, 'coupons'))->handle($request); };
            if (function_exists('add_admin_submenu')) {
                add_admin_submenu('favorite-shop', 'favorite-shop-products', 'Products', $products, 'manage_options');
                add_admin_submenu('favorite-shop', 'favorite-shop-categories', 'Categories', $categories, 'manage_options');
                add_admin_submenu('favorite-shop', 'favorite-shop-offers', 'Offers & Sales', $offers, 'manage_options');
                add_admin_submenu('favorite-shop', 'favorite-shop-coupons', 'Coupons', $coupons, 'manage_options');
                $orders = function (Request $request) { return (new AdminOrderController($this->app))->handle($request); };
                add_admin_submenu('favorite-shop', 'favorite-shop-orders', 'Orders', $orders, 'manage_options');
                $delivery = function (Request $request) { return (new \FavoriteCMS\Shop\Controllers\AdminDeliveryZoneController($this->app))->handle($request); };
                add_admin_submenu('favorite-shop', 'favorite-shop-delivery', 'Delivery Zones', $delivery, 'manage_options');
            }
        }
    }
    public static function reset(): void { self::$instance = null; Installer::reset(); }
}
