<?php

declare(strict_types=1);

namespace FavoriteCMS\Digital\Support {

    class HeaderHelper
    {
        /**
         * Check if the given user (or currently authenticated user) has an ACTIVE premium membership.
         */
        public static function isPremiumActive(?int $userId = null): bool
        {
            if ($userId === null) {
                $user = function_exists('current_user') ? current_user() : null;
                if (!$user || empty($user->id)) {
                    return false;
                }
                $userId = (int)$user->id;
            }

            if ($userId <= 0) {
                return false;
            }

            try {
                $container = class_exists(\FavoriteCMS\Core\Container::class)
                    ? \FavoriteCMS\Core\Container::getInstance()
                    : null;
                if ($container && $container->has(\FavoriteCMS\Digital\Services\MembershipLifecycleService::class)) {
                    $memService = $container->make(\FavoriteCMS\Digital\Services\MembershipLifecycleService::class);
                    $activeMem = $memService->getActiveMembership($userId);
                    return ($activeMem !== null && (
                        $activeMem->status === 'active' ||
                        $activeMem->status === \FavoriteCMS\Digital\Domain\MembershipStatus::ACTIVE
                    ));
                }
            } catch (\Throwable) {
                return false;
            }

            return false;
        }

        /**
         * Retrieve formatted wallet balance string for the given user (or currently authenticated user).
         * Returns null if unauthenticated, unavailable, or wallet error occurs.
         */
        public static function getWalletBalance(?int $userId = null): ?string
        {
            if ($userId === null) {
                $user = function_exists('current_user') ? current_user() : null;
                if (!$user || empty($user->id)) {
                    return null;
                }
                $userId = (int)$user->id;
            }

            if ($userId <= 0) {
                return null;
            }

            try {
                $container = class_exists(\FavoriteCMS\Core\Container::class)
                    ? \FavoriteCMS\Core\Container::getInstance()
                    : null;
                if ($container && $container->has(\FavoriteCMS\Digital\Services\WalletService::class)) {
                    $walletService = $container->make(\FavoriteCMS\Digital\Services\WalletService::class);
                    $rawBalance = $walletService->getBalance($userId);
                    if (class_exists(\FavoriteCMS\Core\Currency::class)) {
                        return \FavoriteCMS\Core\Currency::format($rawBalance);
                    }
                    if (function_exists('format_currency')) {
                        return format_currency($rawBalance);
                    }
                    return (string)$rawBalance;
                }
            } catch (\Throwable) {
                return null;
            }

            return null;
        }
    }
}

namespace {

    // Global helper to check active premium membership
    if (!function_exists('fdig_is_premium_active')) {
        function fdig_is_premium_active(?int $userId = null): bool
        {
            return \FavoriteCMS\Digital\Support\HeaderHelper::isPremiumActive($userId);
        }
    }

    // Global helper to get formatted wallet balance using Core currency system
    if (!function_exists('fdig_get_wallet_balance')) {
        function fdig_get_wallet_balance(?int $userId = null): ?string
        {
            return \FavoriteCMS\Digital\Support\HeaderHelper::getWalletBalance($userId);
        }
    }

    // Pluggable theme helper for favorite-web header wallet pill
    if (!function_exists('fw_wallet_balance')) {
        /**
         * Pluggable helper returning formatted spendable wallet balance string for the authenticated user,
         * or null if unauthenticated, unavailable, or Favorite Digital not present.
         */
        function fw_wallet_balance(?int $userId = null): ?string
        {
            \FavoriteCMS\Digital\FavoriteDigitalPlugin::$walletPillRenderedByTheme = true;
            return fdig_get_wallet_balance($userId);
        }
    }

    if (!function_exists('fdig_format_bytes')) {
        function fdig_format_bytes(int $bytes): string
        {
            if ($bytes <= 0) {
                return '0 B';
            }
            $units = ['B', 'KB', 'MB', 'GB', 'TB'];
            $power = (int)floor(log($bytes, 1024));
            $power = min($power, count($units) - 1);
            $val = round($bytes / pow(1024, $power), 2);
            return $val . ' ' . $units[$power];
        }
    }
}

