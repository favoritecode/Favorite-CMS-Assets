<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Handlers;

use FavoriteCMS\Tools\Support\ResultType;
use InvalidArgumentException;

class JsonFormatterHandler extends AbstractToolHandler
{
    public function getId(): string
    {
        return 'json_formatter';
    }

    public function getName(): string
    {
        return 'JSON Formatter & Beautifier';
    }

    public function execute(array $inputs, array $config = []): array
    {
        $raw = (string)($inputs['input'] ?? $inputs['json'] ?? $inputs['text'] ?? '');
        if (trim($raw) === '') {
            throw new InvalidArgumentException('Please provide JSON input to format.');
        }

        $decoded = json_decode($raw);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('Invalid JSON: ' . json_last_error_msg());
        }

        $formatted = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return [
            'success' => true,
            'type'    => ResultType::JSON,
            'data'    => $formatted,
            'value'   => $formatted,
            'meta'    => [
                'original_size'  => strlen($raw),
                'formatted_size' => strlen($formatted),
            ],
        ];
    }
}

