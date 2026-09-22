<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Engines;

use FavoriteCMS\Tools\Contracts\EngineInterface;
use FavoriteCMS\Tools\Models\Tool;
use FavoriteCMS\Tools\Support\EngineType;
use FavoriteCMS\Tools\Support\ResultType;

class CssEngine implements EngineInterface
{
    public function canHandle(Tool $tool): bool
    {
        return $tool->engine === EngineType::CSS;
    }

    public function validate(Tool $tool, array $inputs): array
    {
        $raw = (string)($inputs['input'] ?? $inputs['css'] ?? $inputs['color'] ?? $inputs['text'] ?? '');
        if (trim($raw) === '') {
            return ['CSS / Color input cannot be empty.'];
        }
        return [];
    }

    public function execute(Tool $tool, array $inputs): array
    {
        $input = (string)($inputs['input'] ?? $inputs['css'] ?? $inputs['color'] ?? $inputs['text'] ?? '');
        $action = (string)($tool->configuration['operation'] ?? $tool->configuration['action'] ?? $this->detectAction($tool->slug));

        return match ($action) {
            'minify'                => $this->minify($input),
            'validate'              => $this->validateCss($input),
            'prefix'                => $this->prefix($input),
            'color', 'color_convert'=> $this->convertColor($input, (string)($inputs['target_format'] ?? 'all')),
            'preview'               => $this->preview($input),
            default                 => $this->format($input),
        };
    }

    private function detectAction(string $slug): string
    {
        if (str_contains($slug, 'minif')) return 'minify';
        if (str_contains($slug, 'validat')) return 'validate';
        if (str_contains($slug, 'prefix')) return 'prefix';
        if (str_contains($slug, 'color')) return 'color';
        if (str_contains($slug, 'preview')) return 'preview';
        return 'format';
    }

    private function format(string $css): array
    {
        // Remove comments temporarily
        $css = preg_replace('/\s+/', ' ', $css);
        $css = str_replace(['{', '}', ';'], [" {\n    ", "\n}\n\n", ";\n    "], $css);
        $lines = explode("\n", $css);
        $cleaned = [];
        foreach ($lines as $line) {
            $t = rtrim($line);
            if (trim($t) !== '') {
                $cleaned[] = $t;
            }
        }
        $formatted = trim(implode("\n", $cleaned));

        return [
            'success' => true,
            'type'    => ResultType::CSS,
            'data'    => $formatted,
            'value'   => $formatted,
            'meta'    => [
                'original_size'  => strlen($css),
                'formatted_size' => strlen($formatted),
            ],
        ];
    }

    private function minify(string $css): array
    {
        // Strip comments
        $clean = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        // Remove space around selectors and braces
        $clean = preg_replace('/\s*([{}|:;,])\s*/', '$1', $clean);
        // Collapse multiple spaces
        $clean = preg_replace('/\s+/', ' ', $clean);
        $clean = trim($clean);

        $saved = max(0, strlen($css) - strlen($clean));
        $percent = strlen($css) > 0 ? round(($saved / strlen($css)) * 100, 1) : 0;

        return [
            'success' => true,
            'type'    => ResultType::CSS,
            'data'    => $clean,
            'value'   => $clean,
            'meta'    => [
                'original_size' => strlen($css),
                'minified_size' => strlen($clean),
                'saved_bytes'   => $saved,
                'saved_percent' => $percent . '%',
            ],
        ];
    }

    private function validateCss(string $css): array
    {
        $openBraces = substr_count($css, '{');
        $closeBraces = substr_count($css, '}');
        $errors = [];

        if ($openBraces !== $closeBraces) {
            $diff = abs($openBraces - $closeBraces);
            $errors[] = "Mismatched curly braces: {$openBraces} opening vs {$closeBraces} closing ({$diff} missing).";
        }

        // Basic check for empty or unclosed blocks
        if (preg_match('/[^{}]+\{\s*$/', $css)) {
            $errors[] = 'Unclosed CSS rule detected at the end of input.';
        }

        $isValid = empty($errors);
        $resultData = [
            'valid'       => $isValid,
            'message'     => $isValid ? 'CSS syntax is valid!' : 'CSS syntax validation found issues.',
            'open_braces' => $openBraces,
            'close_braces'=> $closeBraces,
            'errors'      => $errors,
        ];

        return [
            'success' => true,
            'type'    => ResultType::JSON,
            'data'    => $resultData,
            'value'   => $resultData,
        ];
    }

    private function prefix(string $css): array
    {
        $prefixMap = [
            'user-select'        => ['-webkit-user-select', '-moz-user-select'],
            'backdrop-filter'    => ['-webkit-backdrop-filter'],
            'appearance'         => ['-webkit-appearance', '-moz-appearance'],
            'clip-path'          => ['-webkit-clip-path'],
            'box-decoration-break' => ['-webkit-box-decoration-break'],
            'mask-image'         => ['-webkit-mask-image'],
        ];

        $lines = explode("\n", $css);
        $outLines = [];

        foreach ($lines as $line) {
            $matched = false;
            foreach ($prefixMap as $prop => $prefixes) {
                if (preg_match('/^\s*' . preg_quote($prop, '/') . '\s*:\s*(.+)$/i', $line, $m)) {
                    $val = $m[1];
                    // Add vendor prefixes first
                    foreach ($prefixes as $p) {
                        $outLines[] = preg_replace('/' . preg_quote($prop, '/') . '/i', $p, $line, 1);
                    }
                    $outLines[] = $line;
                    $matched = true;
                    break;
                }
            }
            if (!$matched) {
                $outLines[] = $line;
            }
        }

        $prefixed = implode("\n", $outLines);

        return [
            'success' => true,
            'type'    => ResultType::CSS,
            'data'    => $prefixed,
            'value'   => $prefixed,
        ];
    }

