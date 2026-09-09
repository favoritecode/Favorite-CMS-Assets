<?php

declare(strict_types=1);

namespace FavoriteCMS\Tests\Unit\Plugins\FavoritePay;

use FavoriteCMS\Core\AccountMenu;
use FavoriteCMS\Core\Application;
use FavoriteCMS\Models\User;
use FavoriteCMS\Pay\Contracts\WithdrawalServiceInterface;
use FavoriteCMS\Pay\FavoritePayPlugin;
use FavoriteCMS\Pay\Support\PaymentIcon;
use PHPUnit\Framework\TestCase;

class PaymentIconUserStub extends User
{
    private array $permissionsList;

    public function __construct(int $id = 1, string $name = 'Test User', array $permissions = [])
    {
        $this->attributes = [
            'id'       => $id,
            'username' => 'testuser',
            'name'     => $name,
            'email'    => 'test@example.com',
            'status'   => 'active',
        ];
        $this->permissionsList = $permissions;
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissionsList, true);
    }

    public function getRoles(): array
    {
        return [];
    }
}

class PaymentIconAndMenuRenderingTest extends TestCase
{
    private Application $app;
    private FavoritePayPlugin $plugin;

    protected function setUp(): void
    {
        AccountMenu::reset();
        $this->app = new Application();
        $this->plugin = new FavoritePayPlugin($this->app);
    }

    protected function tearDown(): void
    {
        AccountMenu::reset();
        unset($GLOBALS['_test_current_user']);
    }

    // =========================================================================
    // 1. PAYMENT ICON RENDERER TESTS
    // =========================================================================

    public function testPaymentIconReturnsValidSvgForSupportedIcons(): void
    {
        $icons = ['wallet', 'recharge', 'withdraw', 'payments', 'notifications', 'transactions'];

        foreach ($icons as $name) {
            $this->assertTrue(PaymentIcon::has($name), "Icon '{$name}' must be recognized");
            $svg = PaymentIcon::render($name);

            $this->assertNotEmpty($svg, "SVG output for '{$name}' must not be empty");
            $this->assertStringStartsWith('<svg', $svg);
            $this->assertStringEndsWith('</svg>', $svg);
            $this->assertStringContainsString('viewBox="0 0 24 24"', $svg);
            $this->assertStringContainsString('fill="none"', $svg);
            $this->assertStringContainsString('stroke="currentColor"', $svg);
            $this->assertStringContainsString('stroke-width="2"', $svg);
            $this->assertStringContainsString('aria-hidden="true"', $svg);
        }
    }

    public function testPaymentIconHandlesAliasesAndNormalization(): void
    {
        // Aliases
        $wallet1 = PaymentIcon::render('wallet');
        $wallet2 = PaymentIcon::render('balance');
        $this->assertSame($wallet1, $wallet2, 'Balance must alias to wallet');

        $recharge1 = PaymentIcon::render('recharge');
        $recharge2 = PaymentIcon::render('deposit');
        $this->assertSame($recharge1, $recharge2, 'Deposit must alias to recharge');

        $withdraw1 = PaymentIcon::render('withdraw');
        $withdraw2 = PaymentIcon::render('payout');
        $this->assertSame($withdraw1, $withdraw2, 'Payout must alias to withdraw');

        $payments1 = PaymentIcon::render('payments');
        $payments2 = PaymentIcon::render('receipt');
        $this->assertSame($payments1, $payments2, 'Receipt must alias to payments');

        $notif1 = PaymentIcon::render('notifications');
        $notif2 = PaymentIcon::render('bell');
        $this->assertSame($notif1, $notif2, 'Bell must alias to notifications');

        // Case insensitivity and legacy prefix stripping
        $legacy = PaymentIcon::render('fas fa-wallet');
        $this->assertSame($wallet1, $legacy, 'Legacy Font Awesome prefix must resolve to clean SVG');

        $upper = PaymentIcon::render('  WALLET  ');
        $this->assertSame($wallet1, $upper, 'Whitespace and uppercase must be normalized');
    }

    public function testMissingIconIdentifierFailsSafely(): void
    {
        $this->assertFalse(PaymentIcon::has('completely-bogus-icon-xyz'));
        $this->assertSame('', PaymentIcon::render('completely-bogus-icon-xyz'));
        $this->assertSame('', PaymentIcon::render(''));
    }

    public function testGeneratedIconMarkupContainsNoUnsafeScriptOrEventAttributes(): void
    {
        $malicious = PaymentIcon::render('wallet', [
            'onload'  => 'alert(1)',
            'onerror' => 'alert(2)',
            'onclick' => 'stealCookies()',
            'src'     => 'javascript:alert(3)',
            'class'   => 'icon-safe<script>alert(4)</script>',
            'custom'  => 'safe-val',
        ]);

        $this->assertStringNotContainsString('onload', $malicious);
        $this->assertStringNotContainsString('onerror', $malicious);
        $this->assertStringNotContainsString('onclick', $malicious);
        $this->assertStringNotContainsString('<script>', $malicious);
        $this->assertStringNotContainsString('javascript:', $malicious);
        $this->assertStringContainsString('custom="safe-val"', $malicious);
    }

