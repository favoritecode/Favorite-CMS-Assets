<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Handlers;

use FavoriteCMS\Tools\Support\ResultType;

class QueryStringParserHandler extends AbstractToolHandler
{
    public function getId(): string
    {
        return 'query_string_parser';
    }

    public function getName(): string
    {
        return 'Query String Parser';
    }

    public function execute(array $inputs, array $config = []): array
    {
        $raw = trim((string)($inputs['input'] ?? $inputs['url'] ?? $inputs['query'] ?? ''));

        if ($raw === '') {
            return [
                'success' => false,
                'error'   => 'URL or query string cannot be empty.',
                'type'    => ResultType::JSON,
                'data'    => null,
                'value'   => null,
            ];
        }

        // If a full URL is provided, extract the query part
        $queryString = $raw;
        $urlParts = parse_url($raw);
        if (is_array($urlParts) && isset($urlParts['query'])) {
            $queryString = $urlParts['query'];
        } elseif (strpos($raw, '?') !== false) {
            $parts = explode('?', $raw, 2);
            $queryString = $parts[1];
        }

        // Remove fragment if present
        if (strpos($queryString, '#') !== false) {
            $parts = explode('#', $queryString, 2);
            $queryString = $parts[0];
        }

        $parsed = [];
        parse_str($queryString, $parsed);

        $result = [
            'raw_query'  => $queryString,
            'parameters' => $parsed,
            'count'      => count($parsed),
        ];

        if (is_array($urlParts) && (isset($urlParts['scheme']) || isset($urlParts['host']) || isset($urlParts['path']))) {
            $result['url_components'] = [
                'scheme'   => $urlParts['scheme'] ?? null,
                'host'     => $urlParts['host'] ?? null,
                'port'     => $urlParts['port'] ?? null,
                'path'     => $urlParts['path'] ?? null,
                'fragment' => $urlParts['fragment'] ?? null,
            ];
        }

        return [
            'success' => true,
            'type'    => ResultType::JSON,
            'data'    => $result,
            'value'   => json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            'meta'    => [
                'param_count' => count($parsed),
                'has_url'     => isset($result['url_components']),
            ],
        ];
    }
}

