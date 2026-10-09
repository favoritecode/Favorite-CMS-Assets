<?php
declare(strict_types=1);
namespace FavoriteCMS\Shop;
use FavoriteCMS\Core\Application;
use FavoriteCMS\Core\Database;
use FavoriteCMS\Core\Request;
use FavoriteCMS\Shop\Controllers\AdminProductController;
use FavoriteCMS\Shop\Controllers\AdminCategoryController;
use FavoriteCMS\Shop\Controllers\AdminPromotionsController;
final class FavoriteShopPlugin
{
    public const VERSION = '1.0.0';
    public const TABLES = [
        'favorite_shop_products','favorite_shop_product_categories','favorite_shop_product_category_map',
        'favorite_shop_product_variants','favorite_shop_inventory_movements','favorite_shop_carts',
        'favorite_shop_cart_items','favorite_shop_orders','favorite_shop_order_items',
        'favorite_shop_order_addresses','favorite_shop_shipments','favorite_shop_order_events','favorite_shop_offers','favorite_shop_coupons','favorite_shop_coupon_redemptions','favorite_shop_settings'
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
        Installer::register($this->app);
        if (function_exists('add_route')) {
            $shop = fn(Request $request) => (new \FavoriteCMS\Shop\Controllers\CustomerShopController($this->app))->index($request);
            add_route('GET', '/shop', $shop);
            add_route('GET', '/shop/cart', fn(Request $request) => (new \FavoriteCMS\Shop\Controllers\CustomerShopController($this->app))->cart($request));
            add_route('POST', '/shop/cart/add/{id}', fn(Request $request, string $id) => (new \FavoriteCMS\Shop\Controllers\CustomerShopController($this->app))->add($request, $id));
            add_route('POST', '/shop/cart/remove/{id}', fn(Request $request, string $id) => (new \FavoriteCMS\Shop\Controllers\CustomerShopController($this->app))->remove($request, $id));
            add_route(['GET','POST'], '/shop/checkout', fn(Request $request) => (new \FavoriteCMS\Shop\Controllers\CustomerShopController($this->app))->checkout($request));
            add_route('GET', '/shop/order/{orderNumber}', fn(Request $request, string $orderNumber) => (new \FavoriteCMS\Shop\Controllers\CustomerShopController($this->app))->order($request, $orderNumber));
        }
        if (function_exists('add_route')) {
            add_route(['GET','POST'], '/admin/page/favorite-shop-orders', fn(Request $request) => (new AdminOrderController($this->app))->handle($request));
            add_route(['GET','POST'], '/admin/page/favorite-shop-delivery', fn(Request $request) => (new \FavoriteCMS\Shop\Controllers\AdminDeliveryZoneController($this->app))->handle($request));

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
                $delivery = function (Request $request) { return (new \\FavoriteCMS\\Shop\\Controllers\\AdminDeliveryZoneController($this->app))->handle($request); };
                add_admin_submenu('favorite-shop', 'favorite-shop-delivery', 'Delivery Zones', $delivery, 'manage_options');
            }
        }
    }
    public static function reset(): void { self::$instance = null; Installer::reset(); }
}
