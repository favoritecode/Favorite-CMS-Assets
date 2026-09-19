<?php
/**
 * Favorite Web Official Theme — Configuration Engine.
 *
 * Provides authoritative default settings (reproducing the original design 1:1),
 * theme modification access, server-side validation/sanitization, and dynamic token rendering.
 */

declare(strict_types=1);

if (!function_exists('fw_default_config')) {
    /**
     * Single authoritative dictionary of Favorite Web theme defaults.
     * With ZERO saved theme mods, this exact configuration is used.
     *
     * @return array<string, mixed>
     */
    function fw_default_config(): array
    {
        static $defaults = null;
        if ($defaults !== null) {
            return $defaults;
        }

        $defaults = [
            // Site & Header
            'site_logo_url'            => '',
            'site_favicon_url'         => '',
            'logo_width'               => 38,
            'header_height'            => 64,
            'header_sticky'            => false,
            'header_show_search'       => true,
            'header_show_store'        => true,
            'header_show_theme_toggle' => true,
            'site_layout'              => 'right',

            // Hero Section
            'hero_eyebrow'             => 'Official Digital Platform',
            'hero_title'               => 'Learn, create, and grow your digital presence.',
            'hero_lead'                => 'Curated tutorials, smart web tools, trusted digital products, and professional online services.',
            'hero_primary_text'        => 'Explore Digital Store',
            'hero_primary_url'         => '/store',
            'hero_secondary_text'      => 'View Our Services',
            'hero_secondary_url'       => '#favorite-web-services',
            'hero_media_type'          => 'image', // 'image', 'video', 'none'
            'hero_image_url'           => '',      // empty = default SVG
            'hero_image_alt'           => '',
            'hero_image_link'          => '',
            'hero_image_target'        => '_self',
            'hero_image_fit'           => 'contain',
            'hero_video_type'          => 'mp4',   // 'mp4', 'youtube', 'vimeo'
            'hero_video_url'           => '',
            'hero_video_poster'        => '',
            'hero_video_autoplay'      => false,
            'hero_video_muted'         => true,
            'hero_video_loop'          => true,
            'hero_video_controls'      => true,
            'hero_trust_items'         => [
                ['title' => 'Verified', 'desc' => 'Curated quality'],
                ['title' => 'Reliable', 'desc' => 'Professional support'],
                ['title' => 'Instant',  'desc' => 'Digital delivery'],
            ],

            // Trust & Performance Stats
            'trust_stats_data'         => [],

            // About Section
            'about_badge'              => 'About Us',
            'about_title'              => 'Empowering Your Digital Journey',
            'about_description'        => 'We build modern web experiences, deliver smart digital tools, and provide professional services to help you learn, create, and grow online.',
            'about_highlights'         => [
                'High-performance architecture built for speed and security',
                'Curated digital assets, tools, and developer-friendly resources',
                'Comprehensive tutorials, documentation, and dedicated support',
            ],
            'about_cta_text'           => 'Learn More',
            'about_cta_url'            => '/page/about-us',
            'about_media_type'         => 'image',
            'about_image_url'          => '',
            'about_image_alt'          => 'Digital Products & Services',
            'about_video_url'          => '',
            'about_video_type'         => 'mp4',

            // Services Section
            'services_badge'           => 'Professional expertise',
            'services_title'           => 'Services built around your goals',
            'services_desc'            => 'From launching a website to improving daily digital work, we provide focused help with clear outcomes.',
            'services_items'           => [
                ['title' => 'Digital Products', 'description' => 'Premium apps, subscriptions, templates, and practical online tools.', 'icon' => 'download', 'url' => '/store?product_type=digital', 'enabled' => true],
                ['title' => 'Marketing Development', 'description' => 'Practical campaigns and audience growth support for your online presence.', 'icon' => 'growth', 'url' => '/store?product_type=service', 'enabled' => true],
                ['title' => 'Web Design', 'description' => 'Modern Blogger and WordPress websites built with performance and SEO in mind.', 'icon' => 'browser', 'url' => '/store?product_type=service', 'enabled' => true],
                ['title' => 'Video & Graphic Design', 'description' => 'Clear visual communication through professional editing and brand graphics.', 'icon' => 'design', 'url' => '/store?product_type=service', 'enabled' => true],
                ['title' => 'Microsoft Office Services', 'description' => 'Accurate Word, Excel, and PowerPoint work delivered with professional care.', 'icon' => 'document', 'url' => '/store?product_type=service', 'enabled' => true],
                ['title' => 'Technical Problem Solving', 'description' => 'Straightforward help for supported web, software, and digital workflow issues.', 'icon' => 'support', 'url' => '/store?product_type=service', 'enabled' => true],
            ],

            // Digital Products Section
            'products_badge'           => 'Ready when you are',
            'products_title'           => 'Digital products for smarter work',
            'products_action_text'     => 'Browse all products',
            'products_action_url'      => '/store?product_type=digital',
            'products_source'          => 'digital',
            'products_limit'           => 6,
            'products_columns'         => 3,
            'products_show_image'      => true,
            'products_show_price'      => true,
            'products_show_category'   => true,
            'products_show_button'     => true,

            // Packages Section
            'packages_badge'           => 'Tailored Solutions',
            'packages_title'           => 'Packages built for your success',
            'packages_action_text'     => 'Browse all packages',
            'packages_action_url'      => '/store?product_type=package',
            'packages_limit'           => 3,

            // Membership Section
            'membership_badge'         => 'Membership Benefits',
            'membership_title'         => 'Exclusive access for members',
            'membership_description'   => 'Unlock premium tutorials, member-only tools, and priority updates with an active membership plan.',
            'membership_cta_text'      => 'Explore Memberships',
            'membership_cta_url'       => '/store?product_type=membership',
            'membership_limit'         => 3,
            'membership_image_url'     => '',

            // Latest Articles Section
            'latest_title'             => 'Latest Articles',
            'latest_show_heading'      => true,
            'latest_limit'             => 6,

            // Call To Action Section
            'cta_badge'                => 'Get Started',
            'cta_title'                => 'Ready to elevate your digital presence?',
            'cta_description'          => 'Discover modern digital tools, resources, and professional solutions designed for your growth.',
            'cta_primary_text'         => 'Explore Store',
            'cta_primary_url'          => '/store',
            'cta_secondary_text'       => 'Contact Support',
            'cta_secondary_url'        => '/page/contact-us',

            // Footer
            'footer_brand_name'        => '',
            'footer_summary'           => 'Tutorials, tools, digital products, and professional services for growing online.',
            'footer_copyright'         => '',

            // Colors: Light Mode
            'accent_color'             => '#2563eb',
            'accent_hover'             => '#1d4ed8',
            'color_bg'                 => '#f8fafc',
            'color_surface'            => '#ffffff',
            'color_text'               => '#253044',
            'color_heading'            => '#0f172a',
            'color_muted'              => '#5b6678',
            'color_border'             => '#e3e7ef',
            'color_footer_bg'          => '#0f172a',
            'color_footer_text'        => '#cbd5e1',

            // Colors: Dark Mode
            'dark_accent_color'        => '#3b82f6',
            'dark_accent_hover'        => '#60a5fa',
            'dark_color_bg'            => '#0b1120',
            'dark_color_surface'       => '#111827',
            'dark_color_text'          => '#cbd5e1',
            'dark_color_heading'       => '#f8fafc',
            'dark_color_muted'         => '#94a3b8',
            'dark_color_border'        => '#334155',
            'dark_color_footer_bg'     => '#030712',
            'dark_color_footer_text'   => '#cbd5e1',

            // Typography & Layout
            'font_sans'                => '',
            'font_mono'                => '',
            'font_base_size'           => 16,
            'font_line_height'         => 1.6,
            'heading_weight'           => 800,
            'container_width'          => 1280,
            'border_radius'            => 10,
            'button_radius'            => 12,

            // Custom CSS
            'custom_css'               => '',
        ];

        return $defaults;
    }
}

