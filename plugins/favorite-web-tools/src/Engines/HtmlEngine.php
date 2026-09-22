<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Engines;

use DOMDocument;
use FavoriteCMS\Tools\Contracts\EngineInterface;
use FavoriteCMS\Tools\Models\Tool;
use FavoriteCMS\Tools\Support\EngineType;
use FavoriteCMS\Tools\Support\ResultType;
use InvalidArgumentException;

class HtmlEngine implements EngineInterface
{
    public function canHandle(Tool $tool): bool
    {
        return $tool->engine === EngineType::HTML;
    }

    public function validate(Tool $tool, array $inputs): array
    {
        $raw = (string)($inputs['input'] ?? $inputs['html'] ?? $inputs['text'] ?? '');
        if (trim($raw) === '') {
            return ['Input HTML cannot be empty.'];
        }
        return [];
    }

    public function execute(Tool $tool, array $inputs): array
    {
        $html = (string)($inputs['input'] ?? $inputs['html'] ?? $inputs['text'] ?? '');
        $action = (string)($tool->configuration['operation'] ?? $tool->configuration['action'] ?? $this->detectAction($tool->slug));

        return match ($action) {
            'minify'   => $this->minify($html),
            'validate' => $this->validateHtml($html),
            'encode'   => $this->encode($html),
            'decode'   => $this->decode($html),
            'preview'  => $this->preview($html),
            default    => $this->format($html),
        };
    }

    private function detectAction(string $slug): string
    {
        if (str_contains($slug, 'minif')) return 'minify';
        if (str_contains($slug, 'validat')) return 'validate';
        if (str_contains($slug, 'encod')) return 'encode';
        if (str_contains($slug, 'decod')) return 'decode';
        if (str_contains($slug, 'preview')) return 'preview';
        return 'format';
    }

    private function format(string $html): array
    {
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;

        $previousErrors = libxml_use_internal_errors(true);
        // UTF-8 prefix ensures correct character encoding
        $loaded = @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previousErrors);

        if ($loaded) {
            $formatted = $dom->saveHTML();
            $formatted = preg_replace('/<\?xml[^>]+>/i', '', $formatted);
            $formatted = trim($formatted);
        } else {
            // Regex fallback formatting
            $formatted = $this->regexFormatHtml($html);
        }

        return [
            'success' => true,
            'type'    => ResultType::HTML,
            'data'    => $formatted,
            'value'   => $formatted,
            'meta'    => [
                'original_size'  => strlen($html),
                'formatted_size' => strlen($formatted),
            ],
        ];
    }

    private function regexFormatHtml(string $html): string
    {
        $html = preg_replace('/\s+/', ' ', $html);
        $html = preg_replace('/(<\/[^>]+>)/', "$1\n", $html);
        $html = preg_replace('/(<[^\/>]+[^\/]>)/', "\n$1\n", $html);
        $lines = explode("\n", $html);
        $indent = 0;
        $output = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') continue;

            if (str_starts_with($trimmed, '</')) {
                $indent = max(0, $indent - 1);
            }

            $output[] = str_repeat('    ', $indent) . $trimmed;

            if (preg_match('/^<([a-z1-6]+)(?:\s+[^>]*)?>$/i', $trimmed, $m)) {
                $tag = strtolower($m[1]);
                if (!in_array($tag, ['img', 'br', 'hr', 'input', 'meta', 'link'], true)) {
                    $indent++;
                }
            }
        }

        return implode("\n", $output);
    }

    private function minify(string $html): array
    {
        // Remove comments (except conditional comments)
        $clean = preg_replace('/<!--(?!\s*(?:\[if [^\]]+]|<!|>))(?:(?!-->).)*-->/s', '', $html);
        // Collapse multiple whitespace
        $clean = preg_replace('/>\s+</', '><', $clean);
        $clean = preg_replace('/\s{2,}/', ' ', $clean);
        $clean = trim($clean);

        $saved = max(0, strlen($html) - strlen($clean));
        $percent = strlen($html) > 0 ? round(($saved / strlen($html)) * 100, 1) : 0;

        return [
            'success' => true,
            'type'    => ResultType::HTML,
            'data'    => $clean,
            'value'   => $clean,
            'meta'    => [
                'original_size' => strlen($html),
                'minified_size' => strlen($clean),
                'saved_bytes'   => $saved,
                'saved_percent' => $percent . '%',
            ],
        ];
    }

    private function validateHtml(string $html): array
    {
        $previous = libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $loaded = @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $errorList = [];
        foreach ($errors as $err) {
            // Filter out implied doctype warnings
            if (str_contains($err->message, 'Tag html invalid') || str_contains($err->message, 'xmlParseDocTypeDecl')) {
                continue;
            }
            $errorList[] = [
                'line'    => $err->line,
                'column'  => $err->column,
                'message' => trim($err->message),
                'level'   => $err->level === LIBXML_ERR_WARNING ? 'warning' : 'error',
            ];
        }

        $isValid = empty($errorList);
        $resultData = [
            'valid'   => $isValid,
            'message' => $isValid ? 'HTML structure is valid!' : 'HTML validation found issues.',
            'errors'  => $errorList,
            'count'   => count($errorList),
        ];

        return [
            'success' => true,
            'type'    => ResultType::JSON,
            'data'    => $resultData,
            'value'   => $resultData,
        ];
    }

    private function encode(string $html): array
    {
        $encoded = htmlentities($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return [
            'success' => true,
            'type'    => ResultType::TEXT,
            'data'    => $encoded,
            'value'   => $encoded,
        ];
    }

    private function decode(string $html): array
    {
        $decoded = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return [
            'success' => true,
            'type'    => ResultType::TEXT,
            'data'    => $decoded,
            'value'   => $decoded,
        ];
    }

    private function preview(string $html): array
    {
        // Safe sandboxed iframe output
        $escaped = htmlspecialchars($html, ENT_QUOTES, 'UTF-8');
        $previewHtml = '<div class="fwt-preview-frame-wrapper">'
            . '<iframe class="fwt-preview-frame" sandbox="allow-scripts" srcdoc="' . $escaped . '" style="width:100%;min-height:380px;border:1px solid var(--border,#e2e8f0);border-radius:8px;background:#ffffff;"></iframe>'
            . '</div>';

        return [
            'success' => true,
            'type'    => ResultType::HTML,
            'data'    => $previewHtml,
            'value'   => $previewHtml,
            'meta'    => [
                'raw_html' => $html,
            ],
        ];
    }
}
