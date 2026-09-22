<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Tests;

use FavoriteCMS\Tools\Contracts\MembershipCheckerInterface;
use FavoriteCMS\Tools\Models\Tool;
use FavoriteCMS\Tools\Services\AccessControlService;
use FavoriteCMS\Tools\Support\AccessMode;
use FavoriteCMS\Tools\Support\ToolStatus;
use PHPUnit\Framework\TestCase;

class AccessControlTest extends TestCase
{
    private AccessControlService $accessControl;

    protected function setUp(): void
    {
        parent::setUp();
        $this->accessControl = new AccessControlService();
    }

    public function testFreeToolIsAccessibleToAnyone(): void
    {
        $tool = new Tool([
            'name'        => 'Free Formatter',
            'slug'        => 'free-formatter',
            'access_mode' => AccessMode::FREE,
            'status'      => ToolStatus::ACTIVE,
        ]);

        // Anonymous user
        $anonCheck = $this->accessControl->canAccess($tool, null);
        $this->assertTrue($anonCheck['can_access']);

        // Authenticated user
        $userCheck = $this->accessControl->canAccess($tool, 42);
        $this->assertTrue($userCheck['can_access']);
    }

    public function testLoginRequiredToolRequiresAuthentication(): void
    {
        $tool = new Tool([
            'name'        => 'Protected Converter',
            'slug'        => 'protected-converter',
            'access_mode' => AccessMode::LOGIN_REQUIRED,
            'status'      => ToolStatus::ACTIVE,
        ]);

        // Anonymous access rejected
        $anonCheck = $this->accessControl->canAccess($tool, null);
        $this->assertFalse($anonCheck['can_access']);
        $this->assertEquals('ACCESS_DENIED_AUTH', $anonCheck['reason']);

        // Authenticated access permitted
        $userCheck = $this->accessControl->canAccess($tool, 10);
        $this->assertTrue($userCheck['can_access']);
    }

    public function testMembershipRequiredToolRequiresActiveMembership(): void
    {
        $tool = new Tool([
            'name'        => 'Pro Tool',
            'slug'        => 'pro-tool',
            'access_mode' => AccessMode::MEMBERSHIP_REQUIRED,
            'status'      => ToolStatus::ACTIVE,
        ]);

        // Anonymous access rejected
        $anonCheck = $this->accessControl->canAccess($tool, null);
        $this->assertFalse($anonCheck['can_access']);
        $this->assertEquals('ACCESS_DENIED_AUTH', $anonCheck['reason']);

        // Authenticated user WITHOUT membership rejected
        $nonMemberCheck = $this->accessControl->canAccess($tool, 99);
        $this->assertFalse($nonMemberCheck['can_access']);
        $this->assertEquals('ACCESS_DENIED_MEMBERSHIP', $nonMemberCheck['reason']);

        // Set up mock membership checker that returns true for user 100
        $mockChecker = new class implements MembershipCheckerInterface {
            public function hasActiveMembership(int $userId): bool {
                return $userId === 100;
            }
        };

        $this->accessControl->setMembershipChecker($mockChecker);

        // User 99 still denied
        $this->assertFalse($this->accessControl->canAccess($tool, 99)['can_access']);

        // User 100 with active membership allowed
        $memberCheck = $this->accessControl->canAccess($tool, 100);
        $this->assertTrue($memberCheck['can_access']);
    }

    public function testUnlimitedMembershipAccessHasNoQuotasOrCredits(): void
    {
        $tool = new Tool([
            'name'        => 'Member Tool',
            'slug'        => 'member-tool',
            'access_mode' => AccessMode::MEMBERSHIP_REQUIRED,
            'status'      => ToolStatus::ACTIVE,
        ]);

        $mockChecker = new class implements MembershipCheckerInterface {
            public function hasActiveMembership(int $userId): bool {
                return true;
            }
        };
        $this->accessControl->setMembershipChecker($mockChecker);

        // Execute 50 consecutive checks to verify no credit decrements, daily limits, or locks occur
        for ($i = 0; $i < 50; $i++) {
            $check = $this->accessControl->canAccess($tool, 100);
            $this->assertTrue($check['can_access']);
        }
    }

    public function testDraftAndDisabledToolsAccessRules(): void
    {
        $draftTool = new Tool([
            'name'        => 'Draft Tool',
            'slug'        => 'draft-tool',
            'access_mode' => AccessMode::FREE,
            'status'      => ToolStatus::DRAFT,
        ]);

        $disabledTool = new Tool([
            'name'        => 'Disabled Tool',
            'slug'        => 'disabled-tool',
            'access_mode' => AccessMode::FREE,
            'status'      => ToolStatus::DISABLED,
        ]);

        // Regular users (even authenticated members) cannot access draft or disabled tools
        $this->assertFalse($this->accessControl->canAccess($draftTool, 1, false)['can_access']);
        $this->assertFalse($this->accessControl->canAccess($disabledTool, 1, false)['can_access']);

        // Admin preview/test mode bypasses status restriction
        $this->assertTrue($this->accessControl->canAccess($draftTool, 1, true)['can_access']);
        $this->assertTrue($this->accessControl->canAccess($disabledTool, 1, true)['can_access']);
    }
}

