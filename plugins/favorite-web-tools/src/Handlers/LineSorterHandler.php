<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Handlers;

use FavoriteCMS\Tools\Support\ResultType;

class LineSorterHandler extends AbstractToolHandler
{
    public function getId(): string
    {
        return 'line_sorter';
    }

    public function getName(): string
    {
        return 'Line Sorter';
    }

    public function execute(array $inputs, array $config = []): array
    {
        $text = (string)($inputs['input'] ?? $inputs['text'] ?? '');
        $order = strtolower(trim((string)($inputs['order'] ?? 'asc'))); // 'asc', 'desc', 'natural', 'length'
        $ignoreCase = !empty($inputs['ignore_case']);
        $removeEmpty = !empty($inputs['remove_empty']);

        $lines = preg_split('/\r\n|\r|\n/', $text);

        if ($removeEmpty) {
            $lines = array_values(array_filter($lines, fn($l) => trim($l) !== ''));
        }

        if ($order === 'natural') {
            natcasesort($lines);
            $lines = array_values($lines);
        } elseif ($order === 'length') {
            usort($lines, function ($a, $b) {
                return strlen($a) <=> strlen($b);
            });
        } elseif ($order === 'desc') {
            if ($ignoreCase) {
                usort($lines, fn($a, $b) => strcasecmp($b, $a));
            } else {
                rsort($lines, SORT_STRING);
            }
        } else {
            // asc
            if ($ignoreCase) {
                usort($lines, fn($a, $b) => strcasecmp($a, $b));
            } else {
                sort($lines, SORT_STRING);
            }
        }

        $result = implode("\n", $lines);

        return [
            'success' => true,
            'type'    => ResultType::TEXT,
            'data'    => $result,
            'value'   => $result,
            'meta'    => [
                'count' => count($lines),
                'order' => $order,
            ],
        ];
    }
}

