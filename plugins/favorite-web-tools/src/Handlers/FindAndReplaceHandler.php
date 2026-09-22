<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Handlers;

use FavoriteCMS\Tools\Support\ResultType;

class FindAndReplaceHandler extends AbstractToolHandler
{
    public function getId(): string
    {
        return 'find_and_replace';
    }

    public function getName(): string
    {
        return 'Find and Replace';
    }

    public function execute(array $inputs, array $config = []): array
    {
        $text = (string)($inputs['input'] ?? $inputs['text'] ?? '');
        $find = (string)($inputs['find'] ?? '');
        $replace = (string)($inputs['replace'] ?? '');
        $caseSensitive = !empty($inputs['case_sensitive']);
        $useRegex = !empty($inputs['use_regex']);

        $count = 0;

        if ($find === '') {
            return [
                'success' => true,
                'type'    => ResultType::TEXT,
                'data'    => $text,
                'value'   => $text,
                'meta'    => ['replacements' => 0],
            ];
        }

        if ($useRegex) {
            $pattern = $find;
            if (!preg_match('#^/.*?[a-z]*$#is', $pattern)) {
                $flags = $caseSensitive ? 'u' : 'iu';
                $pattern = '/' . str_replace('/', '\/', $pattern) . '/' . $flags;
            }

            $result = @preg_replace($pattern, $replace, $text, -1, $count);
            if ($result === null) {
                $result = $text;
                $count = 0;
            }
        } else {
            if ($caseSensitive) {
                $result = str_replace($find, $replace, $text, $count);
            } else {
                $result = str_ireplace($find, $replace, $text, $count);
            }
        }

        return [
            'success' => true,
            'type'    => ResultType::TEXT,
            'data'    => $result,
            'value'   => $result,
            'meta'    => [
                'replacements'   => $count,
                'case_sensitive' => $caseSensitive,
                'use_regex'      => $useRegex,
            ],
        ];
    }
}

