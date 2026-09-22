<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Handlers;

use FavoriteCMS\Tools\Contracts\ToolHandlerInterface;
use FavoriteCMS\Tools\Support\ResultType;
use InvalidArgumentException;

class PhpMinifierHandler extends AbstractToolHandler
{
    public function getId(): string
    {
        return 'php_minifier';
    }

    public function getName(): string
    {
        return 'PHP Code Minifier';
    }

    public function execute(array $inputs, array $config = []): array
    {
        $code = (string)($inputs['input'] ?? $inputs['code'] ?? $inputs['text'] ?? '');
        if (trim($code) === '') {
            throw new InvalidArgumentException('Please provide PHP code to minify.');
        }

        $minified = $this->minifyPhp($code);
        $saved = max(0, strlen($code) - strlen($minified));
        $percent = strlen($code) > 0 ? round(($saved / strlen($code)) * 100, 1) : 0;

        return [
            'success' => true,
            'type'    => ResultType::TEXT,
            'data'    => $minified,
            'value'   => $minified,
            'meta'    => [
                'original_size' => strlen($code),
                'minified_size' => strlen($minified),
                'saved_bytes'   => $saved,
                'saved_percent' => $percent . '%',
            ],
        ];
    }

    private function minifyPhp(string $code): string
    {
        $hasPhpTag = str_contains($code, '<?php') || str_contains($code, '<?=');
        $input = $hasPhpTag ? $code : "<?php\n" . $code;

        $tokens = @token_get_all($input);
        if (empty($tokens)) {
            return $code;
        }

        $output = '';
        $lastToken = null;

        foreach ($tokens as $token) {
            if (is_string($token)) {
                $output .= $token;
                $lastToken = $token;
                continue;
            }

            [$id, $text] = $token;

            if ($id === T_COMMENT || $id === T_DOC_COMMENT) {
                continue;
            }

            if ($id === T_WHITESPACE) {
                // Determine if a single space is needed between words/identifiers
                if (is_array($lastToken)) {
                    $lastId = $lastToken[0];
                    if (in_array($lastId, [T_VARIABLE, T_STRING, T_ECHO, T_PRINT, T_RETURN, T_FUNCTION, T_CLASS, T_CONST, T_NEW, T_CASE, T_USE, T_NAMESPACE, T_FINAL, T_STATIC, T_PUBLIC, T_PROTECTED, T_PRIVATE], true)) {
                        $output .= ' ';
                    }
                }
                continue;
            }

            $output .= $text;
            $lastToken = $token;
        }

        $clean = trim($output);
        if (!$hasPhpTag && str_starts_with($clean, "<?php")) {
            $clean = trim(substr($clean, 5));
        }

        return $clean;
    }
}
