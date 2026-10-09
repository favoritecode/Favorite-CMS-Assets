<?php
declare(strict_types=1);
namespace FavoriteCMS\Shop;
use FavoriteCMS\Core\Application;
use FavoriteCMS\Core\Database;
use FavoriteCMS\Core\Request;
use FavoriteCMS\Shop\Controllers\AdminProductController;
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
        if (function_exists('add_admin_menu')) {
            $products = function (Request $request) {
                return (new AdminProductController($this->app))->handle($request);
            };
            add_admin_menu('favorite-shop', 'Favorite Shop', '🛍️', $products, 'manage_options', 57);\n            $promotions = function (Request $request) { return (new AdminPromotionsController($this->app))->handle($request); };
            if (function_exists('add_admin_submenu')) {
                add_admin_submenu('favorite-shop', 'favorite-shop-products', 'Products', $products, 'manage_options');\n                add_admin_submenu('favorite-shop', 'favorite-shop-offers', 'Offers & Sales', $promotions, 'manage_options');\n                add_admin_submenu('favorite-shop', 'favorite-shop-coupons', 'Coupons', $promotions, 'manage_options');
            }
        }
    }
    public static function reset(): void { self::$instance = null; Installer::reset(); }
}
