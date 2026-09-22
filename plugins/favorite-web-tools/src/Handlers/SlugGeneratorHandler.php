<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Handlers;

use FavoriteCMS\Tools\Support\ResultType;

class SlugGeneratorHandler extends AbstractToolHandler
{
    public function getId(): string
    {
        return 'slug_generator';
    }

    public function getName(): string
    {
        return 'Slug Generator';
    }

    public function execute(array $inputs, array $config = []): array
    {
        $raw = (string)($inputs['input'] ?? $inputs['text'] ?? $inputs['title'] ?? '');
        $separator = (string)($inputs['separator'] ?? $config['separator'] ?? '-');
        if (!in_array($separator, ['-', '_', '.'], true)) {
            $separator = '-';
        }

        $lowercase = !isset($inputs['lowercase']) || !empty($inputs['lowercase']);
        $maxLength = isset($inputs['max_length']) && is_numeric($inputs['max_length']) ? (int)$inputs['max_length'] : 0;

        $slug = $this->createSlug($raw, $separator, $lowercase, $maxLength);

        return [
            'success' => true,
            'type'    => ResultType::TEXT,
            'data'    => $slug,
            'value'   => $slug,
            'meta'    => [
                'original_length' => mb_strlen($raw),
                'slug_length'     => strlen($slug),
                'separator'       => $separator,
            ],
        ];
    }

    protected function createSlug(string $text, string $separator = '-', bool $lowercase = true, int $maxLength = 0): string
    {
        // Transliterate accented characters if iconv or Transliterator is available
        if (function_exists('transliterator_transliterate')) {
            $trans = transliterator_transliterate('Any-Latin; Latin-ASCII; [\u0080-\u7fff] remove', $text);
            if ($trans !== false) {
                $text = $trans;
            }
        } elseif (function_exists('iconv')) {
            $iconv = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
            if ($iconv !== false) {
                $text = $iconv;
            }
        }

        // Replace non-alphanumeric characters with separator
        $slug = preg_replace('/[^a-zA-Z0-9]+/', $separator, $text);

        // Trim leading and trailing separators
        $slug = trim($slug, $separator);

        if ($lowercase) {
            $slug = strtolower($slug);
        }

        if ($maxLength > 0 && strlen($slug) > $maxLength) {
            $slug = substr($slug, 0, $maxLength);
            $slug = rtrim($slug, $separator);
        }

        return $slug;
    }
}

