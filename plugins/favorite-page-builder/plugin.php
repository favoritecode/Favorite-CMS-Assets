<?php
/**
 * Plugin Name: Favorite Page Builder
 * Description: Visual drag-and-drop page builder, JSON templates, dynamic grids, landing pages and commerce funnel layouts.
 * Version: 1.0.0
 * Author: Favorite CMS Team
 */
declare(strict_types=1);
namespace FavoriteCMS\PageBuilder;
require_once __DIR__ . '/autoload.php';
if (isset($app) && $app instanceof \FavoriteCMS\Core\Application) {
    FavoritePageBuilderPlugin::bootstrap($app);
} elseif (function_exists('app')) {
    $coreApp = app();
    if ($coreApp instanceof \FavoriteCMS\Core\Application) FavoritePageBuilderPlugin::bootstrap($coreApp);
}
