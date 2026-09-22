<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Handlers;

use FavoriteCMS\Tools\Support\ResultType;

class TextCaseConverterHandler extends AbstractToolHandler
{
    public function getId(): string
    {
        return 'text_case_converter';
    }

    public function getName(): string
    {
        return 'Text Case Converter';
    }

    public function execute(array $inputs, array $config = []): array
    {
        $text = (string)($inputs['input'] ?? $inputs['text'] ?? '');
        $target = strtolower(trim((string)($inputs['mode'] ?? $inputs['case'] ?? 'title')));

        $converted = match ($target) {
            'upper', 'uppercase' => mb_strtoupper($text, 'UTF-8'),
            'lower', 'lowercase' => mb_strtolower($text, 'UTF-8'),
            'title', 'titlecase' => mb_convert_case($text, MB_CASE_TITLE, 'UTF-8'),
            'sentence'           => $this->toSentenceCase($text),
            'camel', 'camelcase' => $this->toCamelCase($text),
            'snake', 'snakecase' => $this->toSnakeCase($text),
            'kebab', 'kebabcase' => $this->toKebabCase($text),
            'constant'           => mb_strtoupper($this->toSnakeCase($text), 'UTF-8'),
            default              => mb_convert_case($text, MB_CASE_TITLE, 'UTF-8'),
        };

        return [
            'success' => true,
            'type'    => ResultType::TEXT,
            'data'    => $converted,
            'value'   => $converted,
            'meta'    => [
                'target_case' => $target,
            ],
        ];
    }

    private function toSentenceCase(string $text): string
    {
        $sentences = preg_split('/([.?!]\s+)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        $result = '';
        foreach ($sentences as $segment) {
            $result .= mb_strtoupper(mb_substr($segment, 0, 1, 'UTF-8'), 'UTF-8') . mb_strtolower(mb_substr($segment, 1, null, 'UTF-8'), 'UTF-8');
        }
        return $result;
    }

    private function toCamelCase(string $text): string
    {
        $words = preg_split('/[\s_\-]+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        if (empty($words)) {
            return '';
        }
        $first = mb_strtolower(array_shift($words), 'UTF-8');
        $rest = array_map(fn($w) => mb_convert_case($w, MB_CASE_TITLE, 'UTF-8'), $words);
        return $first . implode('', $rest);
    }

    private function toSnakeCase(string $text): string
    {
        $text = preg_replace('/([a-z])([A-Z])/u', '$1_$2', $text);
        $text = preg_replace('/[\s\-]+/u', '_', $text);
        return mb_strtolower(trim($text, '_'), 'UTF-8');
    }

    private function toKebabCase(string $text): string
    {
        $text = preg_replace('/([a-z])([A-Z])/u', '$1-$2', $text);
        $text = preg_replace('/[\s_]+/u', '-', $text);
        return mb_strtolower(trim($text, '-'), 'UTF-8');
    }
}

