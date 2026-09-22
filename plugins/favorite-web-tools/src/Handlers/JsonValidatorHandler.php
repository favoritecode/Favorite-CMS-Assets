<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Handlers;

use FavoriteCMS\Tools\Support\ResultType;

class JsonValidatorHandler extends AbstractToolHandler
{
    public function getId(): string
    {
        return 'json_validator';
    }

    public function getName(): string
    {
        return 'JSON Validator';
    }

    public function execute(array $inputs, array $config = []): array
    {
        $raw = (string)($inputs['input'] ?? $inputs['json'] ?? $inputs['text'] ?? '');
        if (trim($raw) === '') {
            $data = [
                'valid'   => false,
                'error'   => 'Empty JSON input.',
                'message' => 'Please provide JSON text to validate.',
            ];
            return [
                'success' => true,
                'type'    => ResultType::JSON,
                'data'    => $data,
                'value'   => $data,
            ];
        }

        $decoded = json_decode($raw, true);
        $lastError = json_last_error();

        if ($lastError === JSON_ERROR_NONE) {
            $type = gettype($decoded);
            $keyCount = is_array($decoded) ? count($decoded) : 1;

            $data = [
                'valid'     => true,
                'message'   => 'Valid JSON!',
                'root_type' => $type,
                'elements'  => $keyCount,
                'size'      => strlen($raw),
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
            'error'   => json_last_error_msg(),
            'code'    => $lastError,
            'message' => 'Invalid JSON: ' . json_last_error_msg(),
        ];

        return [
            'success' => true,
            'type'    => ResultType::JSON,
            'data'    => $data,
            'value'   => $data,
        ];
    }
}

