<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Handlers;

use FavoriteCMS\Tools\Support\ResultType;

class UrlEncoderHandler extends AbstractToolHandler
{
    public function getId(): string
    {
        return 'url_encoder';
    }

    public function getName(): string
    {
        return 'URL Encoder';
    }

    public function execute(array $inputs, array $config = []): array
    {
        $raw = (string)($inputs['input'] ?? $inputs['url'] ?? $inputs['text'] ?? '');
        $rfc3986 = !empty($inputs['rfc3986']);

        $encoded = $rfc3986 ? rawurlencode($raw) : urlencode($raw);

        return [
            'success' => true,
            'type'    => ResultType::TEXT,
            'data'    => $encoded,
            'value'   => $encoded,
            'meta'    => [
                'standard' => $rfc3986 ? 'RFC 3986 (rawurlencode)' : 'application/x-www-form-urlencoded (urlencode)',
            ],
        ];
    }
}