if (!function_exists('fw_get_config')) {
    /**
     * Retrieve effective configuration: Default Config + Saved Theme Mods = Effective Config.
     */
    function fw_get_config(?string $key = null, mixed $default = null): mixed
    {
        $defaults = fw_default_config();

        if ($key === null) {
            $effective = $defaults;
            if (function_exists('get_theme_mod')) {
                foreach ($defaults as $k => $defVal) {
                    $mod = get_theme_mod($k, null);
                    if ($mod !== null && $mod !== '') {
                        $effective[$k] = $mod;
                    }
                }
            }
            return $effective;
        }

        $fallback = $default ?? ($defaults[$key] ?? null);

        if (function_exists('get_theme_mod')) {
            $mod = get_theme_mod($key, null);
            if ($mod !== null && $mod !== '') {
                return $mod;
            }
        }

        return $fallback;
    }
}

if (!function_exists('fw_get_section_config')) {
    /**
     * Retrieve all configuration values pertinent to a specific section.
     *
     * @return array<string, mixed>
     */
    function fw_get_section_config(string $sectionId): array
    {
        $all = fw_get_config();
        $prefix = match ($sectionId) {
            'hero'             => 'hero_',
            'about'            => 'about_',
            'services'         => 'services_',
            'products', 'digital-products' => 'products_',
            'packages'         => 'packages_',
            'memberships'      => 'membership_',
            'latest', 'latest-posts' => 'latest_',
            'cta'              => 'cta_',
            'trust-stats'      => 'trust_stats_',
            'footer'           => 'footer_',
            default            => $sectionId . '_',
        };

        $sectionCfg = [];
        foreach ($all as $k => $v) {
            if (str_starts_with($k, $prefix)) {
                $subKey = substr($k, strlen($prefix));
                $sectionCfg[$subKey] = $v;
            }
        }

        // Direct keys fallback
        if ($sectionId === 'trust-stats' && isset($all['trust_stats_data'])) {
            $sectionCfg['items'] = $all['trust_stats_data'];
        }

        return $sectionCfg;
    }
}

