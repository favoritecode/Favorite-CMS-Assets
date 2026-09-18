<?php
require_once __DIR__ . '/functions.php';

$cfg = function_exists('fw_get_config') ? fw_get_config() : [];

$siteTitle   = !empty($cfg['site_title']) ? $cfg['site_title'] : ($siteTitle ?? \FavoriteCMS\Models\Setting::get('general', 'site_name', 'Favorite CMS'));
$siteTagline = !empty($cfg['site_tagline']) ? $cfg['site_tagline'] : ($siteTagline ?? \FavoriteCMS\Models\Setting::get('general', 'site_description', ''));
$metaTitle   = $metaTitle ?? $siteTitle;
$metaDesc    = $metaDescription ?? \FavoriteCMS\Models\Setting::get('seo', 'meta_description', '');

$siteLogoUrl      = !empty($cfg['site_logo_url']) ? $cfg['site_logo_url'] : (function_exists('get_site_logo_url') ? get_site_logo_url() : (function_exists('get_theme_mod') ? get_theme_mod('site_logo_url') : null));
$siteFaviconUrl   = !empty($cfg['site_favicon_url']) ? $cfg['site_favicon_url'] : (function_exists('get_site_favicon_url') ? get_site_favicon_url() : (function_exists('get_theme_mod') ? get_theme_mod('site_favicon_url') : null));
$logoWidth        = !empty($cfg['logo_width']) ? (int)$cfg['logo_width'] : 0;
$headerSticky     = array_key_exists('header_sticky', $cfg) ? (bool)$cfg['header_sticky'] : true;
$showSearch       = array_key_exists('header_show_search', $cfg) ? (bool)$cfg['header_show_search'] : true;
$showStore        = array_key_exists('header_show_store', $cfg) ? (bool)$cfg['header_show_store'] : true;
$showThemeToggle  = array_key_exists('header_show_theme_toggle', $cfg) ? (bool)$cfg['header_show_theme_toggle'] : true;

$accentColor      = fw_accent_color();
$siteLayout       = fw_site_layout();
$hasSidebar       = ($fwHasSidebar ?? true) && $siteLayout !== 'none';
$currentUser      = function_exists('current_user') ? current_user() : null;
$primaryMenu      = fw_menu_items('primary', 4);
$headerWidgets    = has_region_widgets('header-right');
$walletBalance    = fw_wallet_balance();
$stylesheetUrl    = function_exists('theme_asset_url') ? theme_asset_url('assets/css/style.css') : fw_url('/themes/favorite-web/assets/css/style.css');
$homepageStyleUrl = function_exists('theme_asset_url') ? theme_asset_url('assets/css/home.css') : fw_url('/themes/favorite-web/assets/css/home.css');
$brandMarkUrl     = function_exists('theme_asset_url') ? theme_asset_url('assets/images/favorite-web-mark.svg') : fw_url('/themes/favorite-web/assets/images/favorite-web-mark.svg');

