<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Handlers;

use FavoriteCMS\Tools\Support\ResultType;
use InvalidArgumentException;

class JsonMinifierHandler extends AbstractToolHandler
{
    public function getId(): string
    {
        return 'json_minifier';
    }

    public function getName(): string
    {
        return 'JSON Minifier';
    }

    public function execute(array $inputs, array $config = []): array
    {
        $raw = (string)($inputs['input'] ?? $inputs['json'] ?? $inputs['text'] ?? '');
        if (trim($raw) === '') {
            throw new InvalidArgumentException('Please provide JSON input to minify.');
        }

        $decoded = json_decode($raw);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('Invalid JSON: ' . json_last_error_msg());
        }

        $minified = json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $savedBytes = max(0, strlen($raw) - strlen($minified));
        $percentSaved = strlen($raw) > 0 ? round(($savedBytes / strlen($raw)) * 100, 1) : 0;

        return [
            'success' => true,
            'type'    => ResultType::TEXT,
            'data'    => $minified,
            'value'   => $minified,
            'meta'    => [
                'original_size' => strlen($raw),
                'minified_size' => strlen($minified),
                'saved_bytes'   => $savedBytes,
                'saved_percent' => $percentSaved . '%',
            ],
        ];
    }
}