if (!function_exists('fw_sanitize_color')) {
    /**
     * Validate and sanitize a CSS color string (#rgb, #rrggbb, #rrggbbaa, rgb(), rgba()).
     */
    function fw_sanitize_color(?string $color): string
    {
        if ($color === null) {
            return '';
        }
        $c = trim($color);
        if (preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{4}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $c) === 1) {
            return strtolower($c);
        }
        if (preg_match('/^rgba?\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}(?:\s*,\s*(?:0|1|0?\.\d+))?\s*\)$/i', $c) === 1) {
            return $c;
        }
        return '';
    }
}

if (!function_exists('fw_sanitize_url')) {
    /**
     * Validate and sanitize a URL or relative path.
     * Strictly rejects dangerous schemes (javascript:, data:, vbscript:, file:), control characters, and malformed characters.
     */
    function fw_sanitize_url(?string $url): string
    {
        if ($url === null) {
            return '';
        }
        $u = trim($url);
        if ($u === '') {
            return '';
        }

        // Reject dangerous characters
        if (preg_match('/[\x00-\x1F\x7F<>"\']/', $u) === 1) {
            return '';
        }

        // Allow fragments or root-relative paths
        if ($u === '/' || str_starts_with($u, '#') || (str_starts_with($u, '/') && !str_starts_with($u, '//') && !str_contains($u, '..'))) {
            return $u;
        }

        // Validate scheme
        $scheme = parse_url($u, PHP_URL_SCHEME);
        if (is_string($scheme) && in_array(strtolower($scheme), ['http', 'https'], true)) {
            return filter_var($u, FILTER_VALIDATE_URL) ? $u : '';
        }

        return '';
    }
}

