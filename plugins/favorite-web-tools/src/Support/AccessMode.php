<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Support;

final class AccessMode
{
    public const FREE = 'FREE';
    public const LOGIN_REQUIRED = 'LOGIN_REQUIRED';
    public const MEMBERSHIP_REQUIRED = 'MEMBERSHIP_REQUIRED';

    public static function all(): array
    {
        return [
            self::FREE,
            self::LOGIN_REQUIRED,
            self::MEMBERSHIP_REQUIRED,
        ];
    }

    public static function isValid(string $mode): bool
    {
        return in_array($mode, self::all(), true);
    }

    public static function label(string $mode): string
    {
        return match ($mode) {
            self::FREE => 'Free',
            self::LOGIN_REQUIRED => 'Login Required',
            self::MEMBERSHIP_REQUIRED => 'Membership Required',
            default => $mode,
        };
    }
}

