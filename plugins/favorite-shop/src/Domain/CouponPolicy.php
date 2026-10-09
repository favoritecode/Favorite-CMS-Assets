<?php
declare(strict_types=1);

namespace FavoriteCMS\Shop\Domain;

/** Server-side coupon validation and deterministic discount calculation. */
final class CouponPolicy
{
    public const TYPES = ['fixed', 'percent', 'free_shipping'];

    /** @return array{code:string,type:string,value:int,min_subtotal_cents:int,max_discount_cents:?int,starts_at:string,ends_at:string,usage_limit:?int,per_customer_limit:?int,stackable:bool} */
    public static function normalize(array $input): array
    {
        $code = strtoupper(trim((string)($input['code'] ?? '')));
        if (!preg_match('/^[A-Z0-9][A-Z0-9_-]{2,99}$/', $code)) throw new \InvalidArgumentException('Coupon code must be 3–100 letters, numbers, dashes or underscores.');
        $type = (string)($input['discount_type'] ?? 'fixed');
        if (!in_array($type, self::TYPES, true)) throw new \InvalidArgumentException('Unsupported coupon discount type.');
        $value = filter_var($input['discount_value'] ?? 0, FILTER_VALIDATE_INT);
        if ($value === false || $value < 0 || ($type === 'percent' && $value > 100)) throw new \InvalidArgumentException('Coupon discount value is invalid.');
        if ($type === 'percent' && $value === 0) throw new \InvalidArgumentException('Percentage discount must be greater than zero.');
        if ($type === 'fixed' && $value === 0) throw new \InvalidArgumentException('Fixed discount must be greater than zero.');
        $start = self::date((string)($input['starts_at'] ?? ''), 'Coupon start date is required.');
        $end = self::date((string)($input['ends_at'] ?? ''), 'Coupon expiry date is required.');
        if ($end <= $start) throw new \InvalidArgumentException('Coupon expiry must be after its start.');
        $max = self::optionalInt($input['max_discount_cents'] ?? null, 'Maximum discount');
        $limit = self::optionalInt($input['usage_limit'] ?? null, 'Usage limit');
        $perCustomer = self::optionalInt($input['per_customer_limit'] ?? null, 'Per-customer usage limit');
        return [
            'code'=>$code,'type'=>$type,'value'=>$value,
            'min_subtotal_cents'=>self::nonNegativeInt($input['min_subtotal_cents'] ?? 0, 'Minimum order'),
            'max_discount_cents'=>$max,
            'starts_at'=>$start->format('Y-m-d H:i:s'),'ends_at'=>$end->format('Y-m-d H:i:s'),
            'usage_limit'=>$limit,'per_customer_limit'=>$perCustomer,
            'stackable'=>filter_var($input['stackable'] ?? false, FILTER_VALIDATE_BOOL),
        ];
    }

    public static function state(array $coupon, int $totalUses, int $customerUses, ?\DateTimeImmutable $now = null): string
    {
        if (isset($coupon['status']) && $coupon['status'] !== 'active') return (string)$coupon['status'];
        $start = self::date((string)($coupon['starts_at'] ?? ''), 'Coupon start date is required.');
        $end = self::date((string)($coupon['ends_at'] ?? ''), 'Coupon expiry date is required.');
        if ($end <= $start) throw new \InvalidArgumentException('Coupon expiry must be after its start.');
        $now = $now ?? new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        if ($now < $start) return 'scheduled';
        if ($now >= $end) return 'expired';
        if (isset($coupon['usage_limit']) && $coupon['usage_limit'] !== null && $totalUses >= (int)$coupon['usage_limit']) return 'usage_limit_reached';
        if (isset($coupon['per_customer_limit']) && $coupon['per_customer_limit'] !== null && $customerUses >= (int)$coupon['per_customer_limit']) return 'customer_limit_reached';
        return 'active';
    }

    /** Returns discount in minor currency units; call only after state and product eligibility checks. */
    public static function discountCents(array $coupon, int $eligibleSubtotalCents, int $shippingCents = 0): int
    {
        if ($eligibleSubtotalCents < 0 || $shippingCents < 0) throw new \InvalidArgumentException('Amounts cannot be negative.');
        $type = (string)($coupon['discount_type'] ?? $coupon['type'] ?? '');
        $value = (int)($coupon['discount_value'] ?? $coupon['value'] ?? 0);
        if ($type === 'free_shipping') return $shippingCents;
        if ($type === 'percent') {
            if ($value < 0 || $value > 100) throw new \InvalidArgumentException('Percentage discount must be between 0 and 100.');
            $discount = (int) floor($eligibleSubtotalCents * $value / 100);
        } elseif ($type === 'fixed') {
            $discount = max(0, $value);
        } else {
            throw new \InvalidArgumentException('Unsupported coupon discount type.');
        }
        $max = $coupon['max_discount_cents'] ?? null;
        if ($max !== null) $discount = min($discount, max(0, (int)$max));
        return min($discount, $eligibleSubtotalCents);
    }

    private static function optionalInt(mixed $value, string $label): ?int
    {
        if ($value === null || $value === '') return null;
        $n = filter_var($value, FILTER_VALIDATE_INT);
        if ($n === false || $n < 1) throw new \InvalidArgumentException($label . ' must be a positive whole number or blank.');
        return $n;
    }
    private static function nonNegativeInt(mixed $value, string $label): int
    {
        $n = filter_var($value, FILTER_VALIDATE_INT);
        if ($n === false || $n < 0) throw new \InvalidArgumentException($label . ' must be a non-negative whole number.');
        return $n;
    }
    private static function date(string $value, string $message): \DateTimeImmutable
    {
        if (trim($value) === '') throw new \InvalidArgumentException($message);
        try { return new \DateTimeImmutable($value, new \DateTimeZone('UTC')); }
        catch (\Throwable) { throw new \InvalidArgumentException('Enter a valid coupon date and time.'); }
    }
    private function __construct() {}
}
