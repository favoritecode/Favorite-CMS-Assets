<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Handlers;

use FavoriteCMS\Tools\Support\ResultType;

class DuplicateLinesRemoverHandler extends AbstractToolHandler
{
    public function getId(): string
    {
        return 'duplicate_lines_remover';
    }

    public function getName(): string
    {
        return 'Duplicate Lines Remover';
    }

    public function execute(array $inputs, array $config = []): array
    {
        $text = (string)($inputs['input'] ?? $inputs['text'] ?? '');
        $caseSensitive = !empty($inputs['case_sensitive']);
        $trimLines = !empty($inputs['trim_lines']);

        $lines = preg_split('/\r\n|\r|\n/', $text);
        $totalOriginal = count($lines);

        $seen = [];
        $unique = [];

        foreach ($lines as $line) {
            $keyLine = $trimLines ? trim($line) : $line;
            $checkKey = $caseSensitive ? $keyLine : mb_strtolower($keyLine, 'UTF-8');

            if (!isset($seen[$checkKey])) {
                $seen[$checkKey] = true;
                $unique[] = $trimLines ? $keyLine : $line;
            }
        }

        $result = implode("\n", $unique);
        $removed = $totalOriginal - count($unique);

        return [
            'success' => true,
            'type'    => ResultType::TEXT,
            'data'    => $result,
            'value'   => $result,
            'meta'    => [
                'original_lines' => $totalOriginal,
                'unique_lines'   => count($unique),
                'removed_lines'  => $removed,
            ],
        ];
    }
}

