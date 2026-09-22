<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Support;

use InvalidArgumentException;

class CssScoper
{
    private const FORBIDDEN_TOKENS = [
        '@import',
        'expression(',
        'javascript:',
        'behavior:',
        '-moz-binding:',
        'vbscript:',
    ];

    private const FORBIDDEN_GLOBAL_SELECTORS = [
        'html',
        'body',
        ':root',
        '*',
        'header',
        'footer',
        'nav',
        'main',
        'aside',
        'head',
        'meta',
        'title',
    ];

    /**
     * Validate and scope CSS rules to .fwt-design-container[data-design="SLUG"].
     *
     * @throws InvalidArgumentException if forbidden tokens or unscopeable global selectors exist.
     */
    public static function validateAndScope(string $css, string $slug): string
    {
        $cleanSlug = preg_replace('/[^a-zA-Z0-9_-]/', '', $slug);
        if ($cleanSlug === '') {
            $cleanSlug = 'default';
        }
        $scope = ".fwt-design-container[data-design=\"{$cleanSlug}\"]";

        $css = trim($css);
        if ($css === '') {
            return '';
        }

        // 1. Check for dangerous CSS constructs
        $lowerCss = strtolower($css);
        foreach (self::FORBIDDEN_TOKENS as $token) {
            if (str_contains($lowerCss, $token)) {
                throw new InvalidArgumentException("CSS contains prohibited construct: '{$token}'");
            }
        }

        // 2. Tokenize and scope rules
        return self::scopeBlock($css, $scope);
    }

    private static function scopeBlock(string $css, string $scope): string
    {
        $output = '';
        $length = strlen($css);
        $i = 0;

        while ($i < $length) {
            // Skip whitespace
            while ($i < $length && ctype_space($css[$i])) {
                $i++;
            }
            if ($i >= $length) break;

            // Check comments
            if ($i + 1 < $length && $css[$i] === '/' && $css[$i + 1] === '*') {
                $end = strpos($css, '*/', $i + 2);
                if ($end === false) {
                    break;
                }
                $i = $end + 2;
                continue;
            }

            // Find start of declaration block '{'
            $bracePos = strpos($css, '{', $i);
            if ($bracePos === false) {
                break;
            }

            $selectorText = trim(substr($css, $i, $bracePos - $i));
            $i = $bracePos + 1;

            // Handle @media or other at-rules
            if (str_starts_with($selectorText, '@media') || str_starts_with($selectorText, '@supports')) {
                // Find matching closing brace for the at-rule block
                $depth = 1;
                $innerStart = $i;
                while ($i < $length && $depth > 0) {
                    if ($css[$i] === '{') $depth++;
                    elseif ($css[$i] === '}') $depth--;
                    $i++;
                }
                $innerCss = substr($css, $innerStart, $i - $innerStart - 1);
                $scopedInner = self::scopeBlock($innerCss, $scope);
                $output .= $selectorText . " {\n" . $scopedInner . "\n}\n";
                continue;
            }

            if (str_starts_with($selectorText, '@keyframes')) {
                // Keyframes are kept without selector scoping inside the keyframe block
                $depth = 1;
                $innerStart = $i;
                while ($i < $length && $depth > 0) {
                    if ($css[$i] === '{') $depth++;
                    elseif ($css[$i] === '}') $depth--;
                    $i++;
                }
                $innerCss = substr($css, $innerStart, $i - $innerStart - 1);
                $output .= $selectorText . " {\n" . $innerCss . "\n}\n";
                continue;
            }

            // Regular style rule block
            $depth = 1;
            $innerStart = $i;
            while ($i < $length && $depth > 0) {
                if ($css[$i] === '{') $depth++;
                elseif ($css[$i] === '}') $depth--;
                $i++;
            }
            $ruleBody = substr($css, $innerStart, $i - $innerStart - 1);

            // Scope the comma-separated selectors
            $selectors = explode(',', $selectorText);
            $scopedSelectors = [];

            foreach ($selectors as $sel) {
                $sel = trim($sel);
                if ($sel === '') continue;

                $selLower = strtolower($sel);

                // Reject top-level global selectors
                foreach (self::FORBIDDEN_GLOBAL_SELECTORS as $fg) {
                    if ($selLower === $fg || str_starts_with($selLower, $fg . ' ') || str_starts_with($selLower, $fg . ':') || str_starts_with($selLower, $fg . '.')) {
                        throw new InvalidArgumentException("Global selector '{$sel}' is prohibited in frontend designs.");
                    }
                }

                // If selector already starts with the scope or .fwt-design-container, keep it
                if (str_starts_with($sel, '.fwt-design-container') || str_starts_with($sel, $scope)) {
                    $scopedSelectors[] = $sel;
                } else {
                    $scopedSelectors[] = "{$scope} {$sel}";
                }
            }

            if (!empty($scopedSelectors)) {
                $output .= implode(', ', $scopedSelectors) . " {\n  " . trim($ruleBody) . "\n}\n";
            }
        }

        return trim($output);
    }
}

