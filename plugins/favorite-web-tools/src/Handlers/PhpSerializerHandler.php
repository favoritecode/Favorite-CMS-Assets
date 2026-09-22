<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Handlers;

use FavoriteCMS\Tools\Support\ResultType;

class PhpSerializerHandler extends AbstractToolHandler
{
    public function getId(): string
    {
        return 'php_serializer';
    }

    public function getName(): string
    {
        return 'PHP Serializer / Unserializer';
    }

    public function execute(array $inputs, array $config = []): array
    {
        $raw = trim((string)($inputs['input'] ?? $inputs['data'] ?? ''));
        $action = strtolower((string)($inputs['action'] ?? $config['action'] ?? 'serialize'));

        if ($raw === '') {
            return [
                'success' => false,
                'error'   => 'Input string cannot be empty.',
                'type'    => ResultType::TEXT,
                'data'    => null,
                'value'   => null,
            ];
        }

        if ($action === 'unserialize' || (!isset($inputs['action']) && (str_starts_with($raw, 'a:') || str_starts_with($raw, 's:') || str_starts_with($raw, 'i:') || str_starts_with($raw, 'b:') || str_starts_with($raw, 'd:') || str_starts_with($raw, 'O:')))) {
            // Unserialize operation with strict security: NO classes allowed
            // This prevents PHP Object Injection completely.
            $unserialized = @unserialize($raw, ['allowed_classes' => false]);
            if ($unserialized === false && $raw !== 'b:0;') {
                return [
                    'success' => false,
                    'error'   => 'Invalid PHP serialized string or malformed format.',
                    'type'    => ResultType::JSON,
                    'data'    => null,
                    'value'   => null,
                ];
            }

            return [
                'success' => true,
                'type'    => ResultType::JSON,
                'data'    => $unserialized,
                'value'   => json_encode($unserialized, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                'meta'    => [
                    'mode' => 'unserialize',
                    'type' => gettype($unserialized),
                ],
            ];
        }

        // Serialize operation
        // If input is valid JSON, decode it first so we serialize the native array/object
        $dataToSerialize = $raw;
        $decodedJson = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE && (is_array($decodedJson) || is_numeric($decodedJson) || is_bool($decodedJson))) {
            $dataToSerialize = $decodedJson;
        }

        $serialized = serialize($dataToSerialize);

        return [
            'success' => true,
            'type'    => ResultType::TEXT,
            'data'    => $serialized,
            'value'   => $serialized,
            'meta'    => [
                'mode'            => 'serialize',
                'original_type'   => gettype($dataToSerialize),
                'serialized_len'  => strlen($serialized),
            ],
        ];
    }
}

