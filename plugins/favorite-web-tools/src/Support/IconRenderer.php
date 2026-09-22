<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Support;

class IconRenderer
{
    /**
     * Render an icon identifier, emoji, or SVG string into high-contrast accessible markup.
     *
     * @param string|null $icon The icon string (e.g. 'code', 'palette', 'cpu', '🛠️', or raw SVG)
     * @param string $class CSS class for the wrapper element
     * @param int $size Width and height in pixels for rendered SVG
     * @return string Safe HTML string
     */
    public static function render(?string $icon, string $class = 'fwt-cat-icon', int $size = 16): string
    {
        if ($icon === null || trim($icon) === '') {
            return self::wrapEmoji('🛠️', $class);
        }

        $icon = trim($icon);

        // Check if raw SVG
        if (str_starts_with(strtolower($icon), '<svg')) {
            return sprintf('<span class="%s" aria-hidden="true">%s</span>', htmlspecialchars($class, ENT_QUOTES, 'UTF-8'), $icon);
        }

        $id = strtolower($icon);
        $svg = self::getSvgByIdentifier($id, $size);

        if ($svg !== null) {
            return sprintf(
                '<span class="%s" aria-hidden="true" style="display:inline-flex;align-items:center;justify-content:center;line-height:1;flex-shrink:0;">%s</span>',
                htmlspecialchars($class, ENT_QUOTES, 'UTF-8'),
                $svg
            );
        }

        // Try normalized identifier (e.g. 'file-text' -> 'filetext')
        $normalized = preg_replace('/[^a-z0-9]/', '', $id);
        if ($normalized !== '' && $normalized !== $id) {
            $svg = self::getSvgByIdentifier($normalized, $size);
            if ($svg !== null) {
                return sprintf(
                    '<span class="%s" aria-hidden="true" style="display:inline-flex;align-items:center;justify-content:center;line-height:1;flex-shrink:0;">%s</span>',
                    htmlspecialchars($class, ENT_QUOTES, 'UTF-8'),
                    $svg
                );
            }
        }

        // If it looks like a plain text word (alphanumeric identifier) without SVG match, fallback to default tool SVG
        if (preg_match('/^[a-z0-9_-]+$/i', $icon)) {
            $fallback = self::getSvgByIdentifier('tool', $size);
            return sprintf(
                '<span class="%s" aria-hidden="true" style="display:inline-flex;align-items:center;justify-content:center;line-height:1;flex-shrink:0;">%s</span>',
                htmlspecialchars($class, ENT_QUOTES, 'UTF-8'),
                $fallback
            );
        }

        // Unicode emoji or custom text character
        return self::wrapEmoji($icon, $class);
    }

    private static function wrapEmoji(string $emoji, string $class): string
    {
        return sprintf(
            '<span class="%s" aria-hidden="true" style="display:inline-flex;align-items:center;justify-content:center;font-size:1.15em;line-height:1;flex-shrink:0;">%s</span>',
            htmlspecialchars($class, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($emoji, ENT_QUOTES, 'UTF-8')
        );
    }

    public static function getSvgByIdentifier(string $id, int $size = 16): ?string
    {
        $strokeWidth = 2;
        $commonAttrs = sprintf(
            'width="%d" height="%d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="%s" stroke-linecap="round" stroke-linejoin="round" class="fwt-svg-icon"',
            $size,
            $size,
            $strokeWidth
        );

        return match ($id) {
            'code', 'html' =>
                '<svg ' . $commonAttrs . '><polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline></svg>',

            'palette', 'css', 'style', 'paint' =>
                '<svg ' . $commonAttrs . '><circle cx="13.5" cy="6.5" r=".5" fill="currentColor"></circle><circle cx="17.5" cy="10.5" r=".5" fill="currentColor"></circle><circle cx="8.5" cy="7.5" r=".5" fill="currentColor"></circle><circle cx="6.5" cy="12.5" r=".5" fill="currentColor"></circle><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.965 6.012 17.461 2 12 2z"></path></svg>',

            'terminal', 'javascript', 'js' =>
                '<svg ' . $commonAttrs . '><polyline points="4 17 10 11 4 5"></polyline><line x1="12" y1="19" x2="20" y2="19"></line></svg>',

            'cpu', 'developer', 'dev' =>
                '<svg ' . $commonAttrs . '><rect x="4" y="4" width="16" height="16" rx="2" ry="2"></rect><rect x="9" y="9" width="6" height="6"></rect><line x1="9" y1="1" x2="9" y2="4"></line><line x1="15" y1="1" x2="15" y2="4"></line><line x1="9" y1="20" x2="9" y2="23"></line><line x1="15" y1="20" x2="15" y2="23"></line><line x1="20" y1="9" x2="23" y2="9"></line><line x1="20" y1="14" x2="23" y2="14"></line><line x1="1" y1="9" x2="4" y2="9"></line><line x1="1" y1="14" x2="4" y2="14"></line></svg>',

            'file-text', 'filetext', 'text', 'document' =>
                '<svg ' . $commonAttrs . '><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>',

            'server', 'php', 'database' =>
                '<svg ' . $commonAttrs . '><rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect><rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect><line x1="6" y1="6" x2="6.01" y2="6"></line><line x1="6" y1="18" x2="6.01" y2="18"></line></svg>',

            'activity', 'python', 'pulse', 'api' =>
                '<svg ' . $commonAttrs . '><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>',

            'grid', 'all', 'apps', 'categories' =>
                '<svg ' . $commonAttrs . '><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>',

            'tool', 'tools', 'wrench' =>
                '<svg ' . $commonAttrs . '><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>',

            'qrcode', 'qr' =>
                '<svg ' . $commonAttrs . '><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect><rect x="14" y="14" width="3" height="3"></rect><rect x="18" y="18" width="3" height="3"></rect><rect x="14" y="18" width="3" height="3"></rect><rect x="18" y="14" width="3" height="3"></rect></svg>',

            default => null,
        };
    }
}

