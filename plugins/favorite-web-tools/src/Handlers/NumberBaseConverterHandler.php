<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Handlers;

use FavoriteCMS\Tools\Support\ResultType;
use InvalidArgumentException;

class NumberBaseConverterHandler extends AbstractToolHandler
{
    public function getId(): string
    {
        return 'number_base_converter';
    }

    public function getName(): string
    {
        return 'Number Base Converter';
    }

    public function execute(array $inputs, array $config = []): array
    {
        $raw = trim((string)($inputs['input'] ?? $inputs['number'] ?? ''));
        if ($raw === '') {
            throw new InvalidArgumentException('Please enter a number to convert.');
        }

        $fromBase = max(2, min(36, (int)($inputs['from_base'] ?? 10)));

        // Remove prefix hints if present
        $clean = strtolower($raw);
        if ($fromBase === 16 && str_starts_with($clean, '0x')) {
            $clean = substr($clean, 2);
        } elseif ($fromBase === 2 && str_starts_with($clean, '0b')) {
            $clean = substr($clean, 2);
        } elseif ($fromBase === 8 && str_starts_with($clean, '0o')) {
            $clean = substr($clean, 2);
        }

        // Validate characters for the from_base
        $allowedChars = substr('0123456789abcdefghijklmnopqrstuvwxyz', 0, $fromBase);
        if (!preg_match('/^[' . $allowedChars . ']+$/i', $clean)) {
            throw new InvalidArgumentException("Input contains invalid characters for base {$fromBase}.");
        }

        // Convert to decimal first
        $decimal = base_convert($clean, $fromBase, 10);

        // Convert to common bases
        $binary = base_convert($decimal, 10, 2);
        $octal = base_convert($decimal, 10, 8);
        $hex = strtoupper(base_convert($decimal, 10, 16));

        $data = [
            'input'       => $raw,
            'source_base' => $fromBase,
            'binary'      => $binary,
            'octal'       => $octal,
            'decimal'     => $decimal,
            'hexadecimal' => $hex,
        ];

        $lines = [
            "Binary (Base 2):       " . $binary,
            "Octal (Base 8):        " . $octal,
            "Decimal (Base 10):     " . $decimal,
            "Hexadecimal (Base 16): " . $hex,
        ];

        return [
            'success' => true,
            'type'    => ResultType::TEXT,
            'data'    => $data,
            'value'   => implode("\n", $lines),
            'meta'    => $data,
        ];
    }
}

