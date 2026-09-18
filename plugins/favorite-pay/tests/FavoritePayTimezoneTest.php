<?php

declare(strict_types=1);

namespace FavoriteCMS\Tests\Unit\Plugins\FavoritePay;

use DateTimeImmutable;
use DateTimeZone;
use FavoriteCMS\Core\DateTime;
use FavoriteCMS\Models\Setting;
use FavoriteCMS\Pay\Support\TimezoneHelper;
use PHPUnit\Framework\TestCase;

class SettingTestProxy extends Setting
{
    public static function setTestTimezone(string $timezone): void
    {
        self::$cache['general.timezone'] = $timezone;
    }
}

class FavoritePayTimezoneTest extends TestCase
{
    protected function tearDown(): void
    {
        Setting::clearCache();
    }

    public function testFormatDatetimeConvertsUtcToConfiguredSiteTimezone(): void
    {
        $utcTimestamp = '2026-09-18 08:00:00';

        // 1. When timezone is UTC
        SettingTestProxy::setTestTimezone('UTC');
        $this->assertSame('18 Sep 2026, 08:00 AM', fpay_format_datetime($utcTimestamp));

        // 2. When timezone is Asia/Dhaka (+06:00)
        SettingTestProxy::setTestTimezone('Asia/Dhaka');
        $this->assertSame('18 Sep 2026, 02:00 PM', fpay_format_datetime($utcTimestamp));

        // 3. When timezone is America/New_York (EDT, UTC-4 in September)
        SettingTestProxy::setTestTimezone('America/New_York');
        $this->assertSame('18 Sep 2026, 04:00 AM', fpay_format_datetime($utcTimestamp));
    }

    public function testFormatDateOutputsDateOnlyInSiteTimezone(): void
    {
        // 2026-09-18 20:00:00 UTC is 2026-09-19 02:00:00 in Asia/Dhaka
        $utcTimestamp = '2026-09-18 20:00:00';

        SettingTestProxy::setTestTimezone('UTC');
        $this->assertSame('18 Sep 2026', fpay_format_date($utcTimestamp));

        SettingTestProxy::setTestTimezone('Asia/Dhaka');
        $this->assertSame('19 Sep 2026', fpay_format_date($utcTimestamp));
    }

    public function testFormatHandlesEpochIntegersAndDateTimeObjects(): void
    {
        SettingTestProxy::setTestTimezone('Asia/Dhaka');

        // Epoch for 2026-09-18 08:00:00 UTC is 1789718400
        $epoch = strtotime('2026-09-18 08:00:00 UTC');
        $this->assertSame('18 Sep 2026, 02:00 PM', fpay_format_datetime($epoch));

        $dt = new DateTimeImmutable('2026-09-18 08:00:00', new DateTimeZone('UTC'));
        $this->assertSame('18 Sep 2026, 02:00 PM', fpay_format_datetime($dt));
    }

    public function testFormatHandlesEmptyAndInvalidInputsGracefully(): void
    {
        $this->assertSame('', fpay_format_datetime(null));
        $this->assertSame('', fpay_format_datetime(''));
        $this->assertSame('invalid-date-string', fpay_format_datetime('invalid-date-string'));
    }

    public function testDirectHelperClassFormatWithExplicitOverride(): void
    {
        $utc = '2026-09-18 08:00:00';
        $formatted = TimezoneHelper::format($utc, 'Y-m-d H:i:s', 'Asia/Dhaka');
        $this->assertSame('2026-09-18 14:00:00', $formatted);
    }
}
