<?php
/**
 * Customer Membership Dashboard View (/account/membership)
 *
 * Rendered within CustomerThemeShell.
 *
 * @var object|null $activeMembership
 * @var bool        $hasActive
 * @var array       $allMemberships
 * @var array       $coveredPerks
 * @var array       $wallet
 * @var string      $siteCurrency
 * @var int         $userId
 * @var string      $activeTab
 * @var string      $csrfToken
 * @var int         $remainingDays
 * @var string      $remainingDaysText
 * @var bool        $supportsAutoRenew
 * @var string|null $purchaseDate
 * @var string|null $startDate
 * @var string|null $expiryDate
 * @var string|null $flashSuccess
 * @var string|null $flashError
 */
?>

<style>
.fdig-membership-container {
    max-width: 1000px;
    margin: 0 auto;
    padding: 10px 0 40px;
}
.fdig-card {
    background: var(--surface, #ffffff);
    border: 1px solid var(--border, #e2e8f0);
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
}
.fdig-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 1px solid var(--border, #f1f5f9);
}
.fdig-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    border-radius: 9999px;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}
.fdig-badge-success {
    background: #dcfce7;
    color: #15803d;
    border: 1px solid #bbf7d0;
}
.fdig-badge-warning {
    background: #fef3c7;
    color: #b45309;
    border: 1px solid #fde68a;
}
.fdig-badge-muted {
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
}
.fdig-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}
.fdig-info-box {
    background: var(--surface-muted, #f8fafc);
    border: 1px solid var(--border, #e2e8f0);
    border-radius: 8px;
    padding: 14px 16px;
}
.fdig-info-label {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--muted, #64748b);
    margin-bottom: 6px;
}
.fdig-info-val {
    font-size: 15px;
    font-weight: 700;
    color: var(--heading, #0f172a);
    word-break: break-word;
}
.fdig-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 20px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    border: 1px solid transparent;
    transition: all 0.15s ease-in-out;
}
.fdig-btn-primary {
    background: var(--accent, #2563eb);
    color: #ffffff;
}
.fdig-btn-primary:hover {
    filter: brightness(0.92);
    color: #ffffff;
}
.fdig-btn-secondary {
    background: var(--surface, #ffffff);
    border: 1px solid var(--border, #cbd5e1);
    color: var(--text, #334155);
}
.fdig-btn-secondary:hover {
    background: var(--surface-muted, #f8fafc);
}
.fdig-alert {
    padding: 12px 16px;
    border-radius: 8px;
    font-size: 14px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.fdig-alert-success {
    background: #dcfce7;
    border: 1px solid #bbf7d0;
    color: #15803d;
}
.fdig-alert-error {
    background: #fee2e2;
    border: 1px solid #fecaca;
    color: #991b1b;
}
.fdig-empty-state {
    text-align: center;
    padding: 48px 24px;
}
.fdig-table-wrap {
    overflow-x: auto;
    border-radius: 8px;
    border: 1px solid var(--border, #e2e8f0);
}
.fdig-table {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
    font-size: 14px;
}
.fdig-table th {
    background: var(--surface-muted, #f8fafc);
    padding: 12px 16px;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--muted, #64748b);
    border-bottom: 1px solid var(--border, #e2e8f0);
}
.fdig-table td {
    padding: 14px 16px;
    border-bottom: 1px solid var(--border, #f1f5f9);
    color: var(--text, #1e293b);
}
.fdig-table tr:last-child td {
    border-bottom: none;
}
</style>

<div class="fdig-membership-container">
    <!-- Page Header -->
    <div style="margin-bottom: 24px;">
        <h1 style="margin: 0 0 6px; font-size: 26px; font-weight: 800; color: var(--heading, #0f172a); letter-spacing: -0.02em;">Membership</h1>
        <p style="margin: 0; font-size: 14px; color: var(--muted, #64748b);">Manage your active subscription, validity, renewal preferences, and member perks.</p>
    </div>

    <!-- Flash Messages -->
    <?php if (!empty($flashSuccess)): ?>
        <div class="fdig-alert fdig-alert-success">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
            <span><?= htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    <?php endif; ?>

    <?php if (!empty($flashError)): ?>
        <div class="fdig-alert fdig-alert-error">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            <span><?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    <?php endif; ?>

    <?php if ($hasActive && $activeMembership): ?>
        <!-- ACTIVE MEMBERSHIP CARD -->
        <div class="fdig-card">
            <div class="fdig-card-header">
                <div>
                    <span style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--accent, #2563eb);">
                        Active Membership Tier
                    </span>
                    <h2 style="margin: 4px 0 0; font-size: 22px; font-weight: 800; color: var(--heading, #0f172a);">
                        <?= htmlspecialchars((string)($activeMembership->plan_title ?? 'VIP Membership'), ENT_QUOTES, 'UTF-8') ?>
                    </h2>
                </div>
                <div>
                    <?php if ($activeMembership->status === 'active'): ?>
                        <span class="fdig-badge fdig-badge-success">ACTIVE</span>
                    <?php elseif ($activeMembership->status === 'grace'): ?>
                        <span class="fdig-badge fdig-badge-warning">GRACE PERIOD</span>
                    <?php else: ?>
                        <span class="fdig-badge fdig-badge-muted"><?= strtoupper(htmlspecialchars((string)$activeMembership->status, ENT_QUOTES, 'UTF-8')) ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Details Grid -->
            <div class="fdig-grid">
                <div class="fdig-info-box">
                    <div class="fdig-info-label">Package / Plan</div>
                    <div class="fdig-info-val">
                        <?= htmlspecialchars((string)($activeMembership->plan_title ?? 'Membership'), ENT_QUOTES, 'UTF-8') ?>
                    </div>
                </div>

                <div class="fdig-info-box">
                    <div class="fdig-info-label">Purchase Date</div>
                    <div class="fdig-info-val">
                        <?= htmlspecialchars(fdig_format_date($purchaseDate ?? $activeMembership->created_at ?? $activeMembership->started_at, 'd M Y'), ENT_QUOTES, 'UTF-8') ?>
                    </div>
                </div>

                <div class="fdig-info-box">
                    <div class="fdig-info-label">Start Date</div>
                    <div class="fdig-info-val">
                        <?= htmlspecialchars(fdig_format_date($startDate ?? $activeMembership->started_at, 'd M Y'), ENT_QUOTES, 'UTF-8') ?>
                    </div>
                </div>

                <div class="fdig-info-box">
                    <div class="fdig-info-label">Expiry Date</div>
                    <div class="fdig-info-val">
                        <?= htmlspecialchars(fdig_format_datetime($expiryDate ?? $activeMembership->expires_at), ENT_QUOTES, 'UTF-8') ?>
                    </div>
                </div>

                <div class="fdig-info-box">
                    <div class="fdig-info-label">Remaining Time</div>
                    <div class="fdig-info-val" style="color: var(--accent, #2563eb);">
                        <?= htmlspecialchars($remainingDaysText !== '' ? $remainingDaysText : 'Active', ENT_QUOTES, 'UTF-8') ?>
                    </div>
                </div>

                <div class="fdig-info-box">
                    <div class="fdig-info-label">Auto-Renewal</div>
                    <div class="fdig-info-val">
                        <?php if (!empty($activeMembership->auto_renew)): ?>
                            <span style="color: #15803d; display: inline-flex; align-items: center; gap: 5px;">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                Auto-renewal is ON
                            </span>
                        <?php else: ?>
                            <span style="color: var(--muted, #64748b); display: inline-flex; align-items: center; gap: 5px;">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line></svg>
                                Auto-renewal is OFF
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if ($activeMembership->status === 'grace'): ?>
                <div class="fdig-alert fdig-alert-error" style="margin-bottom: 20px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                    <span>Your membership is currently in the grace period. Please extend your subscription to prevent loss of access.</span>
                </div>
            <?php endif; ?>

            <!-- Action Buttons -->
            <div style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center; padding-top: 8px; border-top: 1px solid var(--border, #f1f5f9);">
                <?php if ($supportsAutoRenew): ?>
                    <form action="/account/membership/toggle-auto-renew" method="POST" style="margin: 0; display: inline;">
                        <input type="hidden" name="_token" value="<?= htmlspecialchars((string)($csrfToken ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars((string)($csrfToken ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        <?php if (!empty($activeMembership->auto_renew)): ?>
                            <button type="submit" class="fdig-btn fdig-btn-secondary">
                                Turn Off Auto-Renewal
                            </button>
                        <?php else: ?>
                            <button type="submit" class="fdig-btn fdig-btn-primary">
                                Turn On Auto-Renewal
                            </button>
                        <?php endif; ?>
                    </form>
                <?php endif; ?>

                <a href="/store?product_type=membership" class="fdig-btn fdig-btn-secondary">
                    Extend / Renew Plan
                </a>

                <a href="/account/digital?product_type=membership" class="fdig-btn fdig-btn-secondary">
                    View Member Perks in Library
                </a>
            </div>
        </div>

        <!-- Member Perks -->
        <?php if (!empty($coveredPerks)): ?>
            <div class="fdig-card">
                <h3 style="margin: 0 0 16px; font-size: 18px; font-weight: 700; color: var(--heading, #0f172a);">
                    Unlocked Member Resources
                </h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 16px;">
                    <?php foreach ($coveredPerks as $perk): ?>
                        <div style="background: var(--surface-muted, #f8fafc); border: 1px solid var(--border, #e2e8f0); border-radius: 8px; padding: 16px; display: flex; flex-direction: column; justify-content: space-between;">
                            <div>
                                <h4 style="margin: 0 0 6px; font-size: 15px; font-weight: 700; color: var(--heading, #0f172a);">
                                    <?= htmlspecialchars((string)$perk->title, ENT_QUOTES, 'UTF-8') ?>
                                </h4>
                                <p style="margin: 0 0 12px; font-size: 12px; color: var(--muted, #64748b);">
                                    <?= htmlspecialchars(substr((string)($perk->description ?? ''), 0, 85), ENT_QUOTES, 'UTF-8') ?>...
                                </p>
                            </div>
                            <a href="/store/<?= htmlspecialchars((string)$perk->slug, ENT_QUOTES, 'UTF-8') ?>" class="fdig-btn fdig-btn-primary" style="padding: 6px 12px; font-size: 13px;">
                                Access Resource
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <!-- EMPTY STATE CARD -->
        <div class="fdig-card fdig-empty-state">
            <div style="width: 56px; height: 56px; border-radius: 28px; background: #eff6ff; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 16px; color: var(--accent, #2563eb);">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3l3 6 3-6 3 6 3-6v14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V3z"></path></svg>
            </div>
            <h2 style="margin: 0 0 8px; font-size: 20px; font-weight: 800; color: var(--heading, #0f172a);">No active membership</h2>
            <p style="margin: 0 auto 24px; max-width: 480px; font-size: 14px; color: var(--muted, #64748b); line-height: 1.6;">
                You do not currently have an active membership plan. Choose a membership to unlock unlimited downloads, exclusive resources, and priority support.
            </p>
            <a href="/store?product_type=membership" class="fdig-btn fdig-btn-primary">
                Choose a Membership
            </a>
        </div>
    <?php endif; ?>

    <!-- Past Subscriptions History -->
    <?php if (!empty($allMemberships)): ?>
        <div class="fdig-card" style="margin-top: 32px;">
            <h3 style="margin: 0 0 16px; font-size: 18px; font-weight: 700; color: var(--heading, #0f172a);">
                Subscription History
            </h3>
            <div class="fdig-table-wrap">
                <table class="fdig-table">
                    <thead>
                        <tr>
                            <th>Plan</th>
                            <th>Status</th>
                            <th>Start Date</th>
                            <th>Expiry Date</th>
                            <th>Auto-Renew</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allMemberships as $m): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars((string)($m->plan_title ?? 'Membership'), ENT_QUOTES, 'UTF-8') ?></strong></td>
                                <td>
                                    <?php if ($m->status === 'active'): ?>
                                        <span class="fdig-badge fdig-badge-success">ACTIVE</span>
                                    <?php elseif ($m->status === 'grace'): ?>
                                        <span class="fdig-badge fdig-badge-warning">GRACE</span>
                                    <?php else: ?>
                                        <span class="fdig-badge fdig-badge-muted"><?= strtoupper(htmlspecialchars((string)$m->status, ENT_QUOTES, 'UTF-8')) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars(fdig_format_date($m->started_at ?? $m->created_at, 'd M Y'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars(fdig_format_datetime($m->expires_at), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= !empty($m->auto_renew) ? 'Enabled' : 'Disabled' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>
