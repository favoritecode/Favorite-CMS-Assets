<?php
declare(strict_types=1);
spl_autoload_register(static function (string $class): void {
    $prefix = 'FavoriteCMS\\PageBuilder\\';
    if (!str_starts_with($class, $prefix)) return;
    $file = __DIR__ . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) require_once $file;
});
if (!class_exists('FavoriteCMS\\Plugins\\FavoritePageBuilderPlugin', false)) {
    class_alias(\FavoriteCMS\PageBuilder\FavoritePageBuilderPlugin::class, 'FavoriteCMS\\Plugins\\FavoritePageBuilderPlugin');
}