    private function convertColor(string $rawColor, string $target = 'all'): array
    {
        $raw = trim($rawColor);
        $rgba = $this->parseColorToRgba($raw);

        if ($rgba === null) {
            $errData = [
                'valid'   => false,
                'error'   => 'Could not parse color: ' . $raw,
                'message' => 'Supported formats: #RGB, #RRGGBB, rgb(r, g, b), rgba(r, g, b, a), hsl(h, s%, l%)',
            ];
            return [
                'success' => false,
                'type'    => ResultType::JSON,
                'data'    => $errData,
                'value'   => $errData,
            ];
        }

        [$r, $g, $b, $a] = $rgba;

        $hex = sprintf('#%02X%02X%02X', $r, $g, $b);
        $hexAlpha = sprintf('#%02X%02X%02X%02X', $r, $g, $b, (int)round($a * 255));
        $rgbStr = "rgb({$r}, {$g}, {$b})";
        $rgbaStr = "rgba({$r}, {$g}, {$b}, {$a})";

        [$h, $s, $l] = $this->rgbToHsl($r, $g, $b);
        $hslStr = "hsl({$h}, {$s}%, {$l}%)";
        $hslaStr = "hsla({$h}, {$s}%, {$l}%, {$a})";

        $allFormats = [
            'hex'   => $hex,
            'hex8'  => $hexAlpha,
            'rgb'   => $rgbStr,
            'rgba'  => $rgbaStr,
            'hsl'   => $hslStr,
            'hsla'  => $hslaStr,
            'values'=> [
                'r' => $r, 'g' => $g, 'b' => $b, 'a' => $a,
                'h' => $h, 's' => $s, 'l' => $l,
            ],
        ];

        $output = "HEX:  {$hex}\nRGB:  {$rgbStr}\nRGBA: {$rgbaStr}\nHSL:  {$hslStr}\nHSLA: {$hslaStr}";

        return [
            'success' => true,
            'type'    => ResultType::TEXT,
            'data'    => $allFormats,
            'value'   => $output,
            'meta'    => $allFormats,
        ];
    }

    private function parseColorToRgba(string $c): ?array
    {
        $c = strtolower(trim($c));

        // HEX: #FFF or #FFFFFF
        if (preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', $c)) {
            $hex = ltrim($c, '#');
            if (strlen($hex) === 3) {
                $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
            }
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
            return [$r, $g, $b, 1.0];
        }

        // RGB: rgb(255, 0, 128)
        if (preg_match('/^rgba?\s*\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})(?:\s*,\s*([0-9.]+))?\s*\)$/i', $c, $m)) {
            $r = min(255, max(0, (int)$m[1]));
            $g = min(255, max(0, (int)$m[2]));
            $b = min(255, max(0, (int)$m[3]));
            $a = isset($m[4]) ? min(1.0, max(0.0, (float)$m[4])) : 1.0;
            return [$r, $g, $b, $a];
        }

        return null;
    }

    private function rgbToHsl(int $r, int $g, int $b): array
    {
        $r /= 255;
        $g /= 255;
        $b /= 255;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $diff = $max - $min;

        $l = ($max + $min) / 2;

        if ($diff == 0) {
            $h = $s = 0;
        } else {
            $s = $l > 0.5 ? $diff / (2 - $max - $min) : $diff / ($max + $min);
            if ($max == $r) {
                $h = ($g - $b) / $diff + ($g < $b ? 6 : 0);
            } elseif ($max == $g) {
                $h = ($b - $r) / $diff + 2;
            } else {
                $h = ($r - $g) / $diff + 4;
            }
            $h /= 6;
        }

        return [round($h * 360), round($s * 100), round($l * 100)];
    }

    private function preview(string $css): array
    {
        $escapedCss = htmlspecialchars($css, ENT_QUOTES, 'UTF-8');
        $htmlDoc = '<!DOCTYPE html><html><head><meta charset="utf-8"><style>'
            . $css
            . '</style></head><body><div class="preview-box"><h2>Preview Card</h2><p>This is live rendered user CSS.</p><button class="btn">Button</button></div></body></html>';

        $escapedDoc = htmlspecialchars($htmlDoc, ENT_QUOTES, 'UTF-8');
        $previewHtml = '<div class="fwt-preview-frame-wrapper">'
            . '<iframe class="fwt-preview-frame" sandbox="allow-scripts" srcdoc="' . $escapedDoc . '" style="width:100%;min-height:380px;border:1px solid var(--border,#e2e8f0);border-radius:8px;background:#ffffff;"></iframe>'
            . '</div>';

        return [
            'type'  => ResultType::HTML,
            'value' => $previewHtml,
        ];
    }
}
