<?php
declare(strict_types=1);
namespace FavoriteCMS\Shop;
use FavoriteCMS\Core\Application;
use FavoriteCMS\Core\Database;
final class FavoriteShopPlugin
{
    public const VERSION = '1.0.0';
    public const TABLES = [
        'favorite_shop_products','favorite_shop_product_categories','favorite_shop_product_category_map',
        'favorite_shop_product_variants','favorite_shop_inventory_movements','favorite_shop_carts',
        'favorite_shop_cart_items','favorite_shop_orders','favorite_shop_order_items',
        'favorite_shop_order_addresses','favorite_shop_shipments','favorite_shop_order_events','favorite_shop_settings'
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
    public function boot(): void { Installer::register($this->app); }
    public static function reset(): void { self::$instance = null; Installer::reset(); }
}
