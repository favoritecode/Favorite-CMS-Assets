<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Support;

use FavoriteCMS\Models\Setting;
use Throwable;

final class ToolThemeShell
{
    public static function render(string $viewPath, array $data = [], string $pageTitle = 'Web Tools', string $pageDescription = ''): string
    {
        if (!is_file($viewPath)) {
            return "<div class='notice notice-error'>View not found.</div>";
        }

        $viewHtml = self::renderFile($viewPath, $data);
        $theme = self::resolveActiveTheme();
        if ($theme === null) {
            return $viewHtml;
        }

        [$content, $headHtml] = self::splitDocument($viewHtml);

        $siteName = 'Favorite CMS';
        try {
            if (class_exists(Setting::class)) {
                $siteName = (string)Setting::get('general', 'site_name', $siteName);
            }
        } catch (Throwable) {
        }

        $title = $pageTitle;
        $metaTitle = $title . ' — ' . $siteName;
        $metaDescription = $pageDescription;
        $fwHasSidebar = false;
        $fcdHasSidebar = false;
        $bodyClass = 'favorite-tools-shell favorite-web-tools-page';

        // Include frontend assets in head if available
        $pluginAssetsHead = self::generateAssetTags();
        $additionalHeadHtml = $pluginAssetsHead . "\n" . $headHtml;

        $startObLevel = ob_get_level();
        ob_start();
        try {
            include $theme['header'];
            echo '<main class="site-main site-main--tools fwt-main-content" id="main-content" tabindex="-1">';
            echo $content;
            echo '</main>';
            include $theme['footer'];
            return (string)ob_get_clean();
        } catch (Throwable $e) {
            while (ob_get_level() > $startObLevel) {
                ob_end_clean();
            }
            return $viewHtml;
        }
    }

    private static function renderFile(string $viewPath, array $data): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        include $viewPath;
        return (string)ob_get_clean();
    }

    /**
     * Dynamically resolve the active theme from Core CMS.
     *
     * @return array{header:string,footer:string,theme_id:string}|null
     */
    public static function resolveActiveTheme(): ?array
    {
        $activeThemeId = null;
        if (function_exists('active_theme_id')) {
            $activeThemeId = active_theme_id();
        }
        if ($activeThemeId === null || $activeThemeId === '') {
            try {
                if (class_exists(Setting::class)) {
                    $activeThemeId = (string)Setting::get('theme', 'active_theme', 'favorite-web');
                }
            } catch (Throwable) {
                $activeThemeId = 'favorite-web';
            }
        }
        if ($activeThemeId === null || preg_match('/^[a-zA-Z0-9_-]+$/', $activeThemeId) !== 1) {
            $activeThemeId = 'favorite-web';
        }

        $themeIdsToTry = array_unique([$activeThemeId, 'favorite-web', 'default']);

        $appRoot = defined('APP_ROOT') ? APP_ROOT : (function_exists('app_root') ? app_root() : dirname(__DIR__, 4));
        $rootDirs = array_unique(array_filter([
            rtrim((string)$appRoot, '/\\'),
            dirname(__DIR__, 4),
            dirname(__DIR__, 3),
            dirname(__DIR__, 5),
        ]));

        foreach ($themeIdsToTry as $tid) {
            foreach ($rootDirs as $root) {
                $themeDir = $root . '/themes/' . $tid;
                $header = $themeDir . '/header.php';
                $footer = $themeDir . '/footer.php';
                if (is_dir($themeDir) && is_file($header) && is_file($footer)) {
                    return [
                        'header'   => $header,
                        'footer'   => $footer,
                        'theme_id' => $tid,
                    ];
                }
            }
        }

        return null;
    }

    private static function generateAssetTags(): string
    {
        $cssPath = '/plugins/favorite-web-tools/assets/css/tools-frontend.css';
        $jsPath = '/plugins/favorite-web-tools/assets/js/tools-frontend.js';

        $inlineCss = '';
        $cssFile = dirname(__DIR__, 2) . '/assets/css/tools-frontend.css';
        if (is_file($cssFile)) {
            $cssContent = @file_get_contents($cssFile);
            if ($cssContent !== false && trim($cssContent) !== '') {
                $inlineCss = '<style id="fwt-frontend-inline-css">' . $cssContent . '</style>' . "\n";
            }
        }

        return $inlineCss
             . '<link rel="stylesheet" href="' . $cssPath . '?v=1.0.0">' . "\n"
             . '<script src="' . $jsPath . '?v=1.0.0" defer></script>';
    }

    /** @return array{0:string,1:string} */
    private static function splitDocument(string $html): array
    {
        $headHtml = '';
        if (preg_match_all('#<style\b[^>]*>(.*?)</style>#is', $html, $styles) === 1 || !empty($styles[0])) {
            $cleanedStyles = [];
            foreach ($styles[1] as $css) {
                $css = preg_replace('/(?<![a-zA-Z0-9_-])body\s*\{([^}]*)\}/i', '.site-main--tools { $1 }', $css);
                $cleanedStyles[] = '<style>' . $css . '</style>';
            }
            $headHtml = implode("\n", $cleanedStyles);
            $html = (string)preg_replace('#<style\b[^>]*>.*?</style>#is', '', $html);
        }

        if (preg_match('#<body\b[^>]*>(.*)</body>#is', $html, $body) === 1) {
            return [trim($body[1]), $headHtml];
        }

        return [trim($html), $headHtml];
    }
}

