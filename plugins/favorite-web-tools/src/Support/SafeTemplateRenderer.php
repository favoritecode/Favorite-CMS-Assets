<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Support;

class SafeTemplateRenderer
{
    /**
     * Render a template with the given context.
     * All output variables are strictly HTML-escaped.
     * Dedicated URL bindings (e.g. download_url, thumbnail) are validated against safe schemes.
     * No raw HTML bypass syntax ({{{...}}} or {{&...}}) is permitted.
     */
    public static function render(string $template, array $context): string
    {
        // Disallow/neutralize raw template bypass tokens {{{ }}} or {{& }}
        $template = preg_replace('/\{\{\{\s*([a-zA-Z0-9_\-\.]+)\s*\}\}\}/', '{{$1}}', $template);
        $template = preg_replace('/\{\{&\s*([a-zA-Z0-9_\-\.]+)\s*\}\}/', '{{$1}}', $template);

        $tokens = preg_split('/(\{\{.*?\}\})/s', $template, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        if ($tokens === false || empty($tokens)) {
            return $template;
        }

        $ast = self::parseTokens($tokens, null);
        return self::renderNodes($ast, $context);
    }

    private static function parseTokens(array &$tokens, ?string $stopTag): array
    {
        $nodes = [];

        while (!empty($tokens)) {
            $token = array_shift($tokens);

            if (!str_starts_with($token, '{{') || !str_ends_with($token, '}}')) {
                $nodes[] = ['type' => 'text', 'value' => $token];
                continue;
            }

            $tag = trim(substr($token, 2, -2));

            // Comment
            if (str_starts_with($tag, '!')) {
                continue;
            }

            // Closing tag
            if (str_starts_with($tag, '/')) {
                $closeName = trim(substr($tag, 1));
                if ($stopTag !== null) {
                    if ($closeName === $stopTag || $stopTag === '*' ||
                        in_array($closeName, ['if', 'unless', 'loop', 'each'], true)) {
                        return $nodes;
                    }
                }
                continue;
            }

            // Else tag
            if ($tag === 'else' || $tag === '^') {
                if ($stopTag !== null) {
                    array_unshift($tokens, $token);
                    return $nodes;
                }
                continue;
            }

            // Block opening: {{#if expr}}
            if (preg_match('/^#if\s+(.+)$/s', $tag, $m)) {
                $expr = trim($m[1]);
                $thenNodes = self::parseTokens($tokens, 'if');
                $elseNodes = [];
                if (!empty($tokens) && in_array(trim(substr($tokens[0], 2, -2)), ['else', '^'], true)) {
                    array_shift($tokens);
                    $elseNodes = self::parseTokens($tokens, 'if');
                }
                $nodes[] = [
                    'type'     => 'if',
                    'expr'     => $expr,
                    'then'     => $thenNodes,
                    'else'     => $elseNodes,
                    'inverted' => false,
                ];
                continue;
            }

            // Block opening: {{#unless expr}}
            if (preg_match('/^#unless\s+(.+)$/s', $tag, $m)) {
                $expr = trim($m[1]);
                $thenNodes = self::parseTokens($tokens, 'unless');
                $elseNodes = [];
                if (!empty($tokens) && in_array(trim(substr($tokens[0], 2, -2)), ['else', '^'], true)) {
                    array_shift($tokens);
                    $elseNodes = self::parseTokens($tokens, 'unless');
                }
                $nodes[] = [
                    'type'     => 'if',
                    'expr'     => $expr,
                    'then'     => $thenNodes,
                    'else'     => $elseNodes,
                    'inverted' => true,
                ];
                continue;
            }

            // Inverted section: {{^expr}}
            if (str_starts_with($tag, '^')) {
                $expr = trim(substr($tag, 1));
                $thenNodes = self::parseTokens($tokens, $expr);
                $nodes[] = [
                    'type' => 'inverted_section',
                    'expr' => $expr,
                    'body' => $thenNodes,
                ];
                continue;
            }

            // Section or loop: {{#loop expr}} or {{#each expr}} or {{#expr}}
            if (str_starts_with($tag, '#')) {
                $inner = trim(substr($tag, 1));
                $closeTag = $inner;
                $expr = $inner;
                if (preg_match('/^(?:loop|each)\s+(.+)$/s', $inner, $m)) {
                    $expr = trim($m[1]);
                    $closeTag = '*';
                }

                $bodyNodes = self::parseTokens($tokens, $closeTag);
                $elseNodes = [];
                if (!empty($tokens) && in_array(trim(substr($tokens[0], 2, -2)), ['else', '^'], true)) {
                    array_shift($tokens);
                    $elseNodes = self::parseTokens($tokens, $closeTag);
                }

                $nodes[] = [
                    'type' => 'section',
                    'expr' => $expr,
                    'body' => $bodyNodes,
                    'else' => $elseNodes,
                ];
                continue;
            }

            // Simple variable
            $nodes[] = [
                'type' => 'var',
                'key'  => $tag,
            ];
        }

        return $nodes;
    }

    private static function renderNodes(array $nodes, array $context): string
    {
        $out = '';
        foreach ($nodes as $node) {
            switch ($node['type']) {
                case 'text':
                    $out .= $node['value'];
                    break;

                case 'var':
                    $key = $node['key'];
                    $val = self::resolveValue($key, $context);
                    if ($val === null || is_array($val)) {
                        break;
                    }
                    if (self::isUrlBinding($key)) {
                        $safe = self::validateSafeUrl((string)$val);
                        $out .= htmlspecialchars($safe, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                    } elseif (is_bool($val)) {
                        $out .= $val ? '1' : '0';
                    } else {
                        $out .= htmlspecialchars((string)$val, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                    }
                    break;

                case 'if':
                    $val = self::resolveValue($node['expr'], $context);
                    $cond = self::isTruthy($val);
                    if ($node['inverted']) {
                        $cond = !$cond;
                    }
                    if ($cond) {
                        $out .= self::renderNodes($node['then'], $context);
                    } else {
                        $out .= self::renderNodes($node['else'], $context);
                    }
                    break;

                case 'inverted_section':
                    $val = self::resolveValue($node['expr'], $context);
                    if (!self::isTruthy($val)) {
                        $out .= self::renderNodes($node['body'], $context);
                    }
                    break;

                case 'section':
                    $val = self::resolveValue($node['expr'], $context);
                    if (is_array($val)) {
                        if (empty($val)) {
                            $out .= self::renderNodes($node['else'], $context);
                            break;
                        }
                        if (!array_is_list($val)) {
                            $sub = array_merge($context, $val);
                            $out .= self::renderNodes($node['body'], $sub);
                            break;
                        }

                        $total = count($val);
                        foreach ($val as $i => $item) {
                            $itemContext = is_array($item) ? $item : ['value' => $item, '.' => $item];
                            $itemContext['@index'] = $i;
                            $itemContext['@number'] = $i + 1;
                            $itemContext['@first'] = ($i === 0);
                            $itemContext['@last'] = ($i === $total - 1);
                            $itemContext['@total'] = $total;
                            $merged = array_merge($context, $itemContext);
                            $out .= self::renderNodes($node['body'], $merged);
                        }
                    } elseif (self::isTruthy($val)) {
                        $out .= self::renderNodes($node['body'], $context);
                    } else {
                        $out .= self::renderNodes($node['else'], $context);
                    }
                    break;
            }
        }
        return $out;
    }

    private static function resolveValue(string $key, array $context): mixed
    {
        $key = trim($key);
        if ($key === '.' || $key === 'this') {
            return $context['value'] ?? ($context['.'] ?? null);
        }

        if (str_starts_with($key, '!')) {
            $inner = ltrim($key, '!');
            $val = self::resolveValue($inner, $context);
            return !self::isTruthy($val);
        }

        if (array_key_exists($key, $context)) {
            return $context[$key];
        }

        if (str_contains($key, '.')) {
            $parts = explode('.', $key);
            $curr = $context;
            foreach ($parts as $p) {
                if (is_array($curr) && array_key_exists($p, $curr)) {
                    $curr = $curr[$p];
                } else {
                    return null;
                }
            }
            return $curr;
        }

        return null;
    }

    private static function isTruthy(mixed $val): bool
    {
        if ($val === null) {
            return false;
        }
        if (is_bool($val)) {
            return $val;
        }
        if (is_array($val)) {
            return !empty($val);
        }
        if (is_string($val)) {
            $trimmed = trim($val);
            return $trimmed !== '' && $trimmed !== '0' && strtolower($trimmed) !== 'false';
        }
        if (is_numeric($val)) {
            return $val != 0;
        }
        return (bool) $val;
    }

    private static function isUrlBinding(string $key): bool
    {
        $k = strtolower(trim($key));
        return $k === 'download_url'
            || $k === 'url'
            || $k === 'thumbnail'
            || $k === 'thumb'
            || $k === 'src'
            || $k === 'href'
            || str_ends_with($k, '_url')
            || str_ends_with($k, '.download_url')
            || str_ends_with($k, '.url')
            || str_ends_with($k, '.thumbnail')
            || str_ends_with($k, '.src');
    }

    public static function validateSafeUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        // Must start with http://, https://, or root-relative / (but not //)
        if (preg_match('#^https?://#i', $url) || (str_starts_with($url, '/') && !str_starts_with($url, '//'))) {
            // Reject any quotes, angle brackets, or spaces
            if (preg_match('#[<>"\'\s]#', $url)) {
                return '';
            }
            // Reject data:, javascript:, vbscript: if hidden in query or fragments
            $lower = strtolower($url);
            if (str_contains($lower, 'javascript:') || str_contains($lower, 'data:') || str_contains($lower, 'vbscript:')) {
                return '';
            }
            return $url;
        }

        return '';
    }
}

