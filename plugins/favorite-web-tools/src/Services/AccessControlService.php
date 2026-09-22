<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Services;

use FavoriteCMS\Tools\Contracts\MembershipCheckerInterface;
use FavoriteCMS\Tools\Models\Tool;
use FavoriteCMS\Tools\Support\AccessMode;
use FavoriteCMS\Tools\Support\ToolStatus;

class AccessControlService
{
    protected ?MembershipCheckerInterface $membershipChecker;

    public function __construct(?MembershipCheckerInterface $membershipChecker = null)
    {
        $this->membershipChecker = $membershipChecker;
    }

    public function setMembershipChecker(MembershipCheckerInterface $checker): void
    {
        $this->membershipChecker = $checker;
    }

    /**
     * Evaluates access permissions for a user attempting to view or execute a tool.
     *
     * @param Tool $tool
     * @param int|null $userId ID of the authenticated user, or null/0 if guest.
     * @param bool $isAdmin Whether the user has administrative privileges.
     * @return array{allowed: bool, reason: string, code: string}
     */
    public function evaluate(Tool $tool, ?int $userId = null, bool $isAdmin = false): array
    {
        $uid = (int)($userId ?? 0);

        // 1. Status Check
        if ($tool->isDisabled()) {
            if (!$isAdmin) {
                return [
                    'allowed' => false,
                    'reason'  => 'This tool is currently disabled and unavailable.',
                    'code'    => 'TOOL_DISABLED',
                ];
            }
            return [
                'allowed' => true,
                'reason'  => 'Admin test of disabled tool.',
                'code'    => 'ADMIN_DISABLED_ACCESS',
            ];
        }

        if ($tool->isDraft()) {
            if (!$isAdmin) {
                return [
                    'allowed' => false,
                    'reason'  => 'This tool is currently in draft mode and not available publicly.',
                    'code'    => 'TOOL_DRAFT',
                ];
            }
            // Admin can test draft tools
            return [
                'allowed' => true,
                'reason'  => 'Admin preview of draft tool.',
                'code'    => 'ADMIN_DRAFT_ACCESS',
            ];
        }

        // Admins always have access to active tools
        if ($isAdmin) {
            return [
                'allowed' => true,
                'reason'  => 'Administrative override.',
                'code'    => 'ADMIN_OVERRIDE',
            ];
        }

        // 2. Access Mode Evaluation
        switch ($tool->access_mode) {
            case AccessMode::FREE:
                return [
                    'allowed' => true,
                    'reason'  => 'Free tool access.',
                    'code'    => 'ACCESS_GRANTED_FREE',
                ];

            case AccessMode::LOGIN_REQUIRED:
                if ($uid <= 0) {
                    return [
                        'allowed' => false,
                        'reason'  => 'Please sign in to use this tool.',
                        'code'    => 'AUTH_REQUIRED',
                    ];
                }
                return [
                    'allowed' => true,
                    'reason'  => 'Authenticated user access.',
                    'code'    => 'ACCESS_GRANTED_LOGIN',
                ];

            case AccessMode::MEMBERSHIP_REQUIRED:
                if ($uid <= 0) {
                    return [
                        'allowed' => false,
                        'reason'  => 'Please sign in to use this tool.',
                        'code'    => 'AUTH_REQUIRED',
                    ];
                }

                $hasMembership = $this->checkMembership($uid);
                if (!$hasMembership) {
                    return [
                        'allowed' => false,
                        'reason'  => 'This tool requires an active membership.',
                        'code'    => 'MEMBERSHIP_REQUIRED',
                    ];
                }

                return [
                    'allowed' => true,
                    'reason'  => 'Active membership access.',
                    'code'    => 'ACCESS_GRANTED_MEMBERSHIP',
                ];

            default:
                return [
                    'allowed' => false,
                    'reason'  => 'Unrecognized tool access mode.',
                    'code'    => 'INVALID_ACCESS_MODE',
                ];
        }
    }

    /**
     * Check if user has active membership via custom checker, global helper, or core database.
     */
    public function checkMembership(int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }

        if ($this->membershipChecker !== null) {
            return $this->membershipChecker->hasActiveMembership($userId);
        }

        // Check global helper from favorite-digital if present
        if (function_exists('fdig_is_premium_active')) {
            try {
                return (bool)fdig_is_premium_active($userId);
            } catch (\Throwable) {
            }
        }

        // Check HeaderHelper from favorite-digital if present
        if (class_exists(\FavoriteCMS\Digital\Support\HeaderHelper::class)) {
            try {
                return \FavoriteCMS\Digital\Support\HeaderHelper::isPremiumActive($userId);
            } catch (\Throwable) {
            }
        }

        // Fallback: check database directly for active membership if Database service is available
        if (function_exists('app')) {
            try {
                $app = app();
                if ($app && $app->has(\FavoriteCMS\Core\Database::class)) {
                    $db = $app->make(\FavoriteCMS\Core\Database::class);
                    if ($db->tableExists('favorite_digital_memberships')) {
                        $sql = "
                            SELECT id FROM `favorite_digital_memberships`
                            WHERE user_id = ? AND status = 'active'
                            AND (ends_at IS NULL OR ends_at > datetime('now'))
                            LIMIT 1
                        ";
                        $row = $db->selectOne($sql, [$userId]);
                        return !empty($row);
                    }
                }
            } catch (\Throwable) {
            }
        }

        return false;
    }

    /**
     * Convenient alias returning can_access boolean and canonical reason codes.
     *
     * @return array{can_access: bool, allowed: bool, reason: string, code: string, message: string}
     */
    public function canAccess(Tool $tool, ?int $userId = null, bool $isAdmin = false): array
    {
        $eval = $this->evaluate($tool, $userId, $isAdmin);
        $code = $eval['code'];
        $canonicalReason = match ($code) {
            'AUTH_REQUIRED'       => 'ACCESS_DENIED_AUTH',
            'MEMBERSHIP_REQUIRED' => 'ACCESS_DENIED_MEMBERSHIP',
            'TOOL_DISABLED'       => 'TOOL_UNAVAILABLE',
            'TOOL_DRAFT'          => 'TOOL_UNAVAILABLE',
            default               => $eval['reason'],
        };

        return [
            'can_access' => $eval['allowed'],
            'allowed'    => $eval['allowed'],
            'reason'     => $canonicalReason,
            'code'       => $code,
            'message'    => $eval['reason'],
        ];
    }
}
