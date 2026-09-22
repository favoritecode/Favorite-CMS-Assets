<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Handlers;

use FavoriteCMS\Tools\Support\ResultType;
use InvalidArgumentException;

class RegexTesterHandler extends AbstractToolHandler
{
    public function getId(): string
    {
        return 'regex_tester';
    }

    public function getName(): string
    {
        return 'Regular Expression Tester';
    }

    public function execute(array $inputs, array $config = []): array
    {
        $pattern = trim((string)($inputs['pattern'] ?? ''));
        $subject = (string)($inputs['input'] ?? $inputs['text'] ?? $inputs['subject'] ?? '');

        if ($pattern === '') {
            throw new InvalidArgumentException('Please provide a regular expression pattern.');
        }

        // Add delimiters if missing
        if (!preg_match('#^/.*?[a-z]*$#is', $pattern) && !preg_match('#^#.*?#[a-z]*$#is', $pattern) && !preg_match('#^~.*?~[a-z]*$#is', $pattern)) {
            $flags = (string)($inputs['flags'] ?? 'u');
            $pattern = '/' . str_replace('/', '\/', $pattern) . '/' . $flags;
        }

        // Test regex safely
        $matches = [];
        $error = null;
        set_error_handler(function ($severity, $message) use (&$error) {
            $error = $message;
        });

        $res = @preg_match_all($pattern, $subject, $matches, PREG_OFFSET_CAPTURE);
        restore_error_handler();

        if ($error !== null || $res === false) {
            $data = [
                'valid'   => false,
                'error'   => $error ?? 'Invalid regular expression.',
                'pattern' => $pattern,
                'matches' => [],
                'count'   => 0,
            ];
            return [
                'success' => true,
                'type'    => ResultType::JSON,
                'data'    => $data,
                'value'   => $data,
            ];
        }

        $formattedMatches = [];
        if (!empty($matches[0])) {
            foreach ($matches[0] as $idx => $match) {
                $groups = [];
                foreach ($matches as $grpIdx => $grpList) {
                    if ($grpIdx > 0 && isset($grpList[$idx])) {
                        $groups[$grpIdx] = $grpList[$idx][0];
                    }
                }
                $formattedMatches[] = [
                    'index'  => $idx + 1,
                    'match'  => $match[0],
                    'offset' => $match[1],
                    'groups' => $groups,
                ];
            }
        }

        $data = [
            'valid'   => true,
            'pattern' => $pattern,
            'count'   => $res,
            'matches' => $formattedMatches,
        ];

        return [
            'success' => true,
            'type'    => ResultType::JSON,
            'data'    => $data,
            'value'   => $data,
        ];
    }
}

