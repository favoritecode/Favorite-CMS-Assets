<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Handlers;

use FavoriteCMS\Tools\Support\ResultType;

class PhpValidatorHandler extends AbstractToolHandler
{
    public function getId(): string
    {
        return 'php_validator';
    }

    public function getName(): string
    {
        return 'PHP Syntax Validator';
    }

    public function execute(array $inputs, array $config = []): array
    {
        $code = (string)($inputs['input'] ?? $inputs['code'] ?? $inputs['text'] ?? '');
        if (trim($code) === '') {
            $data = [
                'valid'   => false,
                'error'   => 'Empty code input.',
                'message' => 'Please provide PHP code to validate.',
            ];
            return [
                'success' => true,
                'type'    => ResultType::JSON,
                'data'    => $data,
                'value'   => $data,
            ];
        }

        $hasPhpTag = str_contains($code, '<?php') || str_contains($code, '<?=');
        $checkCode = $hasPhpTag ? $code : "<?php\n" . $code;

        $isValid = true;
        $errorMsg = null;
        $errorLine = null;

        // Test with tokenizer first to check for token errors (PHP 8 throws ParseError on invalid tokens)
        try {
            $tokens = @token_get_all($checkCode, TOKEN_PARSE);
        } catch (\ParseError $e) {
            $isValid = false;
            $errorMsg = $e->getMessage();
            $errorLine = $e->getLine();
        }

        // Check matching braces
        if ($isValid) {
            $braces = 0;
            $brackets = 0;
            $parens = 0;
            $tokens = @token_get_all($checkCode);
            foreach ($tokens as $t) {
                if ($t === '{') $braces++;
                elseif ($t === '}') $braces--;
                elseif ($t === '[') $brackets++;
                elseif ($t === ']') $brackets--;
                elseif ($t === '(') $parens++;
                elseif ($t === ')') $parens--;

                if ($braces < 0 || $brackets < 0 || $parens < 0) {
                    $isValid = false;
                    $errorMsg = 'Unmatched closing bracket or parenthesis.';
                    break;
                }
            }
            if ($isValid && ($braces !== 0 || $brackets !== 0 || $parens !== 0)) {
                $isValid = false;
                $errorMsg = 'Unclosed bracket, brace, or parenthesis.';
            }
        }

        if ($isValid) {
            $data = [
                'valid'   => true,
                'message' => 'No syntax errors detected! PHP syntax is valid.',
                'tokens'  => count($tokens ?? []),
                'bytes'   => strlen($code),
            ];
            return [
                'success' => true,
                'type'    => ResultType::JSON,
                'data'    => $data,
                'value'   => $data,
            ];
        }

        $data = [
            'valid'   => false,
            'error'   => $errorMsg,
            'line'    => $errorLine,
            'message' => 'PHP Syntax Error: ' . $errorMsg . ($errorLine ? " on line {$errorLine}" : ''),
        ];

        return [
            'success' => true,
            'type'    => ResultType::JSON,
            'data'    => $data,
            'value'   => $data,
        ];
    }
}