if (!function_exists('fw_sanitize_custom_css')) {
    /**
     * Strict Custom CSS sanitizer.
     * Blocks tags, expressions, dangerous URI schemes, @import, and control characters.
     */
    function fw_sanitize_custom_css(?string $css): string
    {
        if ($css === null) {
            return '';
        }

        $c = (string)$css;
        // Strip null bytes and control chars (except \r, \n, \t)
        $c = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $c) ?? '';

        // Strip HTML tags and closing style tags
        $c = preg_replace('/<\/?(?:script|style|iframe|object|embed|applet)\b[^>]*>/i', '', $c) ?? '';
        $c = str_ireplace(['</style', '<style', '<script', '</script'], '', $c);

        // Strip dynamic expression() and dangerous protocols
        $c = preg_replace('/expression\s*\([^)]*\)/i', '', $c) ?? '';
        $c = preg_replace('/behavior\s*:[^;}]*(?:;|$)/i', '/* blocked-behavior */', $c) ?? '';
        $c = preg_replace('/url\s*\(\s*["\']?\s*(?:javascript|vbscript|data):/i', 'url("about:blank', $c) ?? '';
        $c = preg_replace('/-moz-binding\s*:[^;}]*(?:;|$)/i', '/* blocked-binding */', $c) ?? '';
        $c = preg_replace('/@import\b[^;}]*(?:;|$)/i', '/* blocked-import */', $c) ?? '';

        return trim($c);
    }
}

if (!function_exists('fw_extract_youtube_id')) {
    /**
     * Safely extract YouTube video ID from various URL formats including watch, embed, shorts, youtu.be,
     * and URLs with arbitrary query parameters.
     */
    function fw_extract_youtube_id(string $url): ?string
    {
        $u = trim($url);
        if ($u === '') {
            return null;
        }

        // Direct match for watch?v=, embed/, v/, shorts/, youtu.be/
        if (preg_match('#(?:youtube\.com\/(?:watch\?(?:.*&)?v=|embed\/|v\/|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]{6,32})#i', $u, $matches)) {
            return $matches[1];
        }

        // Fallback: parse query string for ?v= or &v= on any youtube.com host
        $parsed = parse_url($u);
        $host = strtolower((string)($parsed['host'] ?? ''));
        if ($host !== '' && (str_ends_with($host, 'youtube.com') || $host === 'youtube.com')) {
            if (!empty($parsed['query'])) {
                parse_str($parsed['query'], $queryParams);
                if (!empty($queryParams['v']) && is_string($queryParams['v']) && preg_match('/^[a-zA-Z0-9_-]{6,32}$/', $queryParams['v'])) {
                    return $queryParams['v'];
                }
            }
        }

        return null;
    }
}

if (!function_exists('fw_extract_vimeo_id')) {
    /**
     * Safely extract Vimeo video ID from various URL formats including player, channels, groups, and albums.
     */
    function fw_extract_vimeo_id(string $url): ?string
    {
        $u = trim($url);
        if ($u === '') {
            return null;
        }
        if (preg_match('#(?:vimeo\.com\/(?:channels\/(?:\w+\/)?|groups\/[^\/]*\/videos\/|album\/(?:\d+\/)?video\/|video\/|)|player\.vimeo\.com\/video\/)([0-9]{6,12})#i', $u, $matches)) {
            return $matches[1];
        }
        return null;
    }
}

