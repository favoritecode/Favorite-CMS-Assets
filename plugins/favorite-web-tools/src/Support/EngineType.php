<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Support;

final class EngineType
{
    public const HTML = 'HTML';
    public const CSS = 'CSS';
    public const JAVASCRIPT = 'JAVASCRIPT';
    public const PHP = 'PHP';
    public const PYTHON_API = 'PYTHON_API';

    public static function all(): array
    {
        return [
            self::HTML,
            self::CSS,
            self::JAVASCRIPT,
            self::PHP,
            self::PYTHON_API,
        ];
    }

    public static function isValid(string $engine): bool
    {
        return in_array($engine, self::all(), true);
    }

    public static function label(string $engine): string
    {
        return match ($engine) {
            self::HTML => 'HTML Engine',
            self::CSS => 'CSS Engine',
            self::JAVASCRIPT => 'JavaScript Engine',
            self::PHP => 'PHP Controlled Handler',
            self::PYTHON_API => 'Python API Engine',
            default => $engine,
        };
    }
}

