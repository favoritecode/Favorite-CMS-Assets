<?php

declare(strict_types=1);

/**
 * Favorite Web Tools PSR-4 Autoloader
 *
 * Automatically maps FavoriteCMS\Tools\ namespace to plugins/favorite-web-tools/src/
 * and registers core plugin class alias.
 */
spl_autoload_register(function (string $class): void {
    $prefix = 'FavoriteCMS\\Tools\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = __DIR__ . '/src/' . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

// Register class alias for Core PluginManager candidate lookup
if (!class_exists('FavoriteCMS\\Plugins\\FavoriteWebToolsPlugin', false)) {
    class_alias(\FavoriteCMS\Tools\FavoriteWebToolsPlugin::class, 'FavoriteCMS\\Plugins\\FavoriteWebToolsPlugin');
}
if (!class_exists('FavoriteCMS\\Web\\FavoriteWebToolsPlugin', false)) {
    class_alias(\FavoriteCMS\Tools\FavoriteWebToolsPlugin::class, 'FavoriteCMS\\Web\\FavoriteWebToolsPlugin');
}

