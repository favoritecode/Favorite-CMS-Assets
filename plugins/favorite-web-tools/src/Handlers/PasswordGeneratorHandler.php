<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Handlers;

use FavoriteCMS\Tools\Support\ResultType;

class PasswordGeneratorHandler extends AbstractToolHandler
{
    public function getId(): string
    {
        return 'password_generator';
    }

    public function getName(): string
    {
        return 'Password Generator';
    }

    public function execute(array $inputs, array $config = []): array
    {
        $length = max(4, min(128, (int)($inputs['length'] ?? 16)));
        $useUpper = !isset($inputs['uppercase']) || !empty($inputs['uppercase']);
        $useLower = !isset($inputs['lowercase']) || !empty($inputs['lowercase']);
        $useNumbers = !isset($inputs['numbers']) || !empty($inputs['numbers']);
        $useSymbols = !isset($inputs['symbols']) || !empty($inputs['symbols']);
        $excludeAmbiguous = !empty($inputs['exclude_ambiguous']);
        $count = max(1, min(50, (int)($inputs['count'] ?? 1)));

        $upper = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lower = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $symbols = '!@#$%^&*()-_=+[]{}<>~;:';

        if ($excludeAmbiguous) {
            $upper = str_replace(['O', 'I'], '', $upper);
            $lower = str_replace(['o', 'i', 'l'], '', $lower);
            $numbers = str_replace(['0', '1'], '', $numbers);
        }

        $charset = '';
        $requiredPools = [];

        if ($useUpper) {
            $charset .= $upper;
            $requiredPools[] = $upper;
        }
        if ($useLower) {
            $charset .= $lower;
            $requiredPools[] = $lower;
        }
        if ($useNumbers) {
            $charset .= $numbers;
            $requiredPools[] = $numbers;
        }
        if ($useSymbols) {
            $charset .= $symbols;
            $requiredPools[] = $symbols;
        }

        if ($charset === '') {
            $charset = $lower . $numbers;
            $requiredPools[] = $lower;
            $requiredPools[] = $numbers;
        }

        $passwords = [];
        $charsetLen = strlen($charset);

        for ($c = 0; $c < $count; $c++) {
            $chars = [];

            // Ensure at least one character from each required pool
            foreach ($requiredPools as $pool) {
                $chars[] = $pool[random_int(0, strlen($pool) - 1)];
            }

            // Fill remaining length
            while (count($chars) < $length) {
                $chars[] = $charset[random_int(0, $charsetLen - 1)];
            }

            // Shuffle randomly
            for ($i = count($chars) - 1; $i > 0; $i--) {
                $j = random_int(0, $i);
                $tmp = $chars[$i];
                $chars[$i] = $chars[$j];
                $chars[$j] = $tmp;
            }

            $passwords[] = implode('', $chars);
        }

        // Calculate entropy for the password
        $poolSize = strlen($charset);
        $entropy = round($length * log($poolSize, 2), 1);

        $strength = match (true) {
            $entropy < 36 => 'Very Weak',
            $entropy < 60 => 'Weak',
            $entropy < 80 => 'Medium',
            $entropy < 100 => 'Strong',
            default => 'Very Strong',
        };

        $resultValue = $count === 1 ? $passwords[0] : implode("\n", $passwords);

        return [
            'success' => true,
            'type'    => ResultType::TEXT,
            'data'    => $resultValue,
            'value'   => $resultValue,
            'meta'    => [
                'password'     => $passwords[0],
                'passwords'    => $passwords,
                'count'        => count($passwords),
                'length'       => $length,
                'entropy_bits' => $entropy,
                'entropy'      => $entropy . ' bits',
                'strength'     => $strength,
            ],
        ];
    }
}
