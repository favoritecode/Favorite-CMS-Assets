<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Handlers;

use FavoriteCMS\Tools\Support\ResultType;

class UrlDecoderHandler extends AbstractToolHandler
{
    public function getId(): string
    {
        return 'url_decoder';
    }

    public function getName(): string
    {
        return 'URL Decoder';
    }

    public function execute(array $inputs, array $config = []): array
    {
        $raw = (string)($inputs['input'] ?? $inputs['url'] ?? $inputs['text'] ?? '');
        $rfc3986 = !empty($inputs['rfc3986']);

        $decoded = $rfc3986 ? rawurldecode($raw) : urldecode($raw);

        return [
            'success' => true,
            'type'    => ResultType::TEXT,
            'data'    => $decoded,
            'value'   => $decoded,
        ];
    }
}

