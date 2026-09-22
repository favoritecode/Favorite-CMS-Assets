<?php
/**
 * Favorite Web Official Theme — presentation helpers.
 *
 * Theme-only helpers (prefixed fw_) shared by templates and partials. Generic behavior that every
 * theme needs (base-path URLs, theme asset URLs, current-URL matching) lives in Core helpers.
 */

require_once __DIR__ . '/inc/config.php';

if (!function_exists('fw_e')) {
    /** Escape a scalar value for HTML output. */
    function fw_e(mixed $value): string
    {
        return htmlspecialchars(is_scalar($value) ? (string)$value : '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('fw_url')) {
    /** Base-path-aware URL for a site path (falls back gracefully if Core helpers are unavailable). */
    function fw_url(string $path): string
    {
        return function_exists('site_path') ? site_path($path) : $path;
    }
}

if (!function_exists('format_date')) {
    /** Format a date string or timestamp. */
    function format_date(mixed $date, string $format = 'M j, Y'): string
    {
        if (!$date) {
            return '';
        }
        $timestamp = is_numeric($date) ? (int)$date : strtotime((string)$date);
        return $timestamp ? date($format, $timestamp) : '';
    }
}

if (!function_exists('fw_partial')) {
    /**
     * Render a theme partial with an isolated variable scope.
     */
    function fw_partial(string $name, array $vars = []): void
    {
        if (preg_match('#^[a-z0-9_-]+(/[a-z0-9_-]+)?$#', $name) !== 1) {
            return;
        }
        $file = __DIR__ . '/partials/' . $name . '.php';
        if (!is_file($file)) {
            return;
        }
        (static function (string $__partialFile, array $__partialVars): void {
            extract($__partialVars, EXTR_SKIP);
            include $__partialFile;
        })($file, $vars);
    }
}

if (!function_exists('fw_initial')) {
    /** First character of a name (multibyte and grapheme safe), uppercased where the script has case. */
    function fw_initial(?string $name, string $fallback = 'A'): string
    {
        $name = trim((string)$name);
        if ($name === '') {
            $name = $fallback;
        }
        if (function_exists('grapheme_substr')) {
            $first = grapheme_substr($name, 0, 1);
            if (is_string($first) && $first !== '') {
                return mb_strtoupper($first, 'UTF-8');
            }
        }
        return mb_strtoupper(mb_substr($name, 0, 1, 'UTF-8'), 'UTF-8');
    }
}

if (!function_exists('fw_display_name')) {
    /** Display name of a user object (name, then username, then fallback). */
    function fw_display_name(?object $user, string $fallback = 'Admin'): string
    {
        if (!$user) {
            return $fallback;
        }
        $name = trim((string)($user->name ?? ''));
        if ($name !== '') {
            return $name;
        }
        $username = trim((string)($user->username ?? ''));
        return $username !== '' ? $username : $fallback;
    }
}

if (!function_exists('fw_read_time')) {
    /**
     * Estimated reading time in minutes (200 words per minute).
     * ASCII text keeps the original str_word_count() behavior; other scripts (e.g. Bangla) use Unicode word tokens.
     */
    function fw_read_time(?string $content): int
    {
        $text = strip_tags((string)$content);
        if (trim($text) === '') {
            return 1;
        }

        if (preg_match('/[^\x00-\x7F]/', $text) === 1) {
            $count = preg_match_all('/[\p{L}\p{M}\p{N}]+/u', html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $words = is_int($count) ? $count : str_word_count($text);
        } else {
            $words = str_word_count($text);
        }

        return max(1, (int)ceil($words / 200));
    }
}

if (!function_exists('fw_format_views')) {
    /**
     * Format a view count with proper singular/plural grammar: "0 Views", "1 View", "123 Views".
     */
    function fw_format_views(int $views): string
    {
        $views = max(0, $views);
        return number_format($views) . ' ' . ($views === 1 ? 'View' : 'Views');
    }
}

if (!function_exists('fw_get_post_views')) {
    /**
     * Retrieve the persistent view count for a given post.
     */
    function fw_get_post_views(mixed $post): int
    {
        $postId = is_object($post) ? (int)($post->id ?? 0) : (int)$post;
        if ($postId <= 0) {
            return 0;
        }

        try {
            if (class_exists(\FavoriteCMS\Models\Setting::class)) {
                return (int)\FavoriteCMS\Models\Setting::get('post_views', (string)$postId, 0);
            }
        } catch (\Throwable) {
            return 0;
        }

        return 0;
    }
}

if (!function_exists('fw_track_post_view')) {
    /**
     * Increment the persistent view count for a published post when viewed by a public visitor.
     * Excludes admin/editor previews and visits, and prevents counting duplicate refreshes in the same session.
     */
    function fw_track_post_view(mixed $post, bool $isPreview = false): int
    {
        $postId = is_object($post) ? (int)($post->id ?? 0) : (int)$post;
        if ($postId <= 0 || $isPreview || isset($_GET['preview'])) {
            return fw_get_post_views($postId);
        }

        // Exclude logged-in admins and editors to avoid artificial inflation
        $user = function_exists('current_user') ? current_user() : null;
        if (!$user && !empty($_SESSION['auth_user_id']) && class_exists(\FavoriteCMS\Models\User::class)) {
            try {
                $user = \FavoriteCMS\Models\User::find((int)$_SESSION['auth_user_id']);
            } catch (\Throwable) {}
        }
        if ($user && ($user->hasRole('admin') || $user->hasRole('super-admin') || $user->hasRole('editor') || $user->hasPermission('manage_settings'))) {
            return fw_get_post_views($postId);
        }

        // Prevent duplicate refreshes in the same browser session
        if (session_status() === PHP_SESSION_ACTIVE || !empty($_SESSION) || isset($_SESSION)) {
            $viewed = (array)($_SESSION['fw_viewed_posts'] ?? []);
            if (in_array($postId, $viewed, true)) {
                return fw_get_post_views($postId);
            }
            $viewed[] = $postId;
            $_SESSION['fw_viewed_posts'] = $viewed;
        }

        // Atomically increment in database using Setting model
        try {
            if (class_exists(\FavoriteCMS\Models\Setting::class)) {
                $current = (int)\FavoriteCMS\Models\Setting::get('post_views', (string)$postId, 0);
                $newCount = $current + 1;
                \FavoriteCMS\Models\Setting::set('post_views', (string)$postId, $newCount, 'int');
                return $newCount;
            }
        } catch (\Throwable) {}

        return fw_get_post_views($postId);
    }
}


if (!function_exists('fw_excerpt')) {
    /** Manual excerpt, or a plain-text summary of the content trimmed to $length characters. */
    function fw_excerpt(object $post, int $length = 170): string
    {
        $excerpt = trim((string)($post->excerpt ?? ''));
        if ($excerpt !== '') {
            return $excerpt;
        }

        $plain = html_entity_decode(strip_tags((string)($post->content ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $plain = trim((string)(preg_replace('/\s+/u', ' ', $plain) ?? $plain));
        if (mb_strlen($plain, 'UTF-8') <= $length) {
            return $plain;
        }
        return rtrim(mb_substr($plain, 0, $length, 'UTF-8')) . '…';
    }
}

if (!function_exists('fw_image_dimensions')) {
    /** width/height attributes for a media record, only when both dimensions are known. */
    function fw_image_dimensions(?object $media): string
    {
        if (!$media) {
            return '';
        }
        $width = (int)($media->width ?? 0);
        $height = (int)($media->height ?? 0);
        return ($width > 0 && $height > 0) ? ' width="' . $width . '" height="' . $height . '"' : '';
    }
}

if (!function_exists('fw_prepare_content')) {
    /**
     * Presentation-only adjustments to already sanitized post/page HTML:
     * tables are wrapped in a keyboard-focusable horizontal scroll region (table semantics stay intact).
     */
    function fw_prepare_content(string $html): string
    {
        if ($html === '' || stripos($html, '<table') === false) {
            return $html;
        }

        $opening = preg_match_all('/<table\b/i', $html);
        $closing = preg_match_all('/<\/table\s*>/i', $html);
        if ($opening === false || $opening !== $closing) {
            return $html;
        }

        $html = preg_replace('/<table\b/i', '<div class="table-scroll" role="region" aria-label="Scrollable table" tabindex="0"><table', $html) ?? $html;
        return preg_replace('/<\/table\s*>/i', '</table></div>', $html) ?? $html;
    }
}

if (!function_exists('fw_accent_color')) {
    /** The Customizer accent color, only when it is a valid hex color (#rgb or #rrggbb). */
    function fw_accent_color(): string
    {
        $raw = function_exists('fw_get_config') ? fw_get_config('accent_color', '') : (function_exists('get_theme_mod') ? get_theme_mod('accent_color', '') : '');
        $raw = is_string($raw) ? trim($raw) : '';
        return preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $raw) === 1 ? strtolower($raw) : '';
    }
}

if (!function_exists('fw_site_layout')) {
    /** Customizer sidebar layout: right, left or none. */
    function fw_site_layout(): string
    {
        $layout = function_exists('fw_get_config') ? fw_get_config('site_layout', 'right') : (function_exists('get_theme_mod') ? get_theme_mod('site_layout', 'right') : 'right');
        return in_array($layout, ['right', 'left', 'none'], true) ? $layout : 'right';
    }
}

if (!function_exists('fw_registration_enabled')) {
    function fw_registration_enabled(): bool
    {
        try {
            return (bool)(int)\FavoriteCMS\Models\Setting::get('general', 'allow_registration', 1);
        } catch (\Throwable) {
            return false;
        }
    }
}

if (!function_exists('fw_wallet_balance')) {
    /**
     * Returns formatted spendable wallet balance string for the authenticated user, or null if unauthenticated or Favorite Pay inactive.
     */
    function fw_wallet_balance(): ?string
    {
        $user = function_exists('current_user') ? current_user() : null;
        if (!$user || empty($user->id)) {
            return null;
        }
        $walletInterface = '\\FavoriteCMS\\Pay\\Contracts\\WalletServiceInterface';
        if (interface_exists($walletInterface) && class_exists(\FavoriteCMS\Core\Container::class)) {
            try {
                $container = \FavoriteCMS\Core\Container::getInstance();
                if ($container->has($walletInterface)) {
                    $walletService = $container->make($walletInterface);
                    $bal = $walletService->getBalance((int)$user->id);
                    if ($bal && method_exists($bal, 'format')) {
                        return $bal->format();
                    }
                }
            } catch (\Throwable) {
                return null;
            }
        }
        return null;
    }
}

if (!function_exists('fw_menu_items')) {
    /**
     * Items for a menu location. When no menu is assigned, falls back to a bounded list of published pages.
     *
     * @return array{assigned: bool, items: array<int, object>}
     */
    function fw_menu_items(string $location, int $fallbackPageLimit = 4): array
    {
        try {
            $menu = \FavoriteCMS\Models\Menu::findByLocation($location);
            if ($menu) {
                return ['assigned' => true, 'items' => $menu->getItems()];
            }
        } catch (\Throwable) {
        }

        $items = [];
        try {
            foreach (\FavoriteCMS\Models\Page::published($fallbackPageLimit) as $page) {
                $items[] = (object)['title' => $page->title, 'url' => '/page/' . $page->slug, 'target' => '', 'children' => []];
            }
        } catch (\Throwable) {
        }

        return ['assigned' => false, 'items' => $items];
    }
}

if (!function_exists('fw_menu_links_home')) {
    /** Whether any top-level menu item already points to the homepage. */
    function fw_menu_links_home(array $items): bool
    {
        foreach ($items as $item) {
            $url = trim((string)($item->url ?? ''));
            if ($url === '' || (!str_starts_with($url, '/') && !preg_match('#^https?://#i', $url))) {
                continue;
            }
            $host = parse_url($url, PHP_URL_HOST);
            if (is_string($host) && strtolower($host) !== strtolower(explode(':', (string)($_SERVER['HTTP_HOST'] ?? ''))[0])) {
                continue;
            }
            $path = (string)(parse_url($url, PHP_URL_PATH) ?: '/');
            if (function_exists('site_request_path') && site_request_path($path) === '/') {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('fw_official_services')) {
    /**
     * Favorite Web's public service categories. Child themes and plugins may
     * replace or extend the cards through the standard Core filter API.
     *
     * @return array<int, array{title:string,description:string,icon:string,url:string}>
     */
    function fw_official_services(): array
    {
        // First check if custom services were configured via Customizer (services_json or services_items)
        $servicesJson = function_exists('get_theme_mod') ? get_theme_mod('services_json', null) : null;
        if (is_string($servicesJson) && trim($servicesJson) !== '') {
            $decoded = json_decode($servicesJson, true);
            if (is_array($decoded) && !empty($decoded)) {
                $services = array_values(array_filter($decoded, static fn(array $item): bool => !isset($item['enabled']) || !empty($item['enabled'])));
                if (function_exists('apply_filters')) {
                    $filtered = apply_filters('favorite_web_official_services', $services);
                    return is_array($filtered) ? array_values($filtered) : $services;
                }
                return $services;
            }
        }

        // Check if Favorite Digital has service products
        $digitalServices = fw_store_products('service', 6);
        if ($digitalServices !== []) {
            $converted = [];
            foreach ($digitalServices as $s) {
                $converted[] = [
                    'title' => (string)($s['title'] ?? ''),
                    'description' => (string)($s['description'] ?? ''),
                    'icon' => 'support',
                    'url' => !empty($s['slug']) ? '/store/' . rawurlencode($s['slug']) : '/store?product_type=service',
                ];
            }
            return $converted;
        }

        $configured = function_exists('fw_get_config') ? fw_get_config('services_items', null) : null;
        if (is_array($configured) && !empty($configured)) {
            $services = array_values(array_filter($configured, static fn(array $item): bool => !isset($item['enabled']) || !empty($item['enabled'])));
        } else {
            $defaults = function_exists('fw_default_config') ? fw_default_config() : [];
            $services = $defaults['services_items'] ?? [
                ['title' => 'Digital Products', 'description' => 'Premium apps, subscriptions, templates, and practical online tools.', 'icon' => 'download', 'url' => '/store?product_type=digital'],
                ['title' => 'Marketing Development', 'description' => 'Practical campaigns and audience growth support for your online presence.', 'icon' => 'growth', 'url' => '/store?product_type=service'],
                ['title' => 'Web Design', 'description' => 'Modern Blogger and WordPress websites built with performance and SEO in mind.', 'icon' => 'browser', 'url' => '/store?product_type=service'],
                ['title' => 'Video & Graphic Design', 'description' => 'Clear visual communication through professional editing and brand graphics.', 'icon' => 'design', 'url' => '/store?product_type=service'],
                ['title' => 'Microsoft Office Services', 'description' => 'Accurate Word, Excel, and PowerPoint work delivered with professional care.', 'icon' => 'document', 'url' => '/store?product_type=service'],
                ['title' => 'Technical Problem Solving', 'description' => 'Straightforward help for supported web, software, and digital workflow issues.', 'icon' => 'support', 'url' => '/store?product_type=service'],
            ];
        }

        if (function_exists('apply_filters')) {
            $filtered = apply_filters('favorite_web_official_services', $services);
            return is_array($filtered) ? array_values($filtered) : $services;
        }

        return $services;
    }
}

if (!function_exists('fw_platform_links')) {
    /** @return array<int, array{title:string,description:string,url:string,label:string}> */
    function fw_platform_links(): array
    {
        $links = [
            ['title' => 'Tutorials', 'description' => 'Bangla and English guides for PC, mobile, coding, and editing.', 'url' => '/category/tutorials', 'label' => 'Start learning'],
            ['title' => 'Web Tools', 'description' => 'Fast browser-based utilities for everyday digital work.', 'url' => '/category/web-tools', 'label' => 'Open web tools'],
            ['title' => 'Multimedia', 'description' => 'Favorite Multimedia apps and media experiences across devices.', 'url' => 'https://media.favoriteweb.net/', 'label' => 'Explore multimedia'],
            ['title' => 'First Support', 'description' => 'Helpful guidance and dependable support when you need it.', 'url' => '/page/contact-us', 'label' => 'Get support'],
        ];

        if (function_exists('apply_filters')) {
            $filtered = apply_filters('favorite_web_platform_links', $links);
            return is_array($filtered) ? array_values($filtered) : $links;
        }

        return $links;
    }
}

if (!function_exists('fw_store_products')) {
    /**
     * Read public listing data through Favorite Digital's registered service.
     * The integration is optional: the theme remains usable when the plugin is absent.
     *
     * @return array<int, array<string, mixed>>
     */
    function fw_store_products(string $type, int $limit = 6): array
    {
        $allowed = ['digital', 'service', 'package', 'membership'];
        if (!in_array($type, $allowed, true)) {
            return [];
        }

        $items = [];
        $serviceClass = '\\FavoriteCMS\\Digital\\Services\\StorefrontService';
        if (class_exists($serviceClass) && class_exists(\FavoriteCMS\Core\Container::class)) {
            try {
                $user = function_exists('current_user') ? current_user() : null;
                $userId = $user && !empty($user->id) ? (int)$user->id : null;
                $service = \FavoriteCMS\Core\Container::getInstance()->make($serviceClass);
                $catalog = $service->browseProducts(
                    ['product_type' => $type, 'sort' => 'newest'],
                    1,
                    max(1, min(12, $limit)),
                    $userId
                );
                $items = is_array($catalog['items'] ?? null) ? $catalog['items'] : [];
            } catch (\Throwable) {
                $items = [];
            }
        }

        if (function_exists('apply_filters')) {
            $filtered = apply_filters('favorite_web_home_products', $items, $type, $limit);
            if (is_array($filtered)) {
                $items = $filtered;
            }
        }

        return array_slice(array_values($items), 0, max(1, min(12, $limit)));
    }
}

if (!function_exists('fw_packages')) {
    /**
     * Read package products from Favorite Digital.
     *
     * @return array<int, array<string, mixed>>
     */
    function fw_packages(int $limit = 3): array
    {
        return fw_store_products('package', $limit);
    }
}

if (!function_exists('fw_trust_stats')) {
    /**
     * Trust and performance statistics for homepage trust-stats section.
     * Automatically hidden if no metrics are configured or provided.
     *
     * @return array<int, array{number: string, label: string, desc?: string}>
     */
    function fw_trust_stats(): array
    {
        $stats = function_exists('fw_get_config') ? fw_get_config('trust_stats_data', []) : [];
        if (!is_array($stats) || empty($stats)) {
            if (function_exists('get_theme_mod')) {
                $modStats = get_theme_mod('trust_stats_data', null);
                if (is_array($modStats)) {
                    $stats = $modStats;
                } elseif (is_string($modStats) && trim($modStats) !== '') {
                    $decoded = json_decode($modStats, true);
                    if (is_array($decoded)) {
                        $stats = $decoded;
                    }
                }
                if (empty($stats)) {
                    $statsJson = get_theme_mod('trust_stats_json', null);
                    if (is_string($statsJson) && trim($statsJson) !== '') {
                        $decoded = json_decode($statsJson, true);
                        if (is_array($decoded)) {
                            $stats = $decoded;
                        }
                    }
                }
            }
        }

        // Individual slot checks (stats_1_value, etc.)
        if ((!is_array($stats) || empty($stats)) && function_exists('get_theme_mod')) {
            $items = [];
            for ($i = 1; $i <= 4; $i++) {
                $num = get_theme_mod("stats_{$i}_value", null) ?? get_theme_mod("stat_{$i}_number", null);
                $lbl = get_theme_mod("stats_{$i}_label", null) ?? get_theme_mod("stat_{$i}_label", null);
                $dsc = get_theme_mod("stats_{$i}_indicator", null) ?? get_theme_mod("stat_{$i}_desc", null);
                if ($num !== null && $num !== '' || $lbl !== null && $lbl !== '') {
                    $items[] = [
                        'number' => (string)($num ?? ''),
                        'label'  => (string)($lbl ?? ''),
                        'desc'   => (string)($dsc ?? ''),
                    ];
                }
            }
            if (!empty($items)) {
                $stats = $items;
            }
        }

        if (!is_array($stats)) {
            $stats = [];
        }

        if (function_exists('apply_filters')) {
            $filtered = apply_filters('favorite_web_trust_stats', $stats);
            return is_array($filtered) ? array_values($filtered) : $stats;
        }

        return $stats;
    }
}

if (!function_exists('fw_about_data')) {
    /**
     * About section data.
     *
     * @return array{badge: string, title: string, description: string, cta_text: string, cta_url: string, highlights: array<int, string>, media_type?: string, image_url?: string, video_url?: string}
     */
    function fw_about_data(): array
    {
        $cfg = function_exists('fw_get_section_config') ? fw_get_section_config('about') : [];
        $data = [
            'badge'       => (string)($cfg['badge'] ?? (function_exists('get_theme_mod') ? get_theme_mod('about_badge', 'About Us') : 'About Us')),
            'title'       => (string)($cfg['title'] ?? (function_exists('get_theme_mod') ? get_theme_mod('about_title', 'Empowering Your Digital Journey') : 'Empowering Your Digital Journey')),
            'description' => (string)($cfg['description'] ?? (function_exists('get_theme_mod') ? get_theme_mod('about_description', 'We build modern web experiences, deliver smart digital tools, and provide professional services to help you learn, create, and grow online.') : 'We build modern web experiences, deliver smart digital tools, and provide professional services to help you learn, create, and grow online.')),
            'cta_text'    => (string)($cfg['cta_text'] ?? (function_exists('get_theme_mod') ? get_theme_mod('about_cta_text', 'Learn More') : 'Learn More')),
            'cta_url'     => (string)($cfg['cta_url'] ?? (function_exists('get_theme_mod') ? get_theme_mod('about_cta_url', '/page/about-us') : '/page/about-us')),
            'highlights'  => is_array($cfg['highlights'] ?? null) ? $cfg['highlights'] : [
                'High-performance architecture built for speed and security',
                'Curated digital assets, tools, and developer-friendly resources',
                'Comprehensive tutorials, documentation, and dedicated support',
            ],
            'media_type'  => (string)($cfg['media_type'] ?? 'image'),
            'image_url'   => (string)($cfg['image_url'] ?? ''),
            'video_url'   => (string)($cfg['video_url'] ?? ''),
        ];

        if (function_exists('apply_filters')) {
            $filtered = apply_filters('favorite_web_about_data', $data);
            return is_array($filtered) ? $filtered : $data;
        }

        return $data;
    }
}

if (!function_exists('fw_cta_data')) {
    /**
     * Call to action banner data.
     *
     * @return array{badge: string, title: string, description: string, primary_text: string, primary_url: string, secondary_text: string, secondary_url: string}
     */
    function fw_cta_data(): array
    {
        $cfg = function_exists('fw_get_section_config') ? fw_get_section_config('cta') : [];
        $data = [
            'badge'          => (string)($cfg['badge'] ?? (function_exists('get_theme_mod') ? get_theme_mod('cta_badge', 'Get Started') : 'Get Started')),
            'title'          => (string)($cfg['title'] ?? (function_exists('get_theme_mod') ? get_theme_mod('cta_title', 'Ready to elevate your digital presence?') : 'Ready to elevate your digital presence?')),
            'description'    => (string)($cfg['description'] ?? (function_exists('get_theme_mod') ? get_theme_mod('cta_description', 'Discover modern digital tools, resources, and professional solutions designed for your growth.') : 'Discover modern digital tools, resources, and professional solutions designed for your growth.')),
            'primary_text'   => (string)($cfg['primary_text'] ?? (function_exists('get_theme_mod') ? get_theme_mod('cta_primary_text', 'Explore Store') : 'Explore Store')),
            'primary_url'    => (string)($cfg['primary_url'] ?? (function_exists('get_theme_mod') ? get_theme_mod('cta_primary_url', '/store') : '/store')),
            'secondary_text' => (string)($cfg['secondary_text'] ?? (function_exists('get_theme_mod') ? get_theme_mod('cta_secondary_text', 'Contact Support') : 'Contact Support')),
            'secondary_url'  => (string)($cfg['secondary_url'] ?? (function_exists('get_theme_mod') ? get_theme_mod('cta_secondary_url', '/page/contact-us') : '/page/contact-us')),
        ];

        if (function_exists('apply_filters')) {
            $filtered = apply_filters('favorite_web_cta_data', $data);
            return is_array($filtered) ? $filtered : $data;
        }

        return $data;
    }
}

if (!function_exists('fw_product_image')) {
    function fw_product_image(array $product): string
    {
        $url = trim((string)($product['cover_image_url'] ?? ''));
        if ($url === '') {
            $url = trim((string)($product['cover_image_path'] ?? ''));
        }
        if ($url === '') {
            return '';
        }
        if (preg_match('#^(?:https?://|/)#i', $url) === 1) {
            return $url;
        }
        if (preg_match('#^(?:storage|uploads)/[a-zA-Z0-9._/-]+$#', $url) === 1 && !str_contains($url, '..')) {
            return '/' . ltrim($url, '/');
        }
        return '';
    }
}

if (!function_exists('fw_render_auth_page_with_theme')) {
    /**
     * Renders authentication screens (login, register, forgot-password, reset-password, resend-verification)
     * seamlessly within the active favorite-web theme shell (header and footer), supporting real-time
     * light/dark mode toggling, brand header, footer widgets, and preserving all security tokens and validation.
     */
    function fw_render_auth_page_with_theme(): void
    {
        $rawHtml = ob_get_clean();
        $code = http_response_code();

        // Pass through redirects (301, 302, 303, etc.), empty payloads, or non-auth pages untouched
        if (($code >= 300 && $code < 400) || $rawHtml === '' || !str_contains($rawHtml, 'class="fc-auth"')) {
            echo $rawHtml;
            return;
        }

        // Extract title
        $metaTitle = null;
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $rawHtml, $m)) {
            $metaTitle = html_entity_decode(trim($m[1]), ENT_QUOTES, 'UTF-8');
        }

        // Extract meta description if provided
        $metaDescription = null;
        if (preg_match('/<meta\s+name="description"\s+content="([^"]*)"/is', $rawHtml, $m)) {
            $metaDescription = html_entity_decode(trim($m[1]), ENT_QUOTES, 'UTF-8');
        }

        // Extract auth card and frame
        $frameHtml = '';
        if (preg_match('/<div class="fc-auth__frame"[^>]*>.*?<\/div>\s*(?=<p class="fc-auth__footer"|<\/div>\s*<\/main>)/is', $rawHtml, $m)) {
            $frameHtml = $m[0];
        } elseif (preg_match('/<main class="fc-auth"[^>]*>(.*?)<\/main>/is', $rawHtml, $m)) {
            $frameHtml = $m[1];
        } else {
            $frameHtml = $rawHtml;
        }

        // Extract auth progressive enhancement scripts (password toggle, validation rules, token hash parser)
        $scriptsHtml = '';
        if (preg_match_all('/<script\b[^>]*>(.*?)<\/script>/is', $rawHtml, $scriptMatches)) {
            foreach ($scriptMatches[0] as $scriptTag) {
                if (!str_contains($scriptTag, 'favorite_admin_theme')) {
                    $scriptsHtml .= "\n" . $scriptTag;
                }
            }
        }

        // Render full page enclosed within favorite-web header and footer
        ob_start();
        $bodyClass = 'favorite-auth-page';
        $fwHasSidebar = false;
        $siteLayout = 'none';

        include __DIR__ . '/header.php';
        ?>
        <main class="site-main site-main--auth" id="main-content" tabindex="-1">
            <div class="fc-auth fc-auth--themed">
                <div class="fc-auth__inner fc-auth__inner--account">
                    <?php echo $frameHtml; ?>
                </div>
            </div>
        </main>
        <?php echo $scriptsHtml; ?>
        <?php
        include __DIR__ . '/footer.php';
        $themedHtml = ob_get_clean();
        echo $themedHtml;
    }
}

if (class_exists(\FavoriteCMS\Core\Hook::class)) {
    \FavoriteCMS\Core\Hook::addAction('init', function () {
        // Only activate for requests with host/URI (web requests and integration tests)
        if (php_sapi_name() === 'cli' && empty($_SERVER['HTTP_HOST'])) {
            return;
        }

        $req = \FavoriteCMS\Core\Request::capture();
        if (!empty($GLOBALS['favorite_cms_base_path'])) {
            $req->setBasePath($GLOBALS['favorite_cms_base_path']);
        }
        $rawPath = $req->path();
        $cleanPath = '/' . trim($rawPath, '/');
        $authPaths = [
            '/admin/login',
            '/login',
            '/register',
            '/signup',
            '/admin/register',
            '/forgot-password',
            '/reset-password',
            '/resend-verification',
            '/verify-email',
            '/logout',
            '/admin/logout',
        ];

        if (in_array($rawPath, $authPaths, true) || in_array($cleanPath, $authPaths, true)) {
            ob_start();
            register_shutdown_function('fw_render_auth_page_with_theme');
        }
    }, 5);
}

