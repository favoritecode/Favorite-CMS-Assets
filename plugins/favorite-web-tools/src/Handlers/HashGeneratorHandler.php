<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Handlers;

use FavoriteCMS\Tools\Support\ResultType;

class HashGeneratorHandler extends AbstractToolHandler
{
    public function getId(): string
    {
        return 'hash_generator';
    }

    public function getName(): string
    {
        return 'Hash Generator';
    }

    public function execute(array $inputs, array $config = []): array
    {
        $text = (string)($inputs['input'] ?? $inputs['text'] ?? '');
        $algo = strtolower(trim((string)($inputs['algorithm'] ?? 'all')));

        $available = [
            'md5'    => md5($text),
            'sha1'   => sha1($text),
            'sha256' => hash('sha256', $text),
            'sha512' => hash('sha512', $text),
            'crc32'  => hash('crc32b', $text),
        ];

        if ($algo !== 'all' && isset($available[$algo])) {
            return [
                'success' => true,
                'type'    => ResultType::TEXT,
                'data'    => [
                    'hash'      => $available[$algo],
                    'algorithm' => $algo,
                ],
                'value'   => $available[$algo],
                'meta'    => [
                    'algorithm' => $algo,
                    'hash'      => $available[$algo],
                ],
            ];
        }

        $lines = [];
        foreach ($available as $k => $v) {
            $lines[] = strtoupper($k) . ": " . $v;
        }

        return [
            'success' => true,
            'type'    => ResultType::TEXT,
            'data'    => $available,
            'value'   => implode("\n", $lines),
            'meta'    => $available,
        ];
    }
}

