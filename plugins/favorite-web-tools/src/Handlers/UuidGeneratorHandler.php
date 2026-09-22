<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Handlers;

use FavoriteCMS\Tools\Support\ResultType;

class UuidGeneratorHandler extends AbstractToolHandler
{
    public function getId(): string
    {
        return 'uuid_generator';
    }

    public function getName(): string
    {
        return 'UUID v4 Generator';
    }

    public function execute(array $inputs, array $config = []): array
    {
        $count = max(1, min(100, (int)($inputs['count'] ?? 1)));
        $uppercase = !empty($inputs['uppercase']);
        $hyphens = !isset($inputs['hyphens']) || !empty($inputs['hyphens']);

        $uuids = [];
        for ($i = 0; $i < $count; $i++) {
            $data = random_bytes(16);
            $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // version 4
            $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // variant RFC 4122

            $hex = bin2hex($data);
            if ($hyphens) {
                $uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split($hex, 4));
            } else {
                $uuid = $hex;
            }

            if ($uppercase) {
                $uuid = strtoupper($uuid);
            }

            $uuids[] = $uuid;
        }

        $resultText = implode("\n", $uuids);

        return [
            'success' => true,
            'type'    => ResultType::TEXT,
            'value'   => $resultText,
            'data'    => [
                'uuids' => $uuids,
                'count' => count($uuids),
                'text'  => $resultText,
            ],
            'meta'    => [
                'count' => count($uuids),
                'uuids' => $uuids,
            ],
        ];
    }
}

