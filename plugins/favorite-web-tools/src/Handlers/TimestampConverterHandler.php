<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Handlers;

use DateTime;
use DateTimeZone;
use FavoriteCMS\Tools\Support\ResultType;
use InvalidArgumentException;

class TimestampConverterHandler extends AbstractToolHandler
{
    public function getId(): string
    {
        return 'timestamp_converter';
    }

    public function getName(): string
    {
        return 'Unix Timestamp Converter';
    }

    public function execute(array $inputs, array $config = []): array
    {
        $raw = trim((string)($inputs['input'] ?? $inputs['timestamp'] ?? $inputs['date'] ?? ''));
        if ($raw === '') {
            $raw = (string)time();
        }

        $tzName = trim((string)($inputs['timezone'] ?? 'UTC'));
        try {
            $tz = new DateTimeZone($tzName !== '' ? $tzName : 'UTC');
        } catch (\Throwable) {
            $tz = new DateTimeZone('UTC');
        }

        $dt = null;
        if (is_numeric($raw)) {
            $timestamp = (int)$raw;
            $dt = new DateTime('@' . $timestamp);
            $dt->setTimezone($tz);
        } else {
            try {
                $dt = new DateTime($raw, $tz);
                $timestamp = $dt->getTimestamp();
            } catch (\Throwable $e) {
                throw new InvalidArgumentException('Could not parse timestamp or date string: ' . $raw);
            }
        }

        $utc = clone $dt;
        $utc->setTimezone(new DateTimeZone('UTC'));

        $lines = [
            "Unix Timestamp: " . $timestamp,
            "UTC Time:       " . $utc->format('Y-m-d H:i:s T'),
            "ISO 8601:       " . $utc->format(DateTime::ATOM),
            "Selected Zone:  " . $dt->format('Y-m-d H:i:s T') . " ({$tz->getName()})",
            "RFC 2822:       " . $dt->format(DateTime::RFC2822),
        ];

        $meta = [
            'timestamp' => $timestamp,
            'utc'       => $utc->format('Y-m-d H:i:s'),
            'iso8601'   => $utc->format(DateTime::ATOM),
            'local'     => $dt->format('Y-m-d H:i:s'),
            'timezone'  => $tz->getName(),
        ];

        return [
            'success' => true,
            'type'    => ResultType::TEXT,
            'value'   => implode("\n", $lines),
            'data'    => $meta,
            'meta'    => $meta,
        ];
    }
}

