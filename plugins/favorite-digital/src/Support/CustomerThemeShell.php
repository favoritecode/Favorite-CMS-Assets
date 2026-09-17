<?php

declare(strict_types=1);

namespace FavoriteCMS\Digital\Support;

use FavoriteCMS\Models\Setting;
use Throwable;

/**
 * Places trusted customer-facing plugin views inside the active CMS theme shell
 * dynamically resolved from Core CMS settings.
 */
final class CustomerThemeShell
{
    public static function render(string $viewPath, array $data = [], string $viewName = ''): string
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

        $title = self::viewTitle($viewName);
        $metaTitle = $title . ' — ' . $siteName;
        $metaDescription = '';
        $fwHasSidebar = false;
        $fcdHasSidebar = false;
        $bodyClass = 'favorite-customer-shell favorite-digital-customer-page';
        $additionalHeadHtml = $headHtml;

        $startObLevel = ob_get_level();
        ob_start();
        try {
            include $theme['header'];
            echo '<main class="site-main site-main--customer" id="main-content" tabindex="-1">';
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
     * @return array{header:string,footer:string,theme_id:string,has_customer_shell:bool}|null
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
        $rootDirs = array_unique([
            rtrim($appRoot, '/\\'),
            dirname(__DIR__, 4),
            'E:/Favorite-CMS-Universal',
            'E:/Favorite-CMS-Assets',
        ]);

        foreach ($themeIdsToTry as $tid) {
            foreach ($rootDirs as $root) {
                $themeDir = $root . '/themes/' . $tid;
                $header = $themeDir . '/header.php';
                $footer = $themeDir . '/footer.php';
                if (is_dir($themeDir) && is_file($header) && is_file($footer)) {
                    $manifestPath = $themeDir . '/theme.json';
                    $hasCustomerShell = false;
                    if (is_file($manifestPath)) {
                        $manifest = json_decode((string)file_get_contents($manifestPath), true);
                        $hasCustomerShell = is_array($manifest) && !empty($manifest['features']['customer_shell']);
                    } else {
                        $hasCustomerShell = true;
                    }
                    return [
                        'header'             => $header,
                        'footer'             => $footer,
                        'theme_id'           => $tid,
                        'has_customer_shell' => $hasCustomerShell,
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Backward compatibility alias for existing callers/tests.
     *
     * @return array{header:string,footer:string,theme_id:string}|null
     */
    public static function supportedTheme(): ?array
    {
        $theme = self::resolveActiveTheme();
        if ($theme === null) {
            return null;
        }
        return [
            'header'   => $theme['header'],
            'footer'   => $theme['footer'],
            'theme_id' => $theme['theme_id'],
        ];
    }

    /** @return array{0:string,1:string} */
    private static function splitDocument(string $html): array
    {
        $headHtml = '';
        if (preg_match_all('#<style\b[^>]*>(.*?)</style>#is', $html, $styles) === 1 || !empty($styles[0])) {
            $cleanedStyles = [];
            foreach ($styles[1] as $css) {
                // Scope body styles so they do not override global theme styling or dark mode
                $css = preg_replace('/(?<![a-zA-Z0-9_-])body\s*\{([^}]*)\}/i', '.site-main--customer { $1 }', $css);
                $css = preg_replace('/background:\s*#f8fafc;?/i', '', $css);
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

    private static function viewTitle(string $viewName): string
    {
        return match ($viewName) {
            'store/index' => 'Digital Store',
            'store/show' => 'Product Details',
            'checkout/index' => 'Checkout',
            'orders/index' => 'My Orders',
            'orders/view' => 'Order Details',
            'downloads/index' => 'My Downloads',
            'account/library' => 'Digital Library',
            'account/membership' => 'My Membership',
            'account/refunds' => 'My Refunds',
            'wallet/index' => 'My Wallet',
            'wallet/manual' => 'Payment Instructions',
            default => 'Customer Account',
        };
    }
}
