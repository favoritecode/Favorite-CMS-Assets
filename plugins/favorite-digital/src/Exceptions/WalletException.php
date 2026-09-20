<?php

declare(strict_types=1);

namespace FavoriteCMS\Digital\Exceptions;

use RuntimeException;

class WalletException extends RuntimeException
{
    public static function insufficientBalance(string $available, string $required, ?string $currency = null): self
    {
        $sym = class_exists(\FavoriteCMS\Core\Currency::class) ? \FavoriteCMS\Core\Currency::getSymbol($currency) : ($currency ? $currency . ' ' : '');
        return new self("Insufficient wallet balance. Available: {$sym}{$available}, Required: {$sym}{$required}.");
    }

    public static function invalidAmount(string $amount): self
    {
        return new self("Invalid wallet transaction amount: '{$amount}'. Amount must be positive.");
    }

    public static function walletNotFound(int $userId): self
    {
        return new self("Wallet not found for user ID: {$userId}.");
    }

    public static function walletInactive(int $userId, string $status): self
    {
        return new self("Wallet for user ID {$userId} is {$status} and cannot process transactions.");
    }

    public static function concurrencyFailure(int $userId): self
    {
        return new self("Failed to acquire wallet lock for user ID {$userId}. Please retry.");
    }

    public static function depositFailed(string $reason): self
    {
        return new self("Wallet deposit failed: {$reason}");
    }
}

