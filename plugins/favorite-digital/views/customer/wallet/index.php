<?php
/**
 * Customer Digital Wallet Hub View
 *
 * @var object $wallet
 * @var string $balance
 * @var string $currency
 * @var array $regularLimits
 * @var array|null $binanceLimits
 * @var array $availableGateways
 * @var array $transactions
 * @var int $totalTransactions
 * @var int $page
 * @var int $perPage
 * @var int $totalPages
 * @var array $recharges
 * @var string $csrfToken
 * @var int $userId
 * @var string|null $flashError
 * @var string|null $flashSuccess
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Digital Wallet — Favorite CMS</title>
    <style>
        .fav-wallet-container {
            max-width: 1100px;
            margin: 0 auto 40px;
            padding: 0 16px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            color: var(--text, #1e293b);
        }
        .fav-alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 500;
        }
        .fav-alert-success {
            background: var(--success-soft, #ecfdf5);
            color: var(--success, #065f46);
            border: 1px solid var(--success-border, #a7f3d0);
        }
        .fav-alert-error {
            background: var(--danger-soft, #fef2f2);
            color: var(--danger, #991b1b);
            border: 1px solid var(--danger-border, #fecaca);
        }
        .fav-wallet-header {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 32px;
        }
        @media (max-width: 768px) {
            .fav-wallet-header {
                grid-template-columns: 1fr;
            }
        }
        .fav-card {
            background: var(--surface, #ffffff);
            border: 1px solid var(--border, #e2e8f0);
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        }
        .fav-card-title {
            font-size: 18px;
            font-weight: 700;
            margin: 0 0 16px;
            color: var(--heading, #0f172a);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .fav-balance-display {
            display: flex;
            align-items: baseline;
            gap: 8px;
            margin: 12px 0;
        }
        .fav-balance-currency {
            font-size: 20px;
            font-weight: 600;
            color: var(--muted, #64748b);
        }
        .fav-balance-val {
            font-size: 40px;
            font-weight: 800;
            color: var(--heading, #0f172a);
            letter-spacing: -0.5px;
        }
        .fav-status-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .fav-status-active {
            background: rgba(16, 185, 129, 0.15);
            color: var(--success, #059669);
        }
        .fav-status-suspended {
            background: rgba(239, 68, 68, 0.15);
            color: var(--danger, #dc2626);
        }
        .fav-status-pending {
            background: rgba(245, 158, 11, 0.15);
            color: #d97706;
        }
        .fav-status-completed, .fav-status-succeeded {
            background: rgba(16, 185, 129, 0.15);
            color: var(--success, #059669);
        }
        .fav-status-failed {
            background: rgba(239, 68, 68, 0.15);
            color: var(--danger, #dc2626);
        }
        .fav-status-expired {
            background: var(--surface-muted, #f1f5f9);
            color: var(--muted, #64748b);
        }
        .fav-form-group {
            margin-bottom: 16px;
        }
        .fav-label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 6px;
            color: var(--text, #334155);
        }
        .fav-input-wrap {
            position: relative;
            display: flex;
            align-items: center;
        }
        .fav-input-prefix {
            position: absolute;
            left: 14px;
            font-size: 16px;
            font-weight: 600;
            color: var(--muted, #64748b);
        }
        .fav-input {
            width: 100%;
            padding: 10px 14px 10px 36px;
            border: 1px solid var(--border-strong, #cbd5e1);
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            color: var(--heading, #0f172a);
            background: var(--surface, #ffffff);
            box-sizing: border-box;
            transition: border-color 0.15s;
        }
        .fav-input:focus {
            outline: none;
            border-color: var(--accent, #2563eb);
            box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
        }
        .fav-quick-amounts {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 8px;
        }
        .fav-quick-btn {
            background: var(--surface-muted, #f1f5f9);
            border: 1px solid var(--border, #e2e8f0);
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            color: var(--muted, #475569);
            cursor: pointer;
            transition: all 0.15s;
        }
        .fav-quick-btn:hover {
            background: var(--border, #e2e8f0);
            color: var(--heading, #0f172a);
        }
        .fav-gateways-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 10px;
            margin-top: 8px;
        }
        .fav-gateway-label {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border: 1px solid var(--border-strong, #cbd5e1);
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            color: var(--text, #334155);
            background: var(--surface, #ffffff);
            transition: all 0.15s;
        }
        .fav-gateway-label:hover {
            border-color: var(--border-strong, #94a3b8);
            background: var(--surface-muted, #f8fafc);
        }
        .fav-gateway-label input[type="radio"]:checked + span {
            color: var(--accent, #2563eb);
        }
        .fav-gateway-label:has(input[type="radio"]:checked) {
            border-color: var(--accent, #2563eb);
            background: var(--accent-soft, #eff6ff);
        }
        .fav-manual-box {
            background: var(--surface-muted, #f8fafc);
            border: 1px solid var(--border-strong, #cbd5e1);
            border-radius: 8px;
            padding: 16px;
            margin-top: 14px;
            font-size: 13px;
        }
        .fav-manual-box h3 {
            margin: 0 0 10px;
            font-size: 14px;
            font-weight: 700;
            color: var(--heading, #0f172a);
        }
        .fav-manual-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 10px;
            margin-bottom: 12px;
        }
        .fav-manual-item {
            background: var(--surface, #ffffff);
            border: 1px solid var(--border, #e2e8f0);
            border-radius: 6px;
            padding: 8px 12px;
        }
        .fav-manual-item-lbl {
            font-size: 11px;
            font-weight: 600;
            color: var(--muted, #64748b);
            text-transform: uppercase;
            margin-bottom: 2px;
        }
        .fav-manual-item-val {
            font-size: 14px;
            font-weight: 700;
            color: var(--heading, #0f172a);
        }
        .fav-manual-instructions {
            background: rgba(245, 158, 11, 0.12);
            border: 1px solid rgba(245, 158, 11, 0.3);
            border-radius: 6px;
            padding: 10px 12px;
            color: #d97706;
            margin-bottom: 12px;
            font-size: 13px;
            line-height: 1.5;
        }
        .fav-manual-inputs-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-top: 12px;
        }
        .fav-manual-inputs-grid .fav-full-span {
            grid-column: 1 / -1;
        }
        .fav-manual-inputs-grid label {
            font-size: 12px;
            font-weight: 600;
            color: var(--text, #334155);
            display: block;
            margin-bottom: 4px;
        }
        .fav-manual-inputs-grid input, .fav-manual-inputs-grid textarea {
            width: 100%;
            box-sizing: border-box;
            padding: 8px 10px;
            border: 1px solid var(--border-strong, #cbd5e1);
            border-radius: 6px;
            font-size: 13px;
            background: var(--surface, #ffffff);
            color: var(--text, #1e293b);
        }
        @media (max-width: 600px) {
            .fav-manual-inputs-grid {
                grid-template-columns: 1fr;
            }
        }
        .fav-btn-primary {
            display: inline-block;
            width: 100%;
            background: var(--accent, #2563eb);
            color: #ffffff;
            font-size: 15px;
            font-weight: 700;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            box-sizing: border-box;
            transition: background 0.15s;
        }
        .fav-btn-primary:hover {
            background: var(--accent-hover, #1d4ed8);
        }
        .fav-btn-primary:disabled {
            background: var(--muted, #94a3b8);
            cursor: not-allowed;
        }
        .fav-btn-sm {
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 600;
            border-radius: 6px;
            text-decoration: none;
            display: inline-block;
            cursor: pointer;
            border: none;
        }
        .fav-table-wrap {
            overflow-x: auto;
            margin-top: 16px;
        }
        .fav-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 14px;
        }
        .fav-table th {
            background: var(--surface-muted, #f8fafc);
            padding: 12px 16px;
            font-weight: 600;
            color: var(--muted, #475569);
            border-bottom: 1px solid var(--border, #e2e8f0);
        }
        .fav-table td {
            padding: 12px 16px;
            border-bottom: 1px solid var(--border, #f1f5f9);
            color: var(--text, #334155);
        }
        .fav-table tr:hover td {
            background: var(--surface-muted, #f8fafc);
        }
        .fav-badge-type {
            display: inline-flex;
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .fav-badge-recharge, .fav-badge-credit {
            background: rgba(16, 185, 129, 0.15);
            color: var(--success, #059669);
        }
        .fav-badge-refund_credit {
            background: rgba(37, 99, 235, 0.15);
            color: var(--accent, #2563eb);
        }
        .fav-badge-debit {
            background: var(--surface-muted, #f1f5f9);
            color: var(--muted, #475569);
        }
        .fav-badge-reversal {
            background: rgba(245, 158, 11, 0.15);
            color: #d97706;
        }
        .fav-amount-credit {
            color: var(--success, #059669);
            font-weight: 700;
        }
        .fav-amount-debit {
            color: var(--heading, #0f172a);
            font-weight: 700;
        }
        .fav-pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            margin-top: 20px;
        }
        .fav-page-link {
            padding: 6px 12px;
            border: 1px solid var(--border-strong, #cbd5e1);
            border-radius: 6px;
            text-decoration: none;
            color: var(--muted, #475569);
            background: var(--surface, #ffffff);
            font-size: 13px;
            font-weight: 600;
        }
        .fav-page-link.active {
            background: var(--accent, #2563eb);
            color: #ffffff;
            border-color: var(--accent, #2563eb);
        }
    </style>
</head>
<body>

<?php
$activeTab = 'wallet';
include __DIR__ . '/../account/nav.php';
?>

<div class="fav-wallet-container">

    <?php if ($flashSuccess): ?>
        <div class="fav-alert fav-alert-success" role="alert">
            <?= htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <?php if ($flashError): ?>
        <div class="fav-alert fav-alert-error" role="alert">
            <?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <div class="fav-wallet-header">
        <!-- Balance Card -->
        <div class="fav-card">
            <div class="fav-card-title">
                <span>👛 Digital Wallet Balance</span>
                <span class="fav-status-badge <?= $wallet->status === 'active' ? 'fav-status-active' : 'fav-status-suspended' ?>">
                    <?= htmlspecialchars(ucfirst((string)$wallet->status), ENT_QUOTES, 'UTF-8') ?>
                </span>
            </div>
            <div class="fav-balance-display">
                <span class="fav-balance-currency"><?= htmlspecialchars($currency, ENT_QUOTES, 'UTF-8') ?></span>
                <span class="fav-balance-val"><?= htmlspecialchars($balance, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <p style="color: var(--muted, #64748b); font-size: 13px; margin: 8px 0 0; line-height: 1.5;">
                ✨ Your wallet balance <strong>never expires</strong>. Recharged funds and refund credits remain safe in your account and can be used immediately during checkout for any digital product, package, or service.
            </p>
        </div>

        <!-- Recharge Card -->
        <div class="fav-card">
            <h2 class="fav-card-title" style="margin-bottom: 12px;">⚡ Recharge Wallet</h2>
            <?php if ($wallet->status !== 'active'): ?>
                <div class="fav-alert fav-alert-error">
                    Your wallet is currently suspended. Recharges are disabled.
                </div>
            <?php else: ?>
                <form action="/account/wallet/recharge" method="POST" id="rechargeForm" enctype="multipart/form-data">
                    <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

                    <div class="fav-form-group">
                        <label for="recharge_amount" class="fav-label">Recharge Amount (<?= htmlspecialchars($currency, ENT_QUOTES, 'UTF-8') ?>)</label>
                        <div class="fav-input-wrap">
                            <span class="fav-input-prefix">৳</span>
                            <input 
                                type="text" 
                                id="recharge_amount" 
                                name="amount" 
                                class="fav-input" 
                                placeholder="500.00" 
                                required 
                                pattern="^\d+(\.\d{1,2})?$"
                                autocomplete="off"
                            >
                        </div>
                        <div class="fav-quick-amounts">
                            <button type="button" class="fav-quick-btn" onclick="setRechargeAmount('100.00')">৳100</button>
                            <button type="button" class="fav-quick-btn" onclick="setRechargeAmount('500.00')">৳500</button>
                            <button type="button" class="fav-quick-btn" onclick="setRechargeAmount('1000.00')">৳1,000</button>
                            <button type="button" class="fav-quick-btn" onclick="setRechargeAmount('2000.00')">৳2,000</button>
                            <button type="button" class="fav-quick-btn" onclick="setRechargeAmount('5000.00')">৳5,000</button>
                        </div>
                        <p style="font-size: 12px; color: var(--muted, #64748b); margin: 6px 0 0;">
                            Limits: Min ৳<?= htmlspecialchars($regularLimits['min'], ENT_QUOTES, 'UTF-8') ?> — Max ৳<?= htmlspecialchars($regularLimits['max'], ENT_QUOTES, 'UTF-8') ?>
                            <?php if ($binanceLimits): ?>
                                (Binance Pay Min: ৳<?= htmlspecialchars($binanceLimits['min'], ENT_QUOTES, 'UTF-8') ?> eq. 1 USD)
                            <?php endif; ?>
                        </p>
                    </div>

                    <div class="fav-form-group">
                        <label class="fav-label">Payment Method</label>
                        <?php if (empty($availableGateways)): ?>
                            <p style="font-size: 13px; color: var(--muted, #64748b);">No payment methods currently available.</p>
                        <?php else: ?>
                            <div class="fav-gateways-grid">
                                <?php foreach ($availableGateways as $gw): ?>
                                    <label class="fav-gateway-label">
                                        <input 
                                            type="radio" 
                                            name="gateway_id" 
                                            value="<?= htmlspecialchars($gw['id'], ENT_QUOTES, 'UTF-8') ?>" 
                                            data-is-manual="<?= !empty($gw['is_manual']) ? '1' : '0' ?>"
                                            data-instructions='<?= htmlspecialchars(json_encode($gw['instructions'] ?? []), ENT_QUOTES, 'UTF-8') ?>'
                                            onchange="onRechargeGatewayChange()"
                                            required
                                        >
                                        <span><?= htmlspecialchars($gw['title'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>

                            <!-- Manual Payment Receiving Details & Inputs Box -->
                            <div class="fav-manual-box" id="manual_payment_box" style="display: none;">
                                <h3 id="manual_box_title">Manual Payment Instructions</h3>
                                
                                <div class="fav-manual-grid" id="manual_receiving_grid">
                                    <!-- Populated via JS -->
                                </div>

                                <div class="fav-manual-instructions" id="manual_instructions_text">
                                    Please send the exact recharge amount to the account above and submit your transaction reference (TrxID).
                                </div>

                                <div class="fav-manual-inputs-grid">
                                    <div>
                                        <label for="sender_account">Your Account / Sender Number:</label>
                                        <input type="text" name="sender_account" id="sender_account" placeholder="e.g., 017XXXXXXXX">
                                    </div>
                                    <div>
                                        <label for="trx_id">Transaction ID (TrxID) <span style="color: var(--danger, #dc2626);">*</span>:</label>
                                        <input type="text" name="trx_id" id="trx_id" placeholder="e.g., 9J28A74LK">
                                    </div>
                                    <div class="fav-full-span">
                                        <label for="payment_proof">Payment Proof / Screenshot (Optional):</label>
                                        <input type="file" name="payment_proof" id="payment_proof" accept="image/jpeg,image/png,image/webp,application/pdf">
                                        <div style="font-size: 11px; color: var(--muted, #64748b); margin-top: 2px;">Supported: JPG, PNG, WEBP, PDF (max 10MB)</div>
                                    </div>
                                    <div class="fav-full-span">
                                        <label for="payment_notes">Notes / Deposit Remarks (Optional):</label>
                                        <input type="text" name="notes" id="payment_notes" placeholder="Optional notes for admin verification...">
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="fav-btn-primary" <?= empty($availableGateways) ? 'disabled' : '' ?>>
                        Proceed to Payment
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Transaction History (Ledger) -->
    <section class="fav-card" style="margin-bottom: 32px;" aria-label="Wallet Transaction History">
        <h2 class="fav-card-title">
            <span>📜 Wallet Transaction Ledger</span>
            <span style="font-size: 13px; font-weight: 500; color: var(--muted, #64748b);"><?= (int)$totalTransactions ?> Total Records</span>
        </h2>

        <?php if (empty($transactions)): ?>
            <p style="color: var(--muted, #64748b); font-size: 14px; text-align: center; padding: 24px 0;">
                No wallet transactions yet.
            </p>
        <?php else: ?>
            <div class="fav-table-wrap">
                <table class="fav-table" aria-label="Wallet Transaction History">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Balance After</th>
                            <th>Reference / Description</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $tx): ?>
                            <?php
                            $isCredit = in_array($tx->type, ['recharge', 'refund_credit', 'credit', 'reversal'], true);
                            $typeLabel = match ($tx->type) {
                                'recharge'      => 'Recharge',
                                'refund_credit' => 'Refund',
                                'debit'         => 'Purchase',
                                'reversal'      => 'Reversal',
                                default         => ucfirst($tx->type),
                            };
                            ?>
                            <tr>
                                <td style="white-space: nowrap; font-size: 13px; color: var(--muted, #64748b);">
                                    <?= htmlspecialchars(fdig_format_datetime($tx->created_at), ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td>
                                    <span class="fav-badge-type fav-badge-<?= htmlspecialchars($tx->type, ENT_QUOTES, 'UTF-8') ?>">
                                         <?= htmlspecialchars($typeLabel, ENT_QUOTES, 'UTF-8') ?>
                                     </span>
                                 </td>
                                 <td class="<?= $isCredit ? 'fav-amount-credit' : 'fav-amount-debit' ?>">
                                     <?= $isCredit ? '+' : '-' ?>৳<?= htmlspecialchars($tx->amount, ENT_QUOTES, 'UTF-8') ?>
                                 </td>
                                 <td style="font-weight: 600;">
                                     ৳<?= htmlspecialchars($tx->balance_after, ENT_QUOTES, 'UTF-8') ?>
                                 </td>
                                 <td style="max-width: 320px;">
                                     <div style="font-weight: 600; font-size: 13px; color: var(--heading, #0f172a);">
                                         <?= htmlspecialchars($tx->description ?: 'Wallet ledger entry', ENT_QUOTES, 'UTF-8') ?>
                                     </div>
                                     <div style="font-size: 11px; color: var(--muted, #64748b); font-family: monospace;">
                                         <?= htmlspecialchars($tx->reference_id, ENT_QUOTES, 'UTF-8') ?>
                                     </div>
                                 </td>
                                 <td>
                                     <span class="fav-status-badge fav-status-completed">
                                         Completed
                                     </span>
                                 </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav class="fav-pagination" aria-label="Transaction Ledger Pagination">
                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                        <a href="/account/wallet?page=<?= $p ?>" class="fav-page-link <?= $p === $page ? 'active' : '' ?>">
                            <?= $p ?>
                        </a>
                    <?php endfor; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </section>

    <!-- Recharge History -->
    <section class="fav-card" aria-label="Recent Recharge History">
        <h2 class="fav-card-title">
            <span>💳 Recent Recharge History</span>
        </h2>

        <?php if (empty($recharges)): ?>
            <p style="color: var(--muted, #64748b); font-size: 14px; text-align: center; padding: 24px 0;">
                No recent recharge records found.
            </p>
        <?php else: ?>
            <div class="fav-table-wrap">
                <table class="fav-table" aria-label="Recent Recharge History">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Reference</th>
                            <th>Method</th>
                            <th>Wallet Credit</th>
                            <th>Payment Amount</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recharges as $rc): ?>
                            <tr>
                                <td style="white-space: nowrap; font-size: 13px; color: var(--muted, #64748b);">
                                    <?= htmlspecialchars(fdig_format_datetime($rc->created_at), ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td style="font-family: monospace; font-size: 12px;">
                                    <?= htmlspecialchars($rc->transaction_id, ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars(ucwords(str_replace(['_', '-'], ' ', $rc->gateway_id)), ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td style="font-weight: 700; color: var(--success, #059669);">
                                    +৳<?= htmlspecialchars($rc->wallet_amount, ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($rc->wallet_currency, ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td style="font-weight: 600;">
                                    <?= htmlspecialchars($rc->charge_amount, ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($rc->charge_currency, ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td>
                                    <span class="fav-status-badge fav-status-<?= htmlspecialchars($rc->status, ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars($rc->status_label, ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($rc->status === 'pending' || $rc->status === 'awaiting_verification'): ?>
                                        <a href="/account/wallet/recharge/manual?intent_id=<?= urlencode($rc->transaction_id) ?>" class="fav-btn-sm" style="background: var(--accent, #2563eb); color: #fff;">
                                            Submit TrxID
                                        </a>
                                    <?php elseif ($rc->status === 'failed' || $rc->status === 'expired'): ?>
                                        <form action="/account/wallet/recharge/retry" method="POST" style="display:inline;">
                                            <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="intent_id" value="<?= htmlspecialchars($rc->transaction_id, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="gateway_id" value="<?= htmlspecialchars($rc->gateway_id, ENT_QUOTES, 'UTF-8') ?>">
                                            <button type="submit" class="fav-btn-sm" style="background: var(--surface-muted, #e2e8f0); color: var(--text, #334155); border: 1px solid var(--border, #cbd5e1);">
                                                Retry
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span style="color: var(--muted, #94a3b8); font-size: 13px;">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

</div>

<script>
function setRechargeAmount(val) {
    var el = document.getElementById('recharge_amount');
    if (el) {
        el.value = val;
    }
}

function onRechargeGatewayChange() {
    var sel = document.querySelector('input[name="gateway_id"]:checked');
    var manualBox = document.getElementById('manual_payment_box');
    if (!sel || !manualBox) {
        if (manualBox) manualBox.style.display = 'none';
        setRechargeTrxRequired(false);
        return;
    }

    var isManual = sel.getAttribute('data-is-manual') === '1' || sel.value.indexOf('manual_') === 0;

    if (!isManual) {
        manualBox.style.display = 'none';
        setRechargeTrxRequired(false);
        return;
    }

    manualBox.style.display = 'block';
    setRechargeTrxRequired(true);

    var rawInst = sel.getAttribute('data-instructions');
    var inst = {};
    try {
        if (rawInst) inst = JSON.parse(rawInst);
    } catch(e) {}

    var grid = document.getElementById('manual_receiving_grid');
    if (grid) {
        var html = '';
        if (inst.account_number) {
            html += '<div class="fav-manual-item"><div class="fav-manual-item-lbl">Receiver Number / Account</div><div class="fav-manual-item-val">' + escapeRechargeHtml(inst.account_number) + '</div></div>';
        }
        if (inst.account_name) {
            html += '<div class="fav-manual-item"><div class="fav-manual-item-lbl">Account Name</div><div class="fav-manual-item-val">' + escapeRechargeHtml(inst.account_name) + '</div></div>';
        }
        if (inst.account_type) {
            html += '<div class="fav-manual-item"><div class="fav-manual-item-lbl">Account Type</div><div class="fav-manual-item-val">' + escapeRechargeHtml(inst.account_type) + '</div></div>';
        }
        if (inst.bank_name) {
            html += '<div class="fav-manual-item"><div class="fav-manual-item-lbl">Bank Name</div><div class="fav-manual-item-val">' + escapeRechargeHtml(inst.bank_name) + '</div></div>';
        }
        if (inst.branch_name) {
            html += '<div class="fav-manual-item"><div class="fav-manual-item-lbl">Branch</div><div class="fav-manual-item-val">' + escapeRechargeHtml(inst.branch_name) + '</div></div>';
        }
        if (inst.routing_no) {
            html += '<div class="fav-manual-item"><div class="fav-manual-item-lbl">Routing Number</div><div class="fav-manual-item-val">' + escapeRechargeHtml(inst.routing_no) + '</div></div>';
        }
        grid.innerHTML = html;
    }

    var instrText = document.getElementById('manual_instructions_text');
    if (instrText) {
        var text = inst.instructions || inst.reference_instructions || 'Please send the exact recharge amount to the account above and submit your transaction reference (TrxID).';
        instrText.textContent = text;
    }
}

function setRechargeTrxRequired(required) {
    var trxInput = document.getElementById('trx_id');
    if (trxInput) {
        if (required) {
            trxInput.setAttribute('required', 'required');
        } else {
            trxInput.removeAttribute('required');
        }
    }
}

function escapeRechargeHtml(text) {
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

document.addEventListener('DOMContentLoaded', function() {
    var activeGw = document.querySelector('input[name="gateway_id"]:checked');
    if (activeGw) {
        onRechargeGatewayChange();
    }
});
</script>

</body>
</html>
