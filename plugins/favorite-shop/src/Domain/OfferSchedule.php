<?php
declare(strict_types=1);

namespace FavoriteCMS\Shop\Domain;

/** Pure rules for scheduled sale offers; persisted prices remain untouched. */
final class OfferSchedule
{
    public const STATES = ['draft', 'scheduled', 'active', 'expired', 'paused'];

    public static function state(string $status, ?string $startsAt, ?string $endsAt, ?\DateTimeImmutable $now = null): string
    {
        if (!in_array($status, self::STATES, true)) {
            throw new \InvalidArgumentException('Invalid offer status.');
        }
        if ($status === 'draft' || $status === 'paused') return $status;
        $start = self::date($startsAt, 'Offer start date is required.');
        $end = self::date($endsAt, 'Offer expiry date is required.');
        if ($end <= $start) throw new \InvalidArgumentException('Offer expiry must be after its start.');
        $now = $now ?? new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        if ($now < $start) return 'scheduled';
        if ($now >= $end) return 'expired';
        return 'active';
    }

    /** @return array{starts_at:string,ends_at:string,status:string} */
    public static function normalize(string $startsAt, string $endsAt, string $status = 'scheduled'): array
    {
        $start = self::date($startsAt, 'Offer start date is required.');
        $end = self::date($endsAt, 'Offer expiry date is required.');
        if ($end <= $start) throw new \InvalidArgumentException('Offer expiry must be after its start.');
        if (!in_array($status, self::STATES, true)) throw new \InvalidArgumentException('Invalid offer status.');
        return ['starts_at'=>$start->format('Y-m-d H:i:s'), 'ends_at'=>$end->format('Y-m-d H:i:s'), 'status'=>$status];
    }

    private static function date(?string $value, string $message): \DateTimeImmutable
    {
        if ($value === null || trim($value) === '') throw new \InvalidArgumentException($message);
        try { return new \DateTimeImmutable($value, new \DateTimeZone('UTC')); }
        catch (\Throwable) { throw new \InvalidArgumentException('Enter a valid offer date and time.'); }
    }

    private function __construct() {}
}
