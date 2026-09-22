<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Handlers;

use FavoriteCMS\Tools\Support\ResultType;
use InvalidArgumentException;

class Base64DecoderHandler extends AbstractToolHandler
{
    public function getId(): string
    {
        return 'base64_decoder';
    }

    public function getName(): string
    {
        return 'Base64 Decoder';
    }

    public function execute(array $inputs, array $config = []): array
    {
        $raw = trim((string)($inputs['input'] ?? $inputs['text'] ?? ''));
        if ($raw === '') {
            throw new InvalidArgumentException('Please provide base64 text to decode.');
        }

        // Support URL safe base64
        $normalized = str_replace(['-', '_'], ['+', '/'], $raw);
        $mod4 = strlen($normalized) % 4;
        if ($mod4 > 0) {
            $normalized .= str_repeat('=', 4 - $mod4);
        }

        $decoded = base64_decode($normalized, true);
        if ($decoded === false) {
            throw new InvalidArgumentException('Input contains invalid base64 characters.');
        }

        return [
            'success' => true,
            'type'    => ResultType::TEXT,
            'data'    => $decoded,
            'value'   => $decoded,
            'meta'    => [
                'encoded_length' => strlen($raw),
                'decoded_length' => strlen($decoded),
            ],
        ];
    }
}

