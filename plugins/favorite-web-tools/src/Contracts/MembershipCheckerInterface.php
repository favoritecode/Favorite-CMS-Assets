<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Contracts;

interface MembershipCheckerInterface
{
    /**
     * Check if the specified user has an active membership entitlement.
     */
    public function hasActiveMembership(int $userId): bool;
}

