<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Support;

final class ToolStatus
{
    public const DRAFT = 'DRAFT';
    public const ACTIVE = 'ACTIVE';
    public const DISABLED = 'DISABLED';

    public static function all(): array
    {
        return [
            self::DRAFT,
            self::ACTIVE,
            self::DISABLED,
        ];
    }

    public static function isValid(string $status): bool
    {
        return in_array($status, self::all(), true);
    }

    public static function label(string $status): string
    {
        return match ($status) {
            self::DRAFT => 'Draft',
            self::ACTIVE => 'Active',
            self::DISABLED => 'Disabled',
            default => $status,
        };
    }
}

