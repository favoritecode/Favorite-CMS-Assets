<?php
/**
 * Plugin Name: Favorite Web Tools
 * Plugin URI: https://github.com/favoritecode/Favorite-CMS-Universal
 * Description: Modular web tools platform for Favorite CMS supporting HTML, CSS, JavaScript, PHP, and Python API tools.
 * Version: 1.1.1
 * Author: Favorite CMS Team
 */

declare(strict_types=1);

namespace FavoriteCMS\Tools;

require_once __DIR__ . '/autoload.php';

// Bootstrap plugin when Favorite CMS boots
if (isset($app) && $app instanceof \FavoriteCMS\Core\Application) {
    FavoriteWebToolsPlugin::bootstrap($app);
} elseif (function_exists('app')) {
    $coreApp = app();
    if ($coreApp instanceof \FavoriteCMS\Core\Application) {
        FavoriteWebToolsPlugin::bootstrap($coreApp);
    }
}