    // =========================================================================
    // 2. ACCOUNT MENU RENDERING & REGRESSION TESTS
    // =========================================================================

    public function testAccountMenuDoesNotEmitRawFontAwesomeClassStringsAsVisibleFallbackText(): void
    {
        AccountMenu::registerCoreItems();
        $this->plugin->registerAccountMenuItems();

        $user = new PaymentIconUserStub(1, 'John Doe');
        $GLOBALS['_test_current_user'] = $user;

        $html = AccountMenu::render(['user' => $user]);

        // CRITICAL REGRESSION ASSERTIONS:
        // Must NOT contain literal Font Awesome class strings anywhere in output
        $this->assertStringNotContainsString('fas', $html, 'Rendered HTML must not contain raw "fas" text');
        $this->assertStringNotContainsString('fa-wallet', $html);
        $this->assertStringNotContainsString('fa-receipt', $html);
        $this->assertStringNotContainsString('fa-bell', $html);
        $this->assertStringNotContainsString('fa-exchange-alt', $html);
        $this->assertStringNotContainsString('fa-plus-circle', $html);
        $this->assertStringNotContainsString('fa-money-bill-wave', $html);

        // Must NOT contain fallback text span
        $this->assertStringNotContainsString('cms-account-icon-fallback', $html, 'Rendered HTML must not contain fallback span for Favorite Pay icons');

        // Must contain valid inline SVGs for all registered items
        $this->assertStringContainsString('cms-account-item-pay_balance', $html);
        $this->assertStringContainsString('cms-account-item-pay_recharge', $html);
        $this->assertStringContainsString('cms-account-item-pay_payments', $html);
        $this->assertStringContainsString('cms-account-item-pay_notifications', $html);
        $this->assertStringContainsString('cms-account-item-pay_transactions', $html);

        // Count <svg occurrences inside the list
        $svgCount = substr_count($html, '<svg');
        $this->assertGreaterThanOrEqual(7, $svgCount);
    }

    public function testAccountMenuPreservesOrderAndLinks(): void
    {
        AccountMenu::registerCoreItems();
        $this->plugin->registerAccountMenuItems();

        $user = new PaymentIconUserStub(1, 'Jane Admin', ['manage_options']);
        $items = AccountMenu::getItems($user);

        $keys = array_keys($items);
        $expectedOrder = [
            'profile',           // 10 (Core)
            'pay_balance',       // 14 (Pay)
            'pay_recharge',      // 16 (Pay)
            'pay_payments',      // 20 (Pay)
            'pay_notifications', // 22 (Pay)
            'pay_transactions',  // 24 (Pay)
            'dashboard',         // 30 (Core)
            'logout',            // 100 (Core)
        ];

        $this->assertSame($expectedOrder, $keys);

        // Verify URL paths
        $this->assertSame('/account/wallet', $items['pay_balance']['url']);
        $this->assertSame('/account/recharge', $items['pay_recharge']['url']);
        $this->assertSame('/account/payments', $items['pay_payments']['url']);
        $this->assertSame('/account/notifications', $items['pay_notifications']['url']);
        $this->assertSame('/account/transactions', $items['pay_transactions']['url']);
    }

    public function testAccountMenuWithWithdrawalEnabledIncludesWithdrawSvgIcon(): void
    {
        $withdrawalMock = $this->createMock(WithdrawalServiceInterface::class);
        $withdrawalMock->method('isWithdrawalEnabled')->willReturn(true);
        $this->app->singleton(WithdrawalServiceInterface::class, fn() => $withdrawalMock);

        AccountMenu::registerCoreItems();
        $this->plugin->registerAccountMenuItems();

        $user = new PaymentIconUserStub(1, 'Test User');
        $items = AccountMenu::getItems($user);

        $this->assertArrayHasKey('pay_withdraw', $items);
        $withdrawItem = $items['pay_withdraw'];
        $this->assertSame(18, $withdrawItem['order']);
        $this->assertSame('/account/withdraw', $withdrawItem['url']);
        $this->assertStringStartsWith('<svg', $withdrawItem['icon']);
        $this->assertStringNotContainsString('fas fa-', $withdrawItem['icon']);

        $html = AccountMenu::render(['user' => $user]);
        $this->assertStringContainsString('cms-account-item-pay_withdraw', $html);
        $this->assertStringNotContainsString('fa-money-bill-wave', $html);
        $this->assertStringNotContainsString('cms-account-icon-fallback', $html);
    }
}
