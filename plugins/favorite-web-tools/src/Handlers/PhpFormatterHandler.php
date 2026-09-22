<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Handlers;

use FavoriteCMS\Tools\Contracts\ToolHandlerInterface;
use FavoriteCMS\Tools\Support\ResultType;
use InvalidArgumentException;

class PhpFormatterHandler extends AbstractToolHandler
{
    public function getId(): string
    {
        return 'php_formatter';
    }

    public function getName(): string
    {
        return 'PHP Code Formatter';
    }

    public function execute(array $inputs, array $config = []): array
    {
        $code = (string)($inputs['input'] ?? $inputs['code'] ?? $inputs['text'] ?? '');
        if (trim($code) === '') {
            throw new InvalidArgumentException('Please provide PHP code to format.');
        }

        $formatted = $this->formatPhp($code);

        return [
            'success' => true,
            'type'    => ResultType::TEXT,
            'data'    => $formatted,
            'value'   => $formatted,
            'meta'    => [
                'original_size'  => strlen($code),
                'formatted_size' => strlen($formatted),
            ],
        ];
    }

    private function formatPhp(string $code): string
    {
        $hasPhpTag = str_contains($code, '<?php') || str_contains($code, '<?=');
        $input = $hasPhpTag ? $code : "<?php\n" . $code;

        $tokens = @token_get_all($input);
        if (empty($tokens)) {
            return $code;
        }

        $output = '';
        $indentLevel = 0;
        $indentStr = '    ';
        $inForHeader = false;

        $count = count($tokens);
        for ($i = 0; $i < $count; $i++) {
            $token = $tokens[$i];

            if (is_string($token)) {
                if ($token === '{') {
                    $indentLevel++;
                    $output = rtrim($output) . " {\n" . str_repeat($indentStr, $indentLevel);
                } elseif ($token === '}') {
                    $indentLevel = max(0, $indentLevel - 1);
                    $output = rtrim($output) . "\n" . str_repeat($indentStr, $indentLevel) . "}";
                    if ($i + 1 < $count) {
                        $next = $tokens[$i + 1];
                        if (is_array($next) && ($next[0] === T_ELSE || $next[0] === T_ELSEIF || $next[0] === T_CATCH || $next[0] === T_FINALLY)) {
                            $output .= ' ';
                        } else {
                            $output .= "\n" . str_repeat($indentStr, $indentLevel);
                        }
                    }
                } elseif ($token === ';') {
                    $output .= ';';
                    if (!$inForHeader) {
                        $output .= "\n" . str_repeat($indentStr, $indentLevel);
                    } else {
                        $output .= ' ';
                    }
                } elseif ($token === '(') {
                    $output .= '(';
                } elseif ($token === ')') {
                    $output .= ')';
                } else {
                    $output .= $token;
                }
                continue;
            }

            [$id, $text] = $token;

            switch ($id) {
                case T_WHITESPACE:
                    // Collapse multiple whitespace
                    if (str_contains($text, "\n")) {
                        $output = rtrim($output) . "\n" . str_repeat($indentStr, $indentLevel);
                    } else {
                        $output .= ' ';
                    }
                    break;

                case T_FOR:
                    $inForHeader = true;
                    $output .= $text . ' ';
                    break;

                case T_IF:
                case T_WHILE:
                case T_FOREACH:
                case T_SWITCH:
                case T_FUNCTION:
                case T_CLASS:
                    $output .= $text . ' ';
                    break;

                case T_DOC_COMMENT:
                case T_COMMENT:
                    $output = rtrim($output) . "\n" . str_repeat($indentStr, $indentLevel) . trim($text) . "\n" . str_repeat($indentStr, $indentLevel);
                    break;

                default:
                    $output .= $text;
                    break;
            }
        }

        // Clean up excessive blank lines
        $clean = preg_replace("/\n{3,}/", "\n\n", trim($output));

        if (!$hasPhpTag && str_starts_with($clean, "<?php")) {
            $clean = trim(substr($clean, 5));
        }

        return $clean;
    }
}
