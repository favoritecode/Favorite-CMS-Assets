<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Handlers;

use FavoriteCMS\Tools\Support\ResultType;

class PhpArrayToJsonHandler extends AbstractToolHandler
{
    public function getId(): string
    {
        return 'php_array_to_json';
    }

    public function getName(): string
    {
        return 'PHP Array to JSON';
    }

    public function execute(array $inputs, array $config = []): array
    {
        $raw = trim((string)($inputs['input'] ?? $inputs['code'] ?? ''));

        if ($raw === '') {
            return [
                'success' => false,
                'error'   => 'PHP array code cannot be empty.',
                'type'    => ResultType::JSON,
                'data'    => null,
                'value'   => null,
            ];
        }

        // Clean up common wrapping like $var = ...; or return ...;
        $clean = preg_replace('/^\s*<\?(?:php)?/i', '', $raw);
        $clean = preg_replace('/^\s*(?:return|\$[a-zA-Z0-9_]+\s*=)\s*/i', '', $clean);
        $clean = rtrim($clean, "; \t\n\r\0\x0B");

        try {
            $parsedArray = $this->parsePhpArraySafely($clean);
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error'   => 'Failed to parse PHP array: ' . $e->getMessage(),
                'type'    => ResultType::JSON,
                'data'    => null,
                'value'   => null,
            ];
        }

        $pretty = !empty($inputs['pretty']) || !isset($inputs['pretty']);
        $flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
        if ($pretty) {
            $flags |= JSON_PRETTY_PRINT;
        }

        $json = json_encode($parsedArray, $flags);

        return [
            'success' => true,
            'type'    => ResultType::JSON,
            'data'    => $parsedArray,
            'value'   => $json,
            'meta'    => [
                'item_count' => is_array($parsedArray) ? count($parsedArray) : 0,
            ],
        ];
    }

    /**
     * Safely parse a PHP array definition using token_get_all().
     * Absolutely NO eval() is used.
     */
    protected function parsePhpArraySafely(string $code): array
    {
        $tokens = token_get_all("<?php " . $code . ";");
        // Filter out opening tag, whitespaces and comments
        $filtered = [];
        foreach ($tokens as $t) {
            if (is_array($t)) {
                if (in_array($t[0], [T_OPEN_TAG, T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                    continue;
                }
                $filtered[] = $t;
            } else {
                $trimmed = trim($t);
                if ($trimmed !== '') {
                    $filtered[] = $trimmed;
                }
            }
        }

        if (empty($filtered)) {
            return [];
        }

        $pos = 0;
        $result = $this->parseValue($filtered, $pos);
        if (!is_array($result)) {
            throw new \RuntimeException('Expression did not evaluate to a PHP array.');
        }

        return $result;
    }

    protected function parseValue(array $tokens, int &$pos)
    {
        if ($pos >= count($tokens)) {
            throw new \RuntimeException('Unexpected end of input.');
        }

        $tok = $tokens[$pos];

        // Check for array start: '[' or 'array' followed by '('
        if ($tok === '[') {
            $pos++;
            return $this->parseArrayBody($tokens, $pos, ']');
        }

        if (is_array($tok) && $tok[0] === T_ARRAY) {
            $pos++;
            if ($pos < count($tokens) && $tokens[$pos] === '(') {
                $pos++;
                return $this->parseArrayBody($tokens, $pos, ')');
            }
            throw new \RuntimeException('Expected "(" after "array".');
        }

        // Unary minus/plus for numbers
        if ($tok === '-' || $tok === '+') {
            $sign = $tok === '-' ? -1 : 1;
            $pos++;
            $next = $this->parseValue($tokens, $pos);
            if (is_numeric($next)) {
                return $sign * $next;
            }
            throw new \RuntimeException('Invalid operand after sign.');
        }

        // Literals
        if (is_array($tok)) {
            $type = $tok[0];
            $val = $tok[1];

            if ($type === T_CONSTANT_ENCAPSED_STRING) {
                $pos++;
                return $this->stripQuotes($val);
            }

            if ($type === T_LNUMBER) {
                $pos++;
                return (int)$val;
            }

            if ($type === T_DNUMBER) {
                $pos++;
                return (float)$val;
            }

            if ($type === T_STRING) {
                $lower = strtolower($val);
                if ($lower === 'true') {
                    $pos++;
                    return true;
                }
                if ($lower === 'false') {
                    $pos++;
                    return false;
                }
                if ($lower === 'null') {
                    $pos++;
                    return null;
                }
                throw new \RuntimeException("Disallowed or unknown identifier '{$val}'. Only literals allowed.");
            }

            throw new \RuntimeException("Disallowed token type: " . token_name($type));
        }

        throw new \RuntimeException("Unexpected token: {$tok}");
    }

    protected function parseArrayBody(array $tokens, int &$pos, string $closeChar): array
    {
        $arr = [];
        $implicitIndex = 0;

        while ($pos < count($tokens)) {
            if ($tokens[$pos] === $closeChar) {
                $pos++;
                return $arr;
            }

            // Parse key or value
            $first = $this->parseValue($tokens, $pos);

            // Check if followed by '=>'
            if ($pos < count($tokens) && is_array($tokens[$pos]) && $tokens[$pos][0] === T_DOUBLE_ARROW) {
                $pos++; // skip '=>'
                $value = $this->parseValue($tokens, $pos);
                $key = is_scalar($first) ? $first : (string)$first;
                $arr[$key] = $value;
                if (is_int($key) && $key >= $implicitIndex) {
                    $implicitIndex = $key + 1;
                }
            } else {
                // Was just a value
                $arr[$implicitIndex++] = $first;
            }

            // Check for comma or closing bracket
            if ($pos < count($tokens) && $tokens[$pos] === ',') {
                $pos++;
                // Check if closing bracket immediately after comma (trailing comma)
                if ($pos < count($tokens) && $tokens[$pos] === $closeChar) {
                    $pos++;
                    return $arr;
                }
            } elseif ($pos < count($tokens) && $tokens[$pos] === $closeChar) {
                $pos++;
                return $arr;
            } else {
                if ($pos < count($tokens) && $tokens[$pos] === ';') {
                    break;
                }
                throw new \RuntimeException("Expected ',' or '{$closeChar}' in array declaration.");
            }
        }

        return $arr;
    }

    protected function stripQuotes(string $str): string
    {
        $quote = $str[0];
        $inner = substr($str, 1, -1);
        if ($quote === "'") {
            return str_replace(["\\'", "\\\\"], ["'", "\\"], $inner);
        }
        return stripcslashes($inner);
    }
}

