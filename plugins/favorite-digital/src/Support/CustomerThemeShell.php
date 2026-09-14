<?php

declare(strict_types=1);

namespace FavoriteCMS\Digital\Support;

use FavoriteCMS\Models\Setting;
use Throwable;

/**
 * Places trusted customer-facing plugin views inside an active theme shell
 * when that theme explicitly declares support in its manifest.
 */
final class CustomerThemeShell
{
    public static function render(string $viewPath, array $data = [], string $viewName = ''): string
    {
        if (!is_file($viewPath)) {
            return "<div class='notice notice-error'>View not found.</div>";
        }

        $viewHtml = self::renderFile($viewPath, $data);
        $theme = self::supportedTheme();
        if ($theme === null) {
            return $viewHtml;
        }

        [$content, $headHtml] = self::splitDocument($viewHtml);
        $siteName = 'Favorite CMS';
        try {
            $siteName = (string)Setting::get('general', 'site_name', $siteName);
        } catch (Throwable) {
        }

        $title = self::viewTitle($viewName);
        $metaTitle = $title . ' — ' . $siteName;
        $metaDescription = '';
        $fwHasSidebar = false;
        $fcdHasSidebar = false;
        $bodyClass = 'favorite-customer-shell favorite-digital-customer-page';
        $additionalHeadHtml = $headHtml;

        ob_start();
        include $theme['header'];
        echo '<main class="site-main site-main--customer" id="main-content" tabindex="-1">';
        echo $content;
        echo '</main>';
        include $theme['footer'];
        return (string)ob_get_clean();
    }

    private static function renderFile(string $viewPath, array $data): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        include $viewPath;
        return (string)ob_get_clean();
    }

    /** @return array{header:string,footer:string}|null */
    private static function supportedTheme(): ?array
    {
        try {
            $themeId = (string)Setting::get('theme', 'active_theme', 'default');
        } catch (Throwable) {
            return null;
        }
        if (preg_match('/^[a-zA-Z0-9_-]+$/', $themeId) !== 1) {
            return null;
        }

        $themeDir = APP_ROOT . '/themes/' . $themeId;
        $manifestPath = $themeDir . '/theme.json';
        $header = $themeDir . '/header.php';
        $footer = $themeDir . '/footer.php';
        if (!is_file($manifestPath) || !is_file($header) || !is_file($footer)) {
            return null;
        }

        $manifest = json_decode((string)file_get_contents($manifestPath), true);
        if (!is_array($manifest) || empty($manifest['features']['customer_shell'])) {
            return null;
        }

        return ['header' => $header, 'footer' => $footer];
    }

    /** @return array{0:string,1:string} */
    private static function splitDocument(string $html): array
    {
        if (stripos($html, '<html') === false) {
            return [$html, ''];
        }

        $headHtml = '';
        if (preg_match_all('#<style\b[^>]*>.*?</style>#is', $html, $styles) === 1 || !empty($styles[0])) {
            $headHtml = implode("\n", $styles[0]);
        }

        if (preg_match('#<body\b[^>]*>(.*)</body>#is', $html, $body) === 1) {
            return [trim($body[1]), $headHtml];
        }

        return [$html, $headHtml];
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
