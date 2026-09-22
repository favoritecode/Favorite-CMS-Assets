<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Support;

final class ViewRenderer
{
    private static string $viewsDir = '';

    public static function setViewsDir(string $dir): void
    {
        self::$viewsDir = rtrim($dir, '/\\');
    }

    public static function getViewsDir(): string
    {
        if (self::$viewsDir === '') {
            self::$viewsDir = dirname(__DIR__, 2) . '/views';
        }
        return self::$viewsDir;
    }

    public static function render(string $viewName, array $data = []): string
    {
        $path = self::getViewsDir() . '/' . ltrim($viewName, '/\\') . '.php';
        if (!is_file($path)) {
            return "<div class='fwt-error-notice'>View not found: " . htmlspecialchars($viewName, ENT_QUOTES, 'UTF-8') . "</div>";
        }

        extract($data, EXTR_SKIP);
        ob_start();
        include $path;
        return (string)ob_get_clean();
    }
}

