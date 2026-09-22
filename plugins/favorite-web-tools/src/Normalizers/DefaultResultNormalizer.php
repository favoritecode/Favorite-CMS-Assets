<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Normalizers;

class DefaultResultNormalizer implements ResultNormalizerInterface
{
    public function supports(string $type, array $data): bool
    {
        return true; // Default fallback
    }

    public function normalize(array $data): array
    {
        $normalized = [
            'success' => !empty($data['success']) || (!isset($data['error']) && !isset($data['failed'])),
            'title' => (string) ($data['title'] ?? $data['name'] ?? ''),
            'message' => (string) ($data['message'] ?? $data['description'] ?? ''),
            'raw_json' => json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            'normalized_type' => 'default',
        ];

        // 1. Direct output/result extraction
        if (isset($data['output']) && is_string($data['output'])) {
            $normalized['output'] = $data['output'];
        } elseif (isset($data['result']) && is_string($data['result'])) {
            $normalized['output'] = $data['result'];
        } elseif (isset($data['text']) && is_string($data['text'])) {
            $normalized['output'] = $data['text'];
        } else {
            $normalized['output'] = '';
        }

        // 2. Tabular/List items extraction
        $items = [];
        $candidates = ['items', 'rows', 'data', 'records', 'results', 'list'];
        foreach ($candidates as $key) {
            if (isset($data[$key]) && is_array($data[$key])) {
                $items = $data[$key];
                break;
            }
        }

        // If top-level array was sequential
        if (empty($items) && array_is_list($data)) {
            $items = $data;
        }

        $normalizedItems = [];
        foreach ($items as $idx => $item) {
            if (is_array($item)) {
                $row = $item;
                $row['@index'] = $idx;
                $row['@number'] = $idx + 1;
                $normalizedItems[] = $row;
            } elseif (is_scalar($item)) {
                $normalizedItems[] = [
                    'value' => (string) $item,
                    '@index' => $idx,
                    '@number' => $idx + 1,
                ];
            }
        }

        $normalized['items'] = $normalizedItems;
        $normalized['item_count'] = count($normalizedItems);
        $normalized['has_items'] = count($normalizedItems) > 0;

        // 3. Key-value fields extraction for cards or inspector
        $fields = [];
        foreach ($data as $k => $v) {
            if (in_array($k, ['items', 'rows', 'data', 'records', 'results', 'list'], true)) {
                continue;
            }
            if (is_scalar($v) || $v === null) {
                $fields[] = [
                    'key' => (string) $k,
                    'value' => (string) ($v ?? 'null'),
                ];
            }
        }
        $normalized['fields'] = $fields;
        $normalized['has_fields'] = count($fields) > 0;

        // Merge all top-level keys so templates can access them directly (e.g. {{my_custom_key}})
        foreach ($data as $k => $v) {
            if (!isset($normalized[$k])) {
                $normalized[$k] = $v;
            }
        }

        return $normalized;
    }
}

