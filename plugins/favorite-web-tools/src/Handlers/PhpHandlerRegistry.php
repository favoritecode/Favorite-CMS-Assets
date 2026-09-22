<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Handlers;

use FavoriteCMS\Tools\Contracts\ToolHandlerInterface;
use InvalidArgumentException;

class PhpHandlerRegistry
{
    /**
     * @var array<string, ToolHandlerInterface>
     */
    protected static array $handlers = [];
    protected static bool $initialized = false;

    public static function register(ToolHandlerInterface $handler): void
    {
        static::$handlers[$handler->getId()] = $handler;
    }

    public static function get(string $id): ?ToolHandlerInterface
    {
        static::ensureDefaults();
        if (isset(static::$handlers[$id])) {
            return static::$handlers[$id];
        }
        foreach (static::$handlers as $handler) {
            if (get_class($handler) === $id) {
                return $handler;
            }
        }
        return null;
    }

    public static function has(string $id): bool
    {
        return static::get($id) !== null;
    }

    /**
     * @return array<string, string> Map of handler ID => Handler Name
     */
    public static function listHandlers(): array
    {
        static::ensureDefaults();
        $list = [];
        foreach (static::$handlers as $id => $handler) {
            $list[$id] = $handler->getName();
        }
        return $list;
    }

    public static function ensureDefaults(): void
    {
        if (static::$initialized) {
            return;
        }
        static::$initialized = true;

        $defaults = [
            new JsonFormatterHandler(),
            new JsonValidatorHandler(),
            new JsonMinifierHandler(),
            new Base64EncoderHandler(),
            new Base64DecoderHandler(),
            new UrlEncoderHandler(),
            new UrlDecoderHandler(),
            new UuidGeneratorHandler(),
            new HashGeneratorHandler(),
            new TimestampConverterHandler(),
            new RegexTesterHandler(),
            new LoremIpsumGeneratorHandler(),
            new TextCaseConverterHandler(),
            new TextCounterHandler(),
            new DuplicateLinesRemoverHandler(),
            new LineSorterHandler(),
            new FindAndReplaceHandler(),
            new PhpFormatterHandler(),
            new PhpValidatorHandler(),
            new PhpMinifierHandler(),
            new PasswordGeneratorHandler(),
            new MarkdownPreviewerHandler(),
            new NumberBaseConverterHandler(),
            new HttpStatusLookupHandler(),
            new UserAgentParserHandler(),
            new QueryStringParserHandler(),
            new SlugGeneratorHandler(),
            new PhpSerializerHandler(),
            new PhpArrayToJsonHandler(),
        ];

        foreach ($defaults as $handler) {
            static::register($handler);
        }
    }

    public static function reset(): void
    {
        static::$handlers = [];
        static::$initialized = false;
    }
}

