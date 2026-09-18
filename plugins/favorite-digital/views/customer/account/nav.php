<?php
/**
 * Shared Customer Account Navigation Bar
 *
 * @var string $activeTab 'library' | 'orders' | 'membership' | 'refunds' | 'downloads'
 * @var array|null $wallet
 */
$tab = $activeTab ?? 'library';
$walletBalance = is_array($wallet) ? ($wallet['balance'] ?? '0.00') : ($wallet->balance_amount ?? ($wallet->balance ?? '0.00'));
$primaryCurrency = class_exists(\FavoriteCMS\Core\Currency::class) ? \FavoriteCMS\Core\Currency::getPrimaryCurrency() : 'BDT';
$currency = is_array($wallet) ? ($wallet['currency'] ?? $primaryCurrency) : ($wallet->currency ?? $primaryCurrency);
$currencySymbol = class_exists(\FavoriteCMS\Core\Currency::class) ? \FavoriteCMS\Core\Currency::getSymbol($currency) : $currency;
?>
<style>
.fav-account-nav-wrap {
    background: var(--surface, #ffffff);
    border-bottom: 1px solid var(--border, #e2e8f0);
    margin-bottom: 24px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.02);
}
.fav-account-nav {
    max-width: 1100px;
    margin: 0 auto;
    padding: 0 16px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
}
.fav-nav-tabs {
    display: flex;
    align-items: center;
    gap: 6px;
    overflow-x: auto;
    padding: 10px 0;
    -webkit-overflow-scrolling: touch;
}
.fav-nav-tab {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    color: var(--muted, #475569);
    white-space: nowrap;
    transition: all 0.15s ease;
}
.fav-nav-tab:hover {
    color: var(--heading, #0f172a);
    background: var(--surface-muted, #f1f5f9);
}
.fav-nav-tab.active {
    color: var(--accent, #2563eb);
    background: var(--accent-soft, #eff6ff);
    font-weight: 700;
}
.fav-wallet-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    background: var(--surface-muted, #f8fafc);
    border: 1px solid var(--border-strong, #cbd5e1);
    border-radius: 20px;
    font-size: 13px;
    font-weight: 700;
    color: var(--heading, #0f172a);
    text-decoration: none;
}
.fav-wallet-pill:hover {
    background: var(--border, #f1f5f9);
}
.fav-wallet-label {
    color: var(--muted, #64748b);
    font-weight: 500;
}
</style>

<div class="fav-account-nav-wrap">
    <nav class="fav-account-nav" aria-label="Customer Account Navigation">
        <div class="fav-nav-tabs" role="tablist">
            <a href="/account/digital" class="fav-nav-tab <?= $tab === 'library' ? 'active' : '' ?>" role="tab" aria-selected="<?= $tab === 'library' ? 'true' : 'false' ?>">
                📚 Digital Library
            </a>
            <a href="/account/orders" class="fav-nav-tab <?= $tab === 'orders' ? 'active' : '' ?>" role="tab" aria-selected="<?= $tab === 'orders' ? 'true' : 'false' ?>">
                📋 Orders
            </a>
            <a href="/account/membership" class="fav-nav-tab <?= $tab === 'membership' ? 'active' : '' ?>" role="tab" aria-selected="<?= $tab === 'membership' ? 'true' : 'false' ?>">
                👑 Membership
            </a>
            <a href="/account/refunds" class="fav-nav-tab <?= $tab === 'refunds' ? 'active' : '' ?>" role="tab" aria-selected="<?= $tab === 'refunds' ? 'true' : 'false' ?>">
                💰 Refunds
            </a>
            <a href="/account/downloads" class="fav-nav-tab <?= $tab === 'downloads' ? 'active' : '' ?>" role="tab" aria-selected="<?= $tab === 'downloads' ? 'true' : 'false' ?>">
                📥 Downloads
            </a>
            <a href="/account/wallet" class="fav-nav-tab <?= $tab === 'wallet' ? 'active' : '' ?>" role="tab" aria-selected="<?= $tab === 'wallet' ? 'true' : 'false' ?>">
                👛 Wallet
            </a>
        </div>
        <div class="fav-wallet-badge-wrap">
            <a href="/account/wallet" class="fav-wallet-pill" title="View Digital Wallet & Recharge">
                👛 <span class="fav-wallet-label">Wallet:</span> <?= htmlspecialchars($currencySymbol, ENT_QUOTES, 'UTF-8') ?><?= htmlspecialchars($walletBalance, ENT_QUOTES, 'UTF-8') ?>
            </a>
        </div>
    </nav>
</div>