if (!function_exists('fw_resolve_hero_video_source')) {
    /**
     * Resolve Hero Video Source safely into direct video, YouTube, Vimeo, generic third-party embed player, or unknown.
     *
     * @param string $input Raw URL or embed snippet from customizer
     * @param array<string, mixed> $options ['autoplay' => bool, 'muted' => bool]
     * @return array{type: 'direct'|'youtube'|'vimeo'|'embed'|'unknown', url: string, src: string, id?: string, is_iframe: bool}
     */
    function fw_resolve_hero_video_source(string $input, array $options = []): array
    {
        $raw = trim($input);
        if ($raw === '') {
            return ['type' => 'unknown', 'url' => '', 'src' => '', 'is_iframe' => false];
        }

        // 1. Check if raw embed HTML was pasted (e.g. <iframe ... src="..." or <video ... src="...")
        if (preg_match('/<iframe\b[^>]*\bsrc=["\']([^"\']+)["\']/i', $raw, $matches)) {
            $raw = trim($matches[1]);
        } elseif (preg_match('/<video\b[^>]*\bsrc=["\']([^"\']+)["\']/i', $raw, $matches)) {
            $raw = trim($matches[1]);
        }

        // 2. Reject dangerous URI schemes, control characters, and dangerous symbols
        if (preg_match('/^(?:javascript|data|vbscript|file):/i', $raw) || preg_match('/[\x00-\x1F\x7F<>"\']/', $raw)) {
            return ['type' => 'unknown', 'url' => '', 'src' => '', 'is_iframe' => false];
        }

        $autoplay = !empty($options['autoplay']);
        $muted = !isset($options['muted']) || !empty($options['muted']);

        // 3. YouTube detection
        $ytId = fw_extract_youtube_id($raw);
        if ($ytId !== null) {
            $params = [
                'rel' => '0',
                'playsinline' => '1',
            ];
            if ($autoplay) {
                $params['autoplay'] = '1';
                if ($muted) {
                    $params['mute'] = '1';
                }
            }
            $queryStr = http_build_query($params);
            $embedUrl = "https://www.youtube-nocookie.com/embed/{$ytId}?{$queryStr}";
            return [
                'type'      => 'youtube',
                'url'       => $embedUrl,
                'src'       => $embedUrl,
                'id'        => $ytId,
                'is_iframe' => true,
            ];
        }

        // 4. Vimeo detection
        $vmId = fw_extract_vimeo_id($raw);
        if ($vmId !== null) {
            $params = [
                'playsinline' => '1',
            ];
            if ($autoplay) {
                $params['autoplay'] = '1';
                if ($muted) {
                    $params['muted'] = '1';
                }
            }
            $queryStr = http_build_query($params);
            $embedUrl = "https://player.vimeo.com/video/{$vmId}?{$queryStr}";
            return [
                'type'      => 'vimeo',
                'url'       => $embedUrl,
                'src'       => $embedUrl,
                'id'        => $vmId,
                'is_iframe' => true,
            ];
        }

        // 5. Direct media file check based on path extension (.mp4, .webm, .ogg, .ogv, .mov, .m4v)
        $parsedPath = parse_url($raw, PHP_URL_PATH);
        if (is_string($parsedPath) && preg_match('/\.(?:mp4|webm|ogg|ogv|mov|m4v)$/i', $parsedPath)) {
            return [
                'type'      => 'direct',
                'url'       => $raw,
                'src'       => $raw,
                'is_iframe' => false,
            ];
        }

        // 6. Generic third-party player / embed URL (e.g. https://player.abyssplayer.com/TEqUw-gUj)
        $scheme = parse_url($raw, PHP_URL_SCHEME);
        if (is_string($scheme) && in_array(strtolower($scheme), ['http', 'https'], true)) {
            if (filter_var($raw, FILTER_VALIDATE_URL)) {
                return [
                    'type'      => 'embed',
                    'url'       => $raw,
                    'src'       => $raw,
                    'is_iframe' => true,
                ];
            }
        }

        return ['type' => 'unknown', 'url' => '', 'src' => '', 'is_iframe' => false];
    }
}

