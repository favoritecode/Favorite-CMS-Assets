<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Support;

final class ResultType
{
    public const TEXT = 'TEXT';
    public const HTML = 'HTML';
    public const CSS = 'CSS';
    public const JSON = 'JSON';
    public const FILE = 'FILE';
    public const DOWNLOAD = 'DOWNLOAD';

    public static function all(): array
    {
        return [
            self::TEXT,
            self::HTML,
            self::CSS,
            self::JSON,
            self::FILE,
            self::DOWNLOAD,
        ];
    }

    public static function isValid(string $type): bool
    {
        return in_array($type, self::all(), true);
    }
}
