<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Engines;

use FavoriteCMS\Tools\Contracts\EngineInterface;
use FavoriteCMS\Tools\Models\Tool;
use FavoriteCMS\Tools\Support\EngineType;
use FavoriteCMS\Tools\Support\ResultType;

class JavaScriptEngine implements EngineInterface
{
    public function canHandle(Tool $tool): bool
    {
        return $tool->engine === EngineType::JAVASCRIPT;
    }

    public function validate(Tool $tool, array $inputs): array
    {
        $raw = (string)($inputs['input'] ?? $inputs['js'] ?? $inputs['code'] ?? $inputs['text'] ?? '');
        if (trim($raw) === '') {
            return ['JavaScript code input cannot be empty.'];
        }
        return [];
    }

    public function execute(Tool $tool, array $inputs): array
    {
        $code = (string)($inputs['input'] ?? $inputs['js'] ?? $inputs['code'] ?? $inputs['text'] ?? '');
        $action = (string)($tool->configuration['operation'] ?? $tool->configuration['action'] ?? $this->detectAction($tool->slug));

        return match ($action) {
            'minify'   => $this->minify($code),
            'validate' => $this->validateJs($code),
            default    => $this->format($code),
        };
    }

    private function detectAction(string $slug): string
    {
        if (str_contains($slug, 'minif')) return 'minify';
        if (str_contains($slug, 'validat')) return 'validate';
        return 'format';
    }

    private function format(string $js): array
    {
        // Safe token-like formatting without running code
        $formatted = $this->beautifyJs($js);

        return [
            'success' => true,
            'type'    => ResultType::TEXT,
            'data'    => $formatted,
            'value'   => $formatted,
            'meta'    => [
                'original_size'  => strlen($js),
                'formatted_size' => strlen($formatted),
            ],
        ];
    }

    private function beautifyJs(string $js): string
    {
        // Collapse whitespace outside strings
        $length = strlen($js);
        $output = '';
        $indentLevel = 0;
        $indentStr = '    ';
        $inString = false;
        $stringChar = '';
        $inLineComment = false;
        $inBlockComment = false;

        for ($i = 0; $i < $length; $i++) {
            $char = $js[$i];
            $next = $i + 1 < $length ? $js[$i + 1] : '';

            // Handle line comments
            if ($inLineComment) {
                $output .= $char;
                if ($char === "\n") {
                    $inLineComment = false;
                    $output .= str_repeat($indentStr, $indentLevel);
                }
                continue;
            }

            // Handle block comments
            if ($inBlockComment) {
                $output .= $char;
                if ($char === '*' && $next === '/') {
                    $output .= '/';
                    $i++;
                    $inBlockComment = false;
                }
                continue;
            }

            // Handle strings
            if ($inString) {
                $output .= $char;
                if ($char === '\\') {
                    if ($next !== '') {
                        $output .= $next;
                        $i++;
                    }
                } elseif ($char === $stringChar) {
                    $inString = false;
                }
                continue;
            }

            // Check for comment start
            if ($char === '/' && $next === '/') {
                $inLineComment = true;
                $output .= '//';
                $i++;
                continue;
            }
            if ($char === '/' && $next === '*') {
                $inBlockComment = true;
                $output .= '/*';
                $i++;
                continue;
            }

            // Check for string start
            if ($char === '"' || $char === "'" || $char === '`') {
                $inString = true;
                $stringChar = $char;
                $output .= $char;
                continue;
            }

            // Formatting characters
            if ($char === '{') {
                $indentLevel++;
                $output = rtrim($output) . " {\n" . str_repeat($indentStr, $indentLevel);
            } elseif ($char === '}') {
                $indentLevel = max(0, $indentLevel - 1);
                $output = rtrim($output) . "\n" . str_repeat($indentStr, $indentLevel) . "}";
            } elseif ($char === ';') {
                $output .= ";\n" . str_repeat($indentStr, $indentLevel);
            } elseif ($char === "\n" || $char === "\r") {
                // normalize line breaks
                if ($output !== '' && !str_ends_with($output, "\n") && !str_ends_with($output, $indentStr)) {
                    $output .= "\n" . str_repeat($indentStr, $indentLevel);
                }
            } else {
                $output .= $char;
            }
        }

        return preg_replace("/\n{3,}/", "\n\n", trim($output));
    }

    private function minify(string $js): array
    {
        // Strip block comments
        $clean = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $js);
        // Strip line comments
        $clean = preg_replace('/(?<!:)\/\/.*$/m', '', $clean);
        // Remove extra spaces
        $clean = preg_replace('/\s*([{}();,:><=+\-*\/&|!])\s*/', '$1', $clean);
        // Collapse whitespace
        $clean = preg_replace('/\s+/', ' ', $clean);
        $clean = trim($clean);

        $saved = max(0, strlen($js) - strlen($clean));
        $percent = strlen($js) > 0 ? round(($saved / strlen($js)) * 100, 1) : 0;

        return [
            'success' => true,
            'type'    => ResultType::TEXT,
            'data'    => $clean,
            'value'   => $clean,
            'meta'    => [
                'original_size' => strlen($js),
                'minified_size' => strlen($clean),
                'saved_bytes'   => $saved,
                'saved_percent' => $percent . '%',
            ],
        ];
    }

    private function validateJs(string $js): array
    {
        $openBraces = substr_count($js, '{');
        $closeBraces = substr_count($js, '}');
        $openParens = substr_count($js, '(');
        $closeParens = substr_count($js, ')');
        $openBrackets = substr_count($js, '[');
        $closeBrackets = substr_count($js, ']');

        $errors = [];
        if ($openBraces !== $closeBraces) {
            $errors[] = "Unbalanced curly braces: {$openBraces} open vs {$closeBraces} closed.";
        }
        if ($openParens !== $closeParens) {
            $errors[] = "Unbalanced parentheses: {$openParens} open vs {$closeParens} closed.";
        }
        if ($openBrackets !== $closeBrackets) {
            $errors[] = "Unbalanced square brackets: {$openBrackets} open vs {$closeBrackets} closed.";
        }

        $isValid = empty($errors);
        $resultData = [
            'valid'   => $isValid,
            'message' => $isValid ? 'JavaScript syntax check passed! Structure is balanced.' : 'Syntax issues found.',
            'errors'  => $errors,
            'counts'  => [
                'braces'   => "{$openBraces} / {$closeBraces}",
                'parens'   => "{$openParens} / {$closeParens}",
                'brackets' => "{$openBrackets} / {$closeBrackets}",
            ],
        ];

        return [
            'success' => true,
            'type'    => ResultType::JSON,
            'data'    => $resultData,
            'value'   => $resultData,
        ];
    }
}
