<?php

declare(strict_types=1);

namespace FavoriteCMS\Digital\Exceptions;

use RuntimeException;

class CheckoutException extends RuntimeException
{
    public static function orderNotPayable(string $orderNumber, string $reason): self
    {
        return new self("Order '{$orderNumber}' cannot be paid: {$reason}.");
    }

    public static function unauthorizedOrderAccess(string $orderNumber, int $userId): self
    {
        return new self("User {$userId} is not authorized to checkout order '{$orderNumber}'.");
    }

    public static function amountMismatch(string $expected, string $provided, ?string $currency = null): self
    {
        $sym = class_exists(\FavoriteCMS\Core\Currency::class) ? \FavoriteCMS\Core\Currency::getSymbol($currency) : ($currency ? $currency . ' ' : '');
        return new self("Payment amount mismatch. Expected: {$sym}{$expected}, Provided: {$sym}{$provided}.");
    }

    public static function currencyMismatch(string $expected, string $provided): self
    {
        return new self("Payment currency mismatch. Expected: {$expected}, Provided: {$provided}.");
    }

    public static function invalidPaymentMethod(string $method): self
    {
        return new self("Unsupported or invalid payment method: '{$method}'.");
    }

    public static function gatewayError(string $message): self
    {
        return new self("Favorite Pay gateway error: {$message}");
    }
}