if (!function_exists('fw_render_custom_styles')) {
    /**
     * Output CSS Custom Properties (Tokens) for light/dark themes and sanitized custom CSS.
     */
    function fw_render_custom_styles(): string
    {
        $cfg = fw_get_config();
        $lines = [];

        // Light tokens
        $accent = fw_sanitize_color((string)($cfg['accent_color'] ?? '#2563eb'));
        $accentHover = fw_sanitize_color((string)($cfg['accent_hover'] ?? '#1d4ed8'));
        $bg = fw_sanitize_color((string)($cfg['color_bg'] ?? '#f8fafc'));
        $surface = fw_sanitize_color((string)($cfg['color_surface'] ?? '#ffffff'));
        $text = fw_sanitize_color((string)($cfg['color_text'] ?? '#253044'));
        $heading = fw_sanitize_color((string)($cfg['color_heading'] ?? '#0f172a'));
        $muted = fw_sanitize_color((string)($cfg['color_muted'] ?? '#5b6678'));
        $border = fw_sanitize_color((string)($cfg['color_border'] ?? '#e3e7ef'));
        $footerBg = fw_sanitize_color((string)($cfg['color_footer_bg'] ?? '#0f172a'));
        $footerText = fw_sanitize_color((string)($cfg['color_footer_text'] ?? '#cbd5e1'));

        $containerW = max(960, min(1920, (int)($cfg['container_width'] ?? 1280)));
        $radius = max(0, min(32, (int)($cfg['border_radius'] ?? 10)));
        $buttonRadius = max(0, min(32, (int)($cfg['button_radius'] ?? 12)));
        $headerH = max(48, min(120, (int)($cfg['header_height'] ?? 64)));

        $rootProps = [];
        if ($accent !== '') { $rootProps[] = "--accent: {$accent};"; }
        if ($accentHover !== '') { $rootProps[] = "--accent-hover: {$accentHover};"; }
        if ($bg !== '') { $rootProps[] = "--bg: {$bg};"; }
        if ($surface !== '') { $rootProps[] = "--surface: {$surface};"; }
        if ($text !== '') { $rootProps[] = "--text: {$text};"; }
        if ($heading !== '') { $rootProps[] = "--heading: {$heading};"; }
        if ($muted !== '') { $rootProps[] = "--muted: {$muted};"; }
        if ($border !== '') { $rootProps[] = "--border: {$border};"; }
        if ($footerBg !== '') { $rootProps[] = "--footer-bg: {$footerBg};"; }
        if ($footerText !== '') { $rootProps[] = "--footer-text: {$footerText};"; }

        $rootProps[] = "--container: {$containerW}px;";
        $rootProps[] = "--radius: {$radius}px;";
        $rootProps[] = "--header-h: {$headerH}px;";

        if ($rootProps !== []) {
            $lines[] = ":root {\n    " . implode("\n    ", $rootProps) . "\n}";
        }

        // Dark tokens
        $darkAccent = fw_sanitize_color((string)($cfg['dark_accent_color'] ?? '#3b82f6'));
        $darkAccentHover = fw_sanitize_color((string)($cfg['dark_accent_hover'] ?? '#60a5fa'));
        $darkBg = fw_sanitize_color((string)($cfg['dark_color_bg'] ?? '#0b1120'));
        $darkSurface = fw_sanitize_color((string)($cfg['dark_color_surface'] ?? '#111827'));
        $darkText = fw_sanitize_color((string)($cfg['dark_color_text'] ?? '#cbd5e1'));
        $darkHeading = fw_sanitize_color((string)($cfg['dark_color_heading'] ?? '#f8fafc'));
        $darkMuted = fw_sanitize_color((string)($cfg['dark_color_muted'] ?? '#94a3b8'));
        $darkBorder = fw_sanitize_color((string)($cfg['dark_color_border'] ?? '#334155'));
        $darkFooterBg = fw_sanitize_color((string)($cfg['dark_color_footer_bg'] ?? '#030712'));
        $darkFooterText = fw_sanitize_color((string)($cfg['dark_color_footer_text'] ?? '#cbd5e1'));

        $darkProps = [];
        if ($darkAccent !== '') { $darkProps[] = "--accent: {$darkAccent};"; }
        if ($darkAccentHover !== '') { $darkProps[] = "--accent-hover: {$darkAccentHover};"; }
        if ($darkBg !== '') { $darkProps[] = "--bg: {$darkBg};"; }
        if ($darkSurface !== '') { $darkProps[] = "--surface: {$darkSurface};"; }
        if ($darkText !== '') { $darkProps[] = "--text: {$darkText};"; }
        if ($darkHeading !== '') { $darkProps[] = "--heading: {$darkHeading};"; }
        if ($darkMuted !== '') { $darkProps[] = "--muted: {$darkMuted};"; }
        if ($darkBorder !== '') { $darkProps[] = "--border: {$darkBorder};"; }
        if ($darkFooterBg !== '') { $darkProps[] = "--footer-bg: {$darkFooterBg};"; }
        if ($darkFooterText !== '') { $darkProps[] = "--footer-text: {$darkFooterText};"; }

        if ($darkProps !== []) {
            $lines[] = "[data-theme=\"dark\"] {\n    " . implode("\n    ", $darkProps) . "\n}";
        }

        // Custom CSS
        $customCss = fw_sanitize_custom_css((string)($cfg['custom_css'] ?? ''));
        if ($customCss !== '') {
            $lines[] = "/* Custom CSS */\n" . $customCss;
        }

        if ($lines === []) {
            return '';
        }

        return "<style id=\"fw-theme-tokens\">\n" . implode("\n\n", $lines) . "\n</style>\n";
    }
}
