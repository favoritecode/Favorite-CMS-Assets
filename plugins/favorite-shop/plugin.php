<?php
/** Plugin Name: Favorite Shop
 * Plugin URI: https://github.com/favoritecode/Favorite-CMS-Assets
 * Description: Physical products, inventory, shipping, orders, and Cash on Delivery for Favorite CMS.
 * Version: 1.0.0
 * Author: Favorite CMS Team
 */
declare(strict_types=1);
namespace FavoriteCMS\Shop;
require_once __DIR__ . '/autoload.php';
if (isset($app) && $app instanceof \FavoriteCMS\Core\Application) {
    FavoriteShopPlugin::bootstrap($app);
} elseif (function_exists('app')) {
    $coreApp = app();
    if ($coreApp instanceof \FavoriteCMS\Core\Application) FavoriteShopPlugin::bootstrap($coreApp);
}
