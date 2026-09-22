<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Handlers;

use FavoriteCMS\Tools\Support\ResultType;

class Base64EncoderHandler extends AbstractToolHandler
{
    public function getId(): string
    {
        return 'base64_encoder';
    }

    public function getName(): string
    {
        return 'Base64 Encoder';
    }

    public function execute(array $inputs, array $config = []): array
    {
        $raw = (string)($inputs['input'] ?? $inputs['text'] ?? '');
        $urlSafe = !empty($inputs['url_safe']);

        $encoded = base64_encode($raw);
        if ($urlSafe) {
            $encoded = str_replace(['+', '/', '='], ['-', '_', ''], $encoded);
        }

        return [
            'success' => true,
            'type'    => ResultType::TEXT,
            'data'    => $encoded,
            'value'   => $encoded,
            'meta'    => [
                'original_length' => strlen($raw),
                'encoded_length'  => strlen($encoded),
                'url_safe'        => $urlSafe,
            ],
        ];
    }
}