$bodyClasses = ['layout-' . $siteLayout, $hasSidebar ? 'has-sidebar' : 'no-sidebar'];
if (!$headerSticky) {
    $bodyClasses[] = 'header-static';
}
if (!empty($bodyClass) && is_string($bodyClass)) {
    $bodyClasses[] = $bodyClass;
}
?>
<!DOCTYPE html>
<html lang="<?php echo fw_e(function_exists('site_language') ? site_language() : 'en'); ?>" class="no-js">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars((string)$metaTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <?php if (!empty($metaDesc)): ?>
        <meta name="description" content="<?php echo htmlspecialchars((string)$metaDesc, ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>
    <script>
        document.documentElement.className = document.documentElement.className.replace(/\bno-js\b/, 'js');
        (function(){
            try {
                var saved = localStorage.getItem('fw_theme_pref');
                var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                if (saved === 'dark' || (!saved && prefersDark)) {
                    document.documentElement.setAttribute('data-theme', 'dark');
                } else {
                    document.documentElement.setAttribute('data-theme', 'light');
                }
            } catch (e) {}
        })();
    </script>
    <?php if (!empty($siteFaviconUrl) && is_string($siteFaviconUrl)): ?>
        <?php
        $favExt = strtolower(pathinfo(parse_url($siteFaviconUrl, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
        $favType = match ($favExt) {
            'ico'   => 'image/x-icon',
            'png'   => 'image/png',
            'svg'   => 'image/svg+xml',
            'gif'   => 'image/gif',
            'webp'  => 'image/webp',
            default => 'image/x-icon',
        };
        ?>
        <link rel="icon" type="<?php echo fw_e($favType); ?>" href="<?php echo fw_e(fw_url($siteFaviconUrl)); ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="<?php echo fw_e($stylesheetUrl); ?>">
    <link rel="stylesheet" href="<?php echo fw_e($homepageStyleUrl); ?>">
    <?php if (!empty($additionalHeadHtml) && is_string($additionalHeadHtml)): ?>
        <?php echo $additionalHeadHtml; ?>
    <?php endif; ?>
    <?php
    if (function_exists('fw_render_custom_styles')) {
        echo fw_render_custom_styles();
    } elseif ($accentColor !== '') {
        echo '<style>:root { --accent: ' . $accentColor . '; }</style>';
    }
    ?>
    <?php if (isset($_GET['fw_preview'])): ?>
    <script>
    window.addEventListener('message', function(e) {
        if (!e || e.origin !== window.location.origin) return;
        if (e.source !== window.parent) return;
        if (!e.data || typeof e.data !== 'object' || e.data.type !== 'fw_preview_update') return;

        // Validate and apply design tokens
        if (e.data.tokens && typeof e.data.tokens === 'object') {
            var validPropRegex = /^--[a-z0-9-]+$/i;
            for (var prop in e.data.tokens) {
                if (!Object.prototype.hasOwnProperty.call(e.data.tokens, prop)) continue;
                if (!validPropRegex.test(prop)) continue;
                var val = e.data.tokens[prop];
                if (typeof val === 'string' && val.length < 128 && !/[<>{};]|expression|javascript/i.test(val)) {
                    document.documentElement.style.setProperty(prop, val);
                }
            }
        }

        // Validate and apply live custom CSS
        if (typeof e.data.customCss === 'string') {
            var rawCss = e.data.customCss;
            if (rawCss.length < 65536) {
                // Strip dangerous script injection and expressions
                var cleanCss = rawCss
                    .replace(/<\/?(script|style)[^>]*>/gi, '')
                    .replace(/expression\s*\([^)]*\)/gi, '')
                    .replace(/url\s*\(\s*['"]?javascript:[^)]*['"]?\s*\)/gi, '')
                    .replace(/@import[^;]*;/gi, '');
                var el = document.getElementById('fw-custom-css-preview');
                if (!el) {
                    el = document.createElement('style');
                    el.id = 'fw-custom-css-preview';
                    document.head.appendChild(el);
                }
                el.textContent = cleanCss;
            }
        }
    });
    </script>
    <?php endif; ?>
    <?php
    // Centralized Frontend SEO, social meta, schema, verification and tracking tags
    if (class_exists(\FavoriteCMS\Services\FrontendSeoService::class)) {
        echo \FavoriteCMS\Services\FrontendSeoService::renderHeadTags(get_defined_vars());
    }
    ?>
</head>
<body class="<?php echo fw_e(implode(' ', $bodyClasses)); ?>">
<?php
// GTM noscript iframe or body tags immediately after <body> opening
if (class_exists(\FavoriteCMS\Services\FrontendSeoService::class)) {
    echo \FavoriteCMS\Services\FrontendSeoService::renderBodyTags();
}
?>
<a class="skip-link" href="#main-content">Skip to content</a>

<header class="site-header<?php echo !$headerSticky ? ' site-header--static' : ''; ?>" role="banner"<?php echo !$headerSticky ? ' style="position: static;"' : ''; ?>>
    <div class="container header-bar">
        <a href="<?php echo fw_e(fw_url('/')); ?>" class="site-branding" aria-label="<?php echo fw_e($siteTitle); ?> Homepage">
            <?php if (!empty($siteLogoUrl) && is_string($siteLogoUrl)): ?>
                <img src="<?php echo fw_e(fw_url($siteLogoUrl)); ?>" alt="<?php echo fw_e($siteTitle); ?>" class="site-custom-logo" decoding="async"<?php echo $logoWidth > 0 ? ' style="max-width:' . $logoWidth . 'px; height:auto;"' : ''; ?>>
            <?php else: ?>
                <span class="site-logo-icon" aria-hidden="true"><img src="<?php echo fw_e($brandMarkUrl); ?>" alt="" width="<?php echo $logoWidth > 0 ? $logoWidth : 38; ?>" height="<?php echo $logoWidth > 0 ? $logoWidth : 38; ?>"<?php echo $logoWidth > 0 ? ' style="width:' . $logoWidth . 'px; height:auto;"' : ''; ?>></span>
                <span class="brand-text">
                    <span class="site-title"><?php echo fw_e($siteTitle); ?></span>
                    <?php if (!empty($siteTagline)): ?>
                        <span class="site-tagline"><?php echo fw_e($siteTagline); ?></span>
                    <?php endif; ?>
                </span>
            <?php endif; ?>
        </a>

        <button type="button" class="mobile-nav-toggle" id="mobile-nav-btn" aria-controls="header-nav-wrap" aria-expanded="false" aria-label="Toggle navigation">
            <svg class="icon icon-menu" viewBox="0 0 24 24" width="22" height="22" aria-hidden="true" focusable="false"><path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            <svg class="icon icon-close" viewBox="0 0 24 24" width="22" height="22" aria-hidden="true" focusable="false"><path d="M6 6l12 12M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            <span class="visually-hidden">Menu</span>
        </button>

        <div class="header-nav-wrap" id="header-nav-wrap">
            <nav class="main-nav" aria-label="Main navigation">
                <?php fw_partial('nav-menu', [
                    'items'       => $primaryMenu['items'],
                    'menuClass'   => 'menu main-menu',
                    'prependHome' => !$primaryMenu['assigned'],
                ]); ?>
            </nav>

            <?php if ($showSearch): ?>
                <?php if ($headerWidgets): ?>
                    <div class="header-right-widgets">
                        <?php echo render_region('header-right'); ?>
                    </div>
                <?php else: ?>
                    <div class="header-search">
                        <?php fw_partial('search-form', ['inputId' => 'header-search-input', 'formClass' => 'search-form--header', 'placeholder' => 'Search…']); ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($showThemeToggle): ?>
                <button type="button" class="theme-toggle-btn" id="theme-toggle-btn" aria-label="Toggle dark mode" title="Toggle dark mode">
                    <svg class="icon icon-sun" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
                    <svg class="icon icon-moon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                </button>
            <?php endif; ?>

            <?php if ($showStore): ?>
                <a class="button button--store button--small" href="<?php echo fw_e(fw_url('/store')); ?>">Store</a>
            <?php endif; ?>

            <?php if ($currentUser): ?>
                <?php if ($walletBalance !== null): ?>
                    <a class="header-wallet-pill" href="<?php echo fw_e(fw_url('/account/wallet')); ?>" title="My Wallet Balance">
                        <svg class="icon" viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/><circle cx="16" cy="15" r="1"/></svg>
                        <span class="header-wallet-amount"><?php echo fw_e($walletBalance); ?></span>
                    </a>
                <?php endif; ?>

                <?php if (function_exists('current_user_can') && current_user_can('publish_posts')): ?>
                    <div class="header-actions">
                        <a class="button button--primary button--small" href="<?php echo fw_e(fw_url('/admin/posts/new')); ?>">+ Create Post</a>
                    </div>
                <?php endif; ?>

                <div class="header-account-wrap">
                    <?php echo function_exists('render_account_menu') ? render_account_menu() : '<a href="' . fw_e(fw_url('/admin')) . '">Account</a>'; ?>
                </div>
            <?php else: ?>
                <div class="header-actions">
                    <a class="header-link" href="<?php echo fw_e(fw_url('/admin/login')); ?>">Log In</a>
                    <?php if (fw_registration_enabled()): ?>
                        <a class="button button--primary button--small" href="<?php echo fw_e(fw_url('/register')); ?>">Sign Up</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</header>

<div class="site-content<?php echo $siteLayout === 'left' ? ' site-content-sidebar-left' : ''; ?>">
    <div class="container layout <?php echo $hasSidebar ? 'layout--with-sidebar' : 'layout--full'; ?>">
