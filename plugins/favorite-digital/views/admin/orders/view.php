<?php
/**
 * Admin Single Order View — Favorite Digital
 */

$orderCurrency = $order->currency ?? (class_exists(\FavoriteCMS\Core\Currency::class) ? \FavoriteCMS\Core\Currency::getPrimaryCurrency() : 'BDT');
$orderSymbol   = class_exists(\FavoriteCMS\Core\Currency::class) ? \FavoriteCMS\Core\Currency::getSymbol($orderCurrency) : '৳';

$orderTotalNum = (float)($order->total_amount ?? 0);
$totalPaidMinor = 0;
if (!empty($order->payments)) {
    foreach ($order->payments as $p) {
        if ($p->status === 'completed' || $p->status === 'paid') {
            $totalPaidMinor += (int)round((float)$p->amount_paid * 100);
        }
    }
}
$orderTotalMinor      = (int)round($orderTotalNum * 100);
$totalRefundedMinor   = 0;
if (!empty($refunds)) {
    foreach ($refunds as $ref) {
        if ($ref->status === 'completed') {
            $totalRefundedMinor += (int)round((float)$ref->refund_amount * 100);
        }
    }
}

$netRetainedMinor     = max(0, $totalPaidMinor - $totalRefundedMinor);
$remainingUnpaidMinor = max(0, $orderTotalMinor - $totalPaidMinor);

$orderTotalFormatted      = $accounting['order_total'] ?? number_format($orderTotalMinor / 100, 2, '.', '');
$totalReceivedFormatted   = $accounting['total_received'] ?? number_format($totalPaidMinor / 100, 2, '.', '');
$totalRefundedFormatted   = $accounting['total_refunded'] ?? number_format($totalRefundedMinor / 100, 2, '.', '');
$netRetainedFormatted     = $accounting['net_retained'] ?? number_format($netRetainedMinor / 100, 2, '.', '');
$remainingUnpaidFormatted = $accounting['remaining_unpaid'] ?? number_format($remainingUnpaidMinor / 100, 2, '.', '');
$refundableFormatted      = $accounting['refundable_amount'] ?? number_format(max(0, $totalPaidMinor - $totalRefundedMinor) / 100, 2, '.', '');

$savedRetained = $order->retained_amount ?? null;
if ($savedRetained !== null) {
    $defaultRetainedVal = number_format((float)$savedRetained, 2, '.', '');
} elseif ($order->status === 'partial') {
    $defaultRetainedVal = $netRetainedFormatted;
} else {
    $defaultRetainedVal = $totalReceivedFormatted;
}
?>

<style>
/* Favorite Digital Admin Order Details - Light & Dark Mode Tokens */
.fd-order-view {
    color: var(--admin-text, #1e293b);
    max-width: 1400px;
    margin: 0 auto;
}
.fd-order-view .postbox,
.fd-order-view .fd-card {
    background: var(--admin-surface, #ffffff);
    border: 1px solid var(--admin-border, #e2e8f0);
    border-radius: var(--radius-md, 6px);
    box-shadow: var(--shadow-sm, 0 1px 2px rgba(0,0,0,0.05));
    color: var(--admin-text, #1e293b);
    padding: 18px;
    margin-bottom: 20px;
    box-sizing: border-box;
}
.fd-order-view h1,
.fd-order-view h2,
.fd-order-view h3,
.fd-order-view h4 {
    color: var(--admin-text-heading, #0f172a);
}
.fd-order-view hr {
    border: 0;
    border-top: 1px solid var(--admin-border, #e2e8f0);
    margin: 16px 0;
}
/* Tables */
.fd-order-view table.wp-list-table,
.fd-order-view table.widefat {
    width: 100%;
    border-collapse: collapse;
    background: var(--admin-surface, #ffffff);
    border: 1px solid var(--admin-border, #e2e8f0);
    border-radius: 4px;
    overflow: hidden;
}
.fd-order-view table.wp-list-table th,
.fd-order-view table.widefat th {
    background: var(--admin-table-th-bg, #f8fafc);
    color: var(--admin-table-th-text, #334155);
    border-bottom: 2px solid var(--admin-border, #e2e8f0);
    padding: 10px 12px;
    font-size: 12px;
    font-weight: 600;
}
.fd-order-view table.wp-list-table td,
.fd-order-view table.widefat td {
    background: var(--admin-surface, #ffffff);
    color: var(--admin-text, #1e293b);
    border-bottom: 1px solid var(--admin-border, #e2e8f0);
    padding: 10px 12px;
    vertical-align: middle;
}
.fd-order-view table.wp-list-table.striped tbody tr:nth-child(odd) td,
.fd-order-view table.widefat.striped tbody tr:nth-child(odd) td {
    background: var(--admin-surface-subtle, #f8fafc);
}
.fd-order-view table.wp-list-table tbody tr:hover td,
.fd-order-view table.widefat tbody tr:hover td {
    background: var(--admin-table-row-hover, #f1f5f9);
}
/* Form Controls */
.fd-order-view select,
.fd-order-view input[type="text"],
.fd-order-view input[type="number"],
.fd-order-view textarea {
    background: var(--admin-input-bg, #ffffff);
    color: var(--admin-input-text, #0f172a);
    border: 1px solid var(--admin-input-border, #cbd5e1);
    border-radius: var(--radius-md, 4px);
    padding: 7px 10px;
    font-size: 13px;
    box-sizing: border-box;
    transition: border-color 0.15s, box-shadow 0.15s;
}
.fd-order-view select:focus,
.fd-order-view input[type="text"]:focus,
.fd-order-view input[type="number"]:focus,
.fd-order-view textarea:focus {
    border-color: var(--admin-border-focus, #3b82f6);
    outline: none;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25);
}
/* Partial Fulfillment / Settlement Panel */
.fd-order-view .fd-partial-panel {
    background: var(--admin-surface, #ffffff);
    border: 1px solid var(--admin-border, #e2e8f0);
    border-left: 4px solid var(--admin-primary, #2563eb);
    border-radius: var(--radius-md, 6px);
    box-shadow: var(--shadow-sm, 0 1px 2px rgba(0,0,0,0.05));
    padding: 18px;
    margin-top: 20px;
}
.fd-order-view .fd-summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
    gap: 12px;
    margin: 14px 0;
}
.fd-order-view .fd-summary-card {
    background: var(--admin-surface-subtle, #f8fafc);
    border: 1px solid var(--admin-border, #e2e8f0);
    border-radius: 6px;
    padding: 10px 12px;
}
.fd-order-view .fd-summary-card .fd-label {
    font-size: 11px;
    text-transform: uppercase;
    font-weight: 600;
    color: var(--admin-text-muted, #64748b);
    margin-bottom: 4px;
}
.fd-order-view .fd-summary-card .fd-val {
    font-size: 15px;
    font-weight: 700;
    color: var(--admin-text-heading, #0f172a);
}
/* Status badges */
.badge-partial, .badge.bg-partial {
    background: #fef3c7 !important;
    color: #92400e !important;
    border: 1px solid #fde68a !important;
}
.badge-partially_refunded, .badge.bg-partially_refunded {
    background: #ede9fe !important;
    color: #5b21b6 !important;
    border: 1px solid #ddd6fe !important;
}
/* Dark theme explicit overrides */
[data-admin-theme="dark"] .fd-order-view .postbox,
[data-admin-theme="dark"] .fd-order-view .fd-card {
    background: var(--admin-surface) !important;
    border-color: var(--admin-border) !important;
    color: var(--admin-text) !important;
}
[data-admin-theme="dark"] .fd-order-view .fd-summary-card {
    background: var(--admin-surface-subtle) !important;
    border-color: var(--admin-border) !important;
}
[data-admin-theme="dark"] .fd-order-view .fd-summary-card .fd-label {
    color: var(--admin-text-muted) !important;
}
[data-admin-theme="dark"] .fd-order-view .fd-summary-card .fd-val {
    color: var(--admin-text-heading) !important;
}
[data-admin-theme="dark"] .fd-order-view pre {
    background: var(--admin-surface-subtle) !important;
    color: var(--admin-info-text, #38bdf8) !important;
    border: 1px solid var(--admin-border) !important;
}
[data-admin-theme="dark"] .fd-order-view select,
[data-admin-theme="dark"] .fd-order-view input[type="text"],
[data-admin-theme="dark"] .fd-order-view input[type="number"],
[data-admin-theme="dark"] .fd-order-view textarea {
    background: var(--admin-input-bg) !important;
    border-color: var(--admin-input-border) !important;
    color: var(--admin-input-text) !important;
}
[data-admin-theme="dark"] .fd-order-view .button-link-delete {
    background: var(--admin-danger-bg) !important;
    color: var(--admin-danger-text) !important;
    border-color: var(--admin-danger-border) !important;
}
[data-admin-theme="dark"] .fd-order-view .button-link-delete:hover {
    background: var(--admin-danger) !important;
    color: #ffffff !important;
}
[data-admin-theme="dark"] .badge-partial,
[data-admin-theme="dark"] .badge.bg-partial {
    background: var(--admin-warning-bg) !important;
    color: var(--admin-warning-text) !important;
    border-color: var(--admin-warning-border) !important;
}
[data-admin-theme="dark"] .badge-partially_refunded,
[data-admin-theme="dark"] .badge.bg-partially_refunded {
    background: rgba(168, 85, 247, 0.15) !important;
    color: #d8b4fe !important;
    border-color: rgba(168, 85, 247, 0.35) !important;
}
[data-admin-theme="dark"] .fd-order-view .fd-accept-order-box div {
    background: var(--admin-surface, #1e293b);
    border-color: var(--admin-border, #334155);
    color: var(--admin-text, #f1f5f9);
}
[data-admin-theme="dark"] .fd-order-view .fd-partial-panel {
    background: var(--admin-surface, #0f172a) !important;
    border-color: var(--admin-border, #334155) !important;
    color: var(--admin-text, #f1f5f9) !important;
}
[data-admin-theme="dark"] .fd-order-view .fd-deliverables-panel {
    background: var(--admin-surface, #0f172a) !important;
    border-color: var(--admin-border, #334155) !important;
    color: var(--admin-text, #f1f5f9) !important;
}
[data-admin-theme="dark"] .fd-order-view #fd-add-deliverable-box {
    background: var(--admin-surface, #1e293b) !important;
    border-color: var(--admin-border, #334155) !important;
    color: var(--admin-text, #f1f5f9) !important;
}
[data-admin-theme="dark"] .fd-order-view #fd-add-deliverable-box label {
    color: var(--admin-text, #f1f5f9) !important;
}
[data-admin-theme="dark"] .fd-order-view #fd-add-deliverable-box input,
[data-admin-theme="dark"] .fd-order-view #fd-add-deliverable-box select,
[data-admin-theme="dark"] .fd-order-view #fd-add-deliverable-box textarea {
    background: var(--admin-input-bg, #0f172a) !important;
    border-color: var(--admin-input-border, #475569) !important;
    color: var(--admin-input-text, #f1f5f9) !important;
}
</style>

<?php
$hasServiceItem = false;
foreach ($order->items ?? [] as $it) {
    if (($it->product_type ?? '') === 'service') {
        $hasServiceItem = true;
        break;
    }
}
?>

<div class="wrap fd-order-view">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 15px;">
        <h1 style="margin: 0;">Order <?= htmlspecialchars((string)$order->order_number, ENT_QUOTES, 'UTF-8') ?></h1>
        <a href="/admin/page/favorite-digital-orders" class="button">&larr; Back to Orders</a>
    </div>

    <?php if (!empty($flashSuccess)): ?>
        <div class="notice notice-success is-dismissible"><p><?= htmlspecialchars((string)$flashSuccess, ENT_QUOTES, 'UTF-8') ?></p></div>
    <?php endif; ?>
    <?php if (!empty($flashError)): ?>
        <div class="notice notice-error is-dismissible"><p><?= htmlspecialchars((string)$flashError, ENT_QUOTES, 'UTF-8') ?></p></div>
    <?php endif; ?>

    <div style="display: flex; gap: 20px; flex-wrap: wrap;">
        <!-- Left details column -->
        <div style="flex: 2; min-width: 320px;">
            <div class="postbox">
                <h2>Order Items</h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Item / Product</th>
                            <th>Type</th>
                            <th style="text-align: right;">Unit Price</th>
                            <th style="text-align: right;">Discount</th>
                            <th style="text-align: right;">Final Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order->items as $item): ?>
                            <?php $snapshot = $item->snapshot ?? []; ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars((string)($snapshot['title'] ?? 'Product #' . $item->product_id), ENT_QUOTES, 'UTF-8') ?></strong>
                                    <?php if ($item->product_type === 'service'): ?>
                                        <div style="margin-top: 4px;">
                                            <span class="badge badge-<?= htmlspecialchars((string)$order->status, ENT_QUOTES, 'UTF-8') ?>" style="font-size: 11px;">
                                                Service Status: <?= strtoupper(htmlspecialchars((string)$order->status, ENT_QUOTES, 'UTF-8')) ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($snapshot['slug'])): ?>
                                        <div style="font-size: 11px; color: var(--admin-text-muted, #64748b);">Slug: <?= htmlspecialchars((string)$snapshot['slug'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($snapshot['attributes'])): ?>
                                        <details style="margin-top: 5px; font-size: 11px; color: var(--admin-text-muted, #64748b);">
                                            <summary style="cursor: pointer;">Snapshot Attributes</summary>
                                            <pre style="margin: 3px 0; padding: 5px; border-radius: 3px;"><?= htmlspecialchars(json_encode($snapshot['attributes'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?></pre>
                                        </details>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge"><?= strtoupper(htmlspecialchars((string)$item->product_type, ENT_QUOTES, 'UTF-8')) ?></span></td>
                                <td style="text-align: right;"><?= htmlspecialchars((string)$item->currency, ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars((string)$item->unit_price, ENT_QUOTES, 'UTF-8') ?></td>
                                <td style="text-align: right;"><?= htmlspecialchars((string)$item->discount_percent, ENT_QUOTES, 'UTF-8') ?>%</td>
                                <td style="text-align: right; font-weight: bold;"><?= htmlspecialchars((string)$item->currency, ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars((string)$item->final_price, ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div style="margin-top: 20px; text-align: right; border-top: 1px solid var(--admin-border, #ddd); padding-top: 10px;">
                    <p style="margin: 4px 0;"><strong>Subtotal:</strong> <?= htmlspecialchars((string)$order->currency, ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars((string)$order->subtotal_amount, ENT_QUOTES, 'UTF-8') ?></p>
                    <p style="margin: 4px 0; color: var(--admin-danger, #dc2626);"><strong>Discount:</strong> -<?= htmlspecialchars((string)$order->currency, ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars((string)$order->discount_amount, ENT_QUOTES, 'UTF-8') ?></p>
                    <h3 style="margin: 6px 0;"><strong>Total:</strong> <?= htmlspecialchars((string)$order->currency, ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars((string)$order->total_amount, ENT_QUOTES, 'UTF-8') ?></h3>
                </div>
            </div>

            <!-- Payment Settlements & Financial Breakdown -->
            <!-- Payment Settlements & Financial Breakdown -->
            <div class="postbox">
                <h2>Payment Settlements & Accounting</h2>
                <div class="fd-summary-grid">
                    <div class="fd-summary-card">
                        <div class="fd-label">Order Total</div>
                        <div class="fd-val"><?= htmlspecialchars($orderSymbol) ?><?= htmlspecialchars($orderTotalFormatted) ?></div>
                    </div>
                    <div class="fd-summary-card">
                        <div class="fd-label">Amount Received</div>
                        <div class="fd-val" style="color: var(--admin-success-text, #166534);"><?= htmlspecialchars($orderSymbol) ?><?= htmlspecialchars($totalReceivedFormatted) ?></div>
                    </div>
                    <div class="fd-summary-card">
                        <div class="fd-label">Amount Retained</div>
                        <div class="fd-val" style="color: var(--admin-primary, #2563eb);"><?= htmlspecialchars($orderSymbol) ?><?= htmlspecialchars($netRetainedFormatted) ?></div>
                    </div>
                    <div class="fd-summary-card">
                        <div class="fd-label">Amount Refunded</div>
                        <div class="fd-val" style="color: var(--admin-danger-text, #991b1b);"><?= htmlspecialchars($orderSymbol) ?><?= htmlspecialchars($totalRefundedFormatted) ?></div>
                    </div>
                    <div class="fd-summary-card">
                        <div class="fd-label">Remaining Unpaid</div>
                        <div class="fd-val" style="color: var(--admin-text-muted, #64748b);"><?= htmlspecialchars($orderSymbol) ?><?= htmlspecialchars($remainingUnpaidFormatted) ?></div>
                    </div>
                    <div class="fd-summary-card">
                        <div class="fd-label">Refundable Amount</div>
                        <div class="fd-val" style="color: var(--admin-warning-text, #b45309);"><?= htmlspecialchars($orderSymbol) ?><?= htmlspecialchars($refundableFormatted) ?></div>
                    </div>
                </div>

                <h3 style="margin-top: 15px; margin-bottom: 8px;">Settlement Transactions</h3>
                <?php if (!empty($order->payments)): ?>
                    <table class="widefat fixed striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Method</th>
                                <th>Status</th>
                                <th style="text-align: right;">Amount</th>
                                <th>Transaction / Reference</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order->payments as $pay): ?>
                                <tr>
                                    <td>#<?= (int)$pay->id ?></td>
                                    <td><strong><?= htmlspecialchars(strtoupper((string)$pay->payment_method), ENT_QUOTES, 'UTF-8') ?></strong></td>
                                    <td>
                                        <span class="badge badge-<?= htmlspecialchars((string)$pay->status, ENT_QUOTES, 'UTF-8') ?>">
                                            <?= htmlspecialchars(ucfirst((string)$pay->status), ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </td>
                                    <td style="text-align: right; font-weight: bold;"><?= htmlspecialchars(class_exists(\FavoriteCMS\Core\Currency::class) ? \FavoriteCMS\Core\Currency::getSymbol($pay->currency ?? $orderCurrency) : '৳', ENT_QUOTES, 'UTF-8') ?><?= htmlspecialchars((string)$pay->amount_paid, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <?php if (!empty($pay->favorite_pay_tx_id)): ?>
                                            <code>FP: <?= htmlspecialchars((string)$pay->favorite_pay_tx_id, ENT_QUOTES, 'UTF-8') ?></code>
                                        <?php endif; ?>
                                        <?php if (!empty($pay->wallet_tx_id)): ?>
                                            <code>Wallet Tx: #<?= htmlspecialchars((string)$pay->wallet_tx_id, ENT_QUOTES, 'UTF-8') ?></code>
                                        <?php endif; ?>
                                        <?php if (empty($pay->favorite_pay_tx_id) && empty($pay->wallet_tx_id)): ?>
                                            <em>N/A</em>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars(fdig_format_datetime($pay->created_at), ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="color: var(--admin-text-muted);"><em>No payment records found for this order.</em></p>
                <?php endif; ?>
            </div>

            <!-- Service Deliverables Section -->
            <?php if ($hasServiceItem): ?>
                <div class="postbox fd-deliverables-panel" id="service-deliverables-panel" style="border-left: 4px solid var(--admin-primary, #2271b1);">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 8px;">
                        <h2 style="margin: 0;">Service Deliverables</h2>
                        <button type="button" class="button button-secondary" onclick="toggleDeliverableForm()" style="font-weight: 600;">
                            + Add Deliverable
                        </button>
                    </div>
                    <p style="font-size: 13px; color: var(--admin-text-muted); margin-bottom: 12px; line-height: 1.5;">
                        Attach customer-accessible deliverables (invoices, reports, certificates, project files) to this service order using the existing resource architecture.
                    </p>

                    <!-- Add Deliverable Form (collapsible) -->
                    <div id="fd-add-deliverable-box" style="display: none; background: var(--admin-surface-subtle, #f8fafc); border: 1px solid var(--admin-border, #cbd5e1); border-radius: 6px; padding: 16px; margin-bottom: 20px;">
                        <h3 style="margin-top: 0; margin-bottom: 12px; font-size: 14px; font-weight: 700; color: var(--admin-text, #1e293b);">Add Service Deliverable</h3>
                        <form method="post" action="/admin/page/favorite-digital-orders" enctype="multipart/form-data">
                            <input type="hidden" name="_token" value="<?= htmlspecialchars((string)$csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="action" value="add_deliverable">
                            <input type="hidden" name="id" value="<?= (int)$order->id ?>">

                            <div style="margin-bottom: 12px;">
                                <label style="display: block; font-weight: 600; font-size: 13px; margin-bottom: 4px;">Deliverable Title <span style="color:red;">*</span></label>
                                <input type="text" name="deliverable_title" placeholder="e.g. Initial Report, Final Report, Certificate, Invoice" required style="width: 100%; font-size: 13px;">
                            </div>

                            <div style="margin-bottom: 12px;">
                                <label style="display: block; font-weight: 600; font-size: 13px; margin-bottom: 4px;">Description (Optional)</label>
                                <textarea name="description" rows="2" placeholder="Optional notes or instructions for the customer" style="width: 100%; font-size: 13px;"></textarea>
                            </div>

                            <div style="margin-bottom: 12px;">
                                <label style="display: block; font-weight: 600; font-size: 13px; margin-bottom: 4px;">Resource Type <span style="color:red;">*</span></label>
                                <select name="resource_type" id="fd_deliv_resource_type" onchange="onDeliverableTypeChange()" style="width: 100%;">
                                    <option value="file" selected>Direct Upload (Automatic File Size)</option>
                                    <option value="url">External Resource URL (Manual File Size)</option>
                                </select>
                            </div>

                            <!-- Direct Upload Block -->
                            <div id="fd_deliv_file_block" style="margin-bottom: 14px;">
                                <label style="display: block; font-weight: 600; font-size: 13px; margin-bottom: 4px;">Upload Document / File <span style="color:red;">*</span></label>
                                <input type="file" name="deliverable_file" id="fd_deliv_file_input" style="display: block; width: 100%;">
                                <small style="color: var(--admin-text-muted); display: block; margin-top: 4px;">
                                    Supported formats: .pdf, .docx, .xlsx, .zip, .png, .jpg, .json, etc. File size is automatically detected upon upload.
                                </small>
                            </div>

                            <!-- External URL Block -->
                            <div id="fd_deliv_url_block" style="display: none; margin-bottom: 14px;">
                                <div style="margin-bottom: 10px;">
                                    <label style="display: block; font-weight: 600; font-size: 13px; margin-bottom: 4px;">External Resource URL <span style="color:red;">*</span></label>
                                    <input type="url" name="resource_url" id="fd_deliv_url_input" placeholder="https://drive.google.com/... or https://example.com/file.pdf" style="width: 100%; font-size: 13px;">
                                    <small style="color: var(--admin-text-muted); display: block; margin-top: 4px;">
                                        Must use safe HTTP or HTTPS protocol.
                                    </small>
                                </div>

                                <div>
                                    <label style="display: block; font-weight: 600; font-size: 13px; margin-bottom: 4px;">File Size (Manual Input)</label>
                                    <div style="display: flex; gap: 8px; align-items: center;">
                                        <input type="number" step="any" min="0" name="manual_file_size_val" placeholder="e.g. 4.8" style="flex: 1; font-size: 13px;">
                                        <select name="manual_file_size_unit" style="width: 100px; font-size: 13px;">
                                            <option value="MB" selected>MB</option>
                                            <option value="GB">GB</option>
                                            <option value="KB">KB</option>
                                            <option value="B">Bytes</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div style="margin-bottom: 14px;">
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 600; font-size: 13px;">
                                    <input type="checkbox" name="is_released" value="1" checked>
                                    <span>Release to customer immediately (make accessible now)</span>
                                </label>
                                <small style="color: var(--admin-text-muted); display: block; margin-left: 24px; margin-top: 2px;">
                                    If unchecked, deliverable is stored as draft and hidden from customer until explicitly released.
                                </small>
                            </div>

                            <div style="display: flex; gap: 10px;">
                                <button type="submit" class="button button-primary" style="font-weight: bold;">
                                    Save Deliverable
                                </button>
                                <button type="button" class="button" onclick="toggleDeliverableForm()">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Deliverables Table / List -->
                    <?php if (!empty($deliverables)): ?>
                        <table class="widefat fixed striped">
                            <thead>
                                <tr>
                                    <th style="width: 25%;">Deliverable</th>
                                    <th style="width: 15%;">Type</th>
                                    <th style="width: 25%;">Source / File</th>
                                    <th style="width: 12%;">Size</th>
                                    <th style="width: 11%;">Status</th>
                                    <th style="width: 12%; text-align: right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($deliverables as $deliv): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars((string)$deliv->title, ENT_QUOTES, 'UTF-8') ?></strong>
                                            <?php if (!empty($deliv->description)): ?>
                                                <div style="font-size: 11px; color: var(--admin-text-muted); margin-top: 2px;">
                                                    <?= htmlspecialchars((string)$deliv->description, ENT_QUOTES, 'UTF-8') ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($deliv->resource_type === 'url'): ?>
                                                <span class="badge" style="background: rgba(59, 130, 246, 0.15); color: #2563eb; border: 1px solid rgba(59, 130, 246, 0.3);">External URL</span>
                                            <?php else: ?>
                                                <span class="badge" style="background: rgba(16, 185, 129, 0.15); color: #059669; border: 1px solid rgba(16, 185, 129, 0.3);">Direct Upload</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($deliv->resource_type === 'url'): ?>
                                                <a href="<?= htmlspecialchars((string)$deliv->resource_url, ENT_QUOTES, 'UTF-8') ?>" target="_blank" style="word-break: break-all; font-size: 12px;">
                                                    <?= htmlspecialchars((string)$deliv->resource_url, ENT_QUOTES, 'UTF-8') ?> &nearr;
                                                </a>
                                            <?php else: ?>
                                                <span style="font-size: 12px;">
                                                    📄 <?= htmlspecialchars((string)($deliv->file_name ?? basename($deliv->file_path ?? 'file')), ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars(fdig_format_bytes((int)$deliv->file_size), ENT_QUOTES, 'UTF-8') ?></strong>
                                        </td>
                                        <td>
                                            <?php if ((int)$deliv->is_released === 1): ?>
                                                <span class="badge badge-completed" style="background: var(--admin-success-bg, #dcfce7); color: var(--admin-success-text, #15803d); border: 1px solid var(--admin-success-border, #86efac); padding: 2px 8px; border-radius: 4px; font-weight: 700; font-size: 11px;">
                                                    ✓ Released
                                                </span>
                                            <?php else: ?>
                                                <span class="badge" style="background: var(--admin-surface-muted, #f1f5f9); color: var(--admin-text-muted, #64748b); border: 1px solid var(--admin-border, #cbd5e1); padding: 2px 8px; border-radius: 4px; font-weight: 700; font-size: 11px;">
                                                    Draft / Unreleased
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align: right;">
                                            <div style="display: inline-flex; gap: 4px; align-items: center;">
                                                <form method="post" action="/admin/page/favorite-digital-orders" style="display: inline;">
                                                    <input type="hidden" name="_token" value="<?= htmlspecialchars((string)$csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                                    <input type="hidden" name="action" value="toggle_deliverable_release">
                                                    <input type="hidden" name="id" value="<?= (int)$order->id ?>">
                                                    <input type="hidden" name="deliverable_id" value="<?= (int)$deliv->id ?>">
                                                    <button type="submit" class="button button-small" title="<?= (int)$deliv->is_released === 1 ? 'Unrelease (hide from customer)' : 'Release (make available to customer)' ?>">
                                                        <?= (int)$deliv->is_released === 1 ? 'Unrelease' : 'Release' ?>
                                                    </button>
                                                </form>
                                                <a href="/download/<?= htmlspecialchars((string)$deliv->download_token, ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="button button-small" title="Download / Test Access">
                                                    ⬇
                                                </a>
                                                <form method="post" action="/admin/page/favorite-digital-orders" style="display: inline;" onsubmit="return confirm('Delete this deliverable?');">
                                                    <input type="hidden" name="_token" value="<?= htmlspecialchars((string)$csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                                    <input type="hidden" name="action" value="delete_deliverable">
                                                    <input type="hidden" name="id" value="<?= (int)$order->id ?>">
                                                    <input type="hidden" name="deliverable_id" value="<?= (int)$deliv->id ?>">
                                                    <button type="submit" class="button button-small button-link-delete" title="Delete deliverable">
                                                        &times;
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div style="padding: 16px; background: var(--admin-surface-subtle, #f8fafc); border: 1px dashed var(--admin-border, #cbd5e1); border-radius: 6px; text-align: center; color: var(--admin-text-muted);">
                            <p style="margin: 0; font-size: 13px;">No service deliverables added yet. Click <strong>+ Add Deliverable</strong> to attach reports, certificates, or project files.</p>
                        </div>
                    <?php endif; ?>

                    <script>
                    function toggleDeliverableForm() {
                        const box = document.getElementById('fd-add-deliverable-box');
                        box.style.display = (box.style.display === 'none' || box.style.display === '') ? 'block' : 'none';
                    }
                    function onDeliverableTypeChange() {
                        const type = document.getElementById('fd_deliv_resource_type').value;
                        const fileBlock = document.getElementById('fd_deliv_file_block');
                        const urlBlock = document.getElementById('fd_deliv_url_block');
                        const fileInput = document.getElementById('fd_deliv_file_input');
                        const urlInput = document.getElementById('fd_deliv_url_input');

                        if (type === 'url') {
                            fileBlock.style.display = 'none';
                            urlBlock.style.display = 'block';
                            fileInput.removeAttribute('required');
                            urlInput.setAttribute('required', 'required');
                        } else {
                            fileBlock.style.display = 'block';
                            urlBlock.style.display = 'none';
                            urlInput.removeAttribute('required');
                            fileInput.setAttribute('required', 'required');
                        }
                    }
                    </script>
                </div>
            <?php endif; ?>

            <?php if (!empty($entitlements)): ?>
                <div class="postbox">
                    <h2>Granted Entitlements & Access</h2>
                    <table class="widefat fixed striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Product / Item</th>
                                <th>Source Type</th>
                                <th>Status</th>
                                <th>Granted At</th>
                                <th>Expires At</th>
                                <th>Downloads</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($entitlements as $ent): ?>
                                <tr>
                                    <td>#<?= (int)$ent->id ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars((string)($ent->product_title ?? 'Product #' . $ent->product_id), ENT_QUOTES, 'UTF-8') ?></strong>
                                    </td>
                                    <td><span class="badge"><?= htmlspecialchars(strtoupper((string)$ent->source_type), ENT_QUOTES, 'UTF-8') ?></span></td>
                                    <td>
                                        <span class="badge badge-<?= htmlspecialchars((string)$ent->status, ENT_QUOTES, 'UTF-8') ?>">
                                            <?= htmlspecialchars(ucfirst((string)$ent->status), ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars(fdig_format_datetime($ent->granted_at), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= !empty($ent->expires_at) ? htmlspecialchars(fdig_format_datetime($ent->expires_at), ENT_QUOTES, 'UTF-8') : '<em>Lifetime</em>' ?></td>
                                    <td><?= ($ent->product_type === 'digital') ? (isset($ent->download_count) ? (int)$ent->download_count . ' / 3 used' : '0 / 3 used') : '—' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <!-- Refund Audit & History -->
            <?php if (!empty($refunds)): ?>
                <div class="postbox" style="border-left: 4px solid var(--admin-danger, #d63638);">
                    <h2>Refund Audit & History</h2>
                    <table class="widefat fixed striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Amount</th>
                                <th>Method / Destination</th>
                                <th>Reference / Tx ID</th>
                                <th>Reason / Note</th>
                                <th>Status</th>
                                <th>Processed At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($refunds as $ref): ?>
                                <?php
                                $methodLabel = match ($ref->refund_method ?? $ref->destination ?? 'wallet') {
                                    'manual_bkash' => 'Manual Refund (bKash)',
                                    'manual_nagad' => 'Manual Refund (Nagad)',
                                    'manual_rocket' => 'Manual Refund (Rocket)',
                                    'manual_bank' => 'Manual Refund (Bank Transfer)',
                                    'manual_other', 'manual' => 'Manual Refund (Other/Cash)',
                                    'wallet' => 'Customer Digital Wallet',
                                    default => strtoupper((string)($ref->refund_method ?? $ref->destination ?? 'manual')),
                                };
                                $isWallet = (($ref->destination ?? '') === 'wallet' || ($ref->refund_method ?? '') === 'wallet');
                                ?>
                                <tr>
                                    <td>#<?= (int)$ref->id ?></td>
                                    <td><strong style="color: var(--admin-danger-text, #dc2626);"><?= htmlspecialchars((string)$ref->currency, ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars((string)$ref->refund_amount, ENT_QUOTES, 'UTF-8') ?></strong></td>
                                    <td>
                                        <span class="badge <?= $isWallet ? 'badge-info' : 'badge-secondary' ?>">
                                            <?= htmlspecialchars($methodLabel, ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($ref->reference)): ?>
                                            <code>Ref: <?= htmlspecialchars((string)$ref->reference, ENT_QUOTES, 'UTF-8') ?></code>
                                        <?php endif; ?>
                                        <?php if (!empty($ref->wallet_transaction_id)): ?>
                                            <code>Wallet Tx: #<?= (int)$ref->wallet_transaction_id ?></code>
                                        <?php endif; ?>
                                        <?php if (empty($ref->reference) && empty($ref->wallet_transaction_id)): ?>
                                            <em>N/A</em>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars((string)$ref->reason, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><span class="badge badge-success"><?= htmlspecialchars(ucfirst((string)$ref->status), ENT_QUOTES, 'UTF-8') ?></span></td>
                                    <td><?= htmlspecialchars(fdig_format_datetime($ref->processed_at), ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <?php if (!empty($order->notes)): ?>
                <div class="postbox">
                    <h3>Order Notes</h3>
                    <p style="line-height: 1.5; color: var(--admin-text);"><?= nl2br(htmlspecialchars((string)$order->notes, ENT_QUOTES, 'UTF-8')) ?></p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Right sidebar column -->
        <div style="flex: 1; min-width: 260px;">
            <div class="postbox">
                <h2>Order Overview</h2>
                <p><strong>Order ID:</strong> #<?= (int)$order->id ?></p>
                <p><strong>Customer ID:</strong> User #<?= (int)$order->user_id ?></p>
                <p><strong>Created:</strong> <?= htmlspecialchars(fdig_format_datetime($order->created_at), ENT_QUOTES, 'UTF-8') ?></p>
                <p><strong>Last Updated:</strong> <?= htmlspecialchars(fdig_format_datetime($order->updated_at), ENT_QUOTES, 'UTF-8') ?></p>

                <div style="margin: 12px 0; padding: 10px; background: var(--admin-surface-subtle, #f8fafc); border: 1px solid var(--admin-border, #e2e8f0); border-radius: 6px;">
                    <div style="margin-bottom: 6px; display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 12px; font-weight: 600; color: var(--admin-text-muted, #64748b);">Order Status:</span>
                        <span class="badge badge-<?= htmlspecialchars((string)$order->status, ENT_QUOTES, 'UTF-8') ?>" style="font-weight: 700; font-size: 11px;">
                            <?= strtoupper(htmlspecialchars((string)$order->status, ENT_QUOTES, 'UTF-8')) ?>
                        </span>
                    </div>
                    <div style="margin-bottom: 6px; display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 12px; font-weight: 600; color: var(--admin-text-muted, #64748b);">Payment Status:</span>
                        <span class="badge badge-<?= htmlspecialchars((string)$order->payment_status, ENT_QUOTES, 'UTF-8') ?>" style="font-weight: 700; font-size: 11px;">
                            <?= strtoupper(htmlspecialchars((string)$order->payment_status, ENT_QUOTES, 'UTF-8')) ?>
                        </span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 12px; font-weight: 600; color: var(--admin-text-muted, #64748b);">Fulfillment Status:</span>
                        <span class="badge badge-<?= htmlspecialchars((string)$order->fulfillment_status, ENT_QUOTES, 'UTF-8') ?>" style="font-weight: 700; font-size: 11px;">
                            <?= strtoupper(htmlspecialchars((string)$order->fulfillment_status, ENT_QUOTES, 'UTF-8')) ?>
                        </span>
                    </div>
                </div>

                <?php
                $hasServiceItem = false;
                if (!empty($order->items)) {
                    foreach ($order->items as $it) {
                        if (($it->product_type ?? '') === 'service') {
                            $hasServiceItem = true;
                            break;
                        }
                    }
                }
                $isPendingPayment = ($order->payment_status === 'pending' || $order->payment_status === 'unpaid');
                ?>

                <div class="fd-order-status-control-box" style="margin-top: 15px;">
                    <h3 style="margin-top: 0; margin-bottom: 8px;">Order Status</h3>

                    <?php if ($order->status === 'cancelled'): ?>
                        <div style="padding: 10px; background: var(--admin-danger-bg, #fee2e2); border: 1px solid var(--admin-danger-border, #fca5a5); border-radius: 6px; color: var(--admin-danger-text, #991b1b); font-size: 12px; line-height: 1.4;">
                            <strong>Order Cancelled (Terminal)</strong><br>
                            This order was cancelled due to payment rejection or failure. Cancel is a payment-controlled state and cannot be modified.
                        </div>
                    <?php elseif ($order->status === 'refunded'): ?>
                        <div style="padding: 10px; background: var(--admin-danger-bg, #fee2e2); border: 1px solid var(--admin-danger-border, #fca5a5); border-radius: 6px; color: var(--admin-danger-text, #991b1b); font-size: 12px; line-height: 1.4;">
                            <strong>Order Refunded (Terminal)</strong><br>
                            This order is fully refunded and all customer access has been revoked. Status cannot be modified.
                        </div>
                    <?php else: ?>
                        <?php if ($order->status === 'pending'): ?>
                            <div style="margin-bottom: 10px; padding: 8px 10px; background: var(--admin-warning-bg, #fef3c7); border: 1px solid var(--admin-warning-border, #fde68a); border-radius: 6px; color: var(--admin-warning-text, #92400e); font-size: 11px; line-height: 1.4;">
                                <strong>Payment Pending:</strong> Order is awaiting payment verification by Favorite Pay.
                            </div>
                        <?php endif; ?>

                        <form method="post" action="/admin/page/favorite-digital-orders" id="fd_order_status_form">
                            <input type="hidden" name="_token" value="<?= htmlspecialchars((string)$csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="id" value="<?= (int)$order->id ?>">

                            <div style="margin-bottom: 12px;">
                                <label for="fd_main_status_select" style="font-weight: 600; font-size: 13px;">Change Status To:</label>
                                <select name="status" id="fd_main_status_select" style="width: 100%; margin-top: 5px; font-size: 13px; font-weight: 600;" onchange="onStatusDropdownChange()">
                                    <?php if ($order->status === 'pending'): ?>
                                        <option value="" disabled selected>-- Select New Status --</option>
                                    <?php endif; ?>
                                    <option value="processing" <?= ($order->status === 'processing') ? 'selected' : '' ?>>Processing</option>
                                    <option value="partial" <?= ($order->status === 'partial') ? 'selected' : '' ?>>Partial</option>
                                    <option value="completed" <?= ($order->status === 'completed') ? 'selected' : '' ?>>Complete</option>
                                    <option value="refunded" <?= ($order->status === 'refunded') ? 'selected' : '' ?>>Refund</option>
                                </select>
                            </div>

                            <!-- Conditional container for Partial Refund -->
                            <div id="fd_status_partial_fields" style="display: none; margin-bottom: 12px; padding: 12px; background: var(--admin-surface-subtle, #f8fafc); border: 1px solid var(--admin-warning-border, #fde68a); border-radius: 6px;">
                                <?php if (!$hasServiceItem): ?>
                                    <div style="color: var(--admin-danger-text, #dc2626); font-size: 12px; font-weight: 600; line-height: 1.4;">
                                        ⚠️ Partial refund is not allowed for Digital Product orders.
                                    </div>
                                <?php else: ?>
                                    <div style="margin-bottom: 8px;">
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                                            <label for="fd_status_refund_amount" style="font-size: 12px; font-weight: 600;">Refund Amount [BDT]:</label>
                                            <span style="font-size: 11px; color: var(--admin-text-muted, #64748b);">Max: <strong><?= htmlspecialchars($orderSymbol) ?><?= htmlspecialchars((string)$refundableFormatted) ?></strong></span>
                                        </div>
                                        <input type="number" step="0.01" min="0.01" max="<?= htmlspecialchars((string)$refundableFormatted, ENT_QUOTES, 'UTF-8') ?>" name="refund_amount" id="fd_status_refund_amount" placeholder="0.00" style="width: 100%; font-size: 13px;" oninput="validateRefundAmounts()">
                                    </div>
                                    <div style="font-size: 11px; color: var(--admin-text-muted, #64748b); margin-bottom: 8px;">
                                        Remaining refundable: <strong><?= htmlspecialchars($orderSymbol) ?><?= htmlspecialchars((string)$refundableFormatted) ?></strong>. Released deliverables will remain accessible.
                                    </div>
                                    <label for="fd_status_refund_reason" style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 4px;">Settlement / Refund Reason:</label>
                                    <input type="text" name="refund_reason" id="fd_status_refund_reason" placeholder="e.g. Partial refund for completed milestones" style="width: 100%; font-size: 12px;">
                                <?php endif; ?>
                            </div>

                            <!-- Conditional container for Full Refund -->
                            <div id="fd_status_refund_fields" style="display: none; margin-bottom: 12px; padding: 12px; background: var(--admin-danger-bg, #fee2e2); border: 1px solid var(--admin-danger-border, #fca5a5); border-radius: 6px;">
                                <div style="font-size: 12px; color: var(--admin-danger-text, #991b1b); font-weight: 600; margin-bottom: 6px;">
                                    ⚠️ Full Refund Action
                                </div>
                                <div style="font-size: 11px; color: var(--admin-danger-text, #991b1b); line-height: 1.4; margin-bottom: 8px;">
                                    Remaining refundable: <strong><?= htmlspecialchars($orderSymbol) ?><?= htmlspecialchars((string)$refundableFormatted) ?></strong> will be credited to customer's Favorite Pay wallet and all access will be revoked immediately.
                                </div>
                                <label for="fd_status_full_reason" style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 4px; color: var(--admin-danger-text, #991b1b);">Refund Reason <span style="color:red;">*</span>:</label>
                                <textarea name="full_refund_reason" id="fd_status_full_reason" rows="2" placeholder="Required reason for full refund" style="width: 100%; font-size: 12px;"></textarea>
                            </div>

                            <div id="fd_status_warning" style="display: none; margin-bottom: 10px; padding: 8px; background: var(--admin-danger-bg, #fee2e2); border: 1px solid var(--admin-danger-border, #fca5a5); border-radius: 4px; color: var(--admin-danger-text, #991b1b); font-size: 12px;"></div>

                            <button type="submit" id="fd_btn_save_status" class="button button-primary" style="width: 100%; font-weight: bold;">
                                Update Order Status
                            </button>
                        </form>

                        <script>
                        const maxRefundable = <?= json_encode((float)$refundableFormatted) ?>;
                        const orderSymbol = <?= json_encode($orderSymbol) ?>;
                        const hasService = <?= json_encode((bool)$hasServiceItem) ?>;
                        const isPaid = <?= json_encode($order->payment_status === 'paid') ?>;

                        function validateRefundAmounts() {
                            const selEl = document.getElementById('fd_main_status_select');
                            if (!selEl) return;
                            const sel = selEl.value;
                            const warnBox = document.getElementById('fd_status_warning');
                            const saveBtn = document.getElementById('fd_btn_save_status');
                            const amountInput = document.getElementById('fd_status_refund_amount');

                            if (sel === 'partial' && hasService && amountInput) {
                                const val = parseFloat(amountInput.value);
                                if (!isNaN(val) && val > maxRefundable) {
                                    if (warnBox) {
                                        warnBox.style.display = 'block';
                                        warnBox.textContent = 'Refund amount cannot exceed the remaining refundable amount of ' + orderSymbol + maxRefundable.toFixed(2) + '.';
                                    }
                                    if (saveBtn) saveBtn.disabled = true;
                                    return;
                                }
                            }
                            if (warnBox && sel !== 'completed') {
                                warnBox.style.display = 'none';
                            }
                            if (saveBtn && !(sel === 'partial' && !hasService) && !(sel === 'completed' && !isPaid)) {
                                saveBtn.disabled = false;
                            }
                        }

                        function onStatusDropdownChange() {
                            const selEl = document.getElementById('fd_main_status_select');
                            if (!selEl) return;
                            const sel = selEl.value;
                            const partialBox = document.getElementById('fd_status_partial_fields');
                            const refundBox = document.getElementById('fd_status_refund_fields');
                            const warnBox = document.getElementById('fd_status_warning');
                            const saveBtn = document.getElementById('fd_btn_save_status');

                            if (partialBox) partialBox.style.display = 'none';
                            if (refundBox) refundBox.style.display = 'none';
                            if (warnBox) warnBox.style.display = 'none';
                            if (saveBtn) {
                                saveBtn.disabled = false;
                                saveBtn.textContent = 'Update Order Status';
                            }

                            if (sel === 'partial') {
                                if (partialBox) partialBox.style.display = 'block';
                                if (!hasService) {
                                    if (saveBtn) saveBtn.disabled = true;
                                    if (warnBox) {
                                        warnBox.style.display = 'block';
                                        warnBox.textContent = 'Partial refund is not allowed for Digital Product orders.';
                                    }
                                } else {
                                    if (saveBtn) saveBtn.textContent = 'Process Partial Refund';
                                    validateRefundAmounts();
                                }
                            } else if (sel === 'refunded') {
                                if (refundBox) refundBox.style.display = 'block';
                                if (saveBtn) saveBtn.textContent = 'Issue Full Refund';
                            } else if (sel === 'completed') {
                                if (!isPaid) {
                                    if (saveBtn) saveBtn.disabled = true;
                                    if (warnBox) {
                                        warnBox.style.display = 'block';
                                        warnBox.textContent = 'Cannot set order to Complete before payment is confirmed.';
                                    }
                                } else {
                                    if (saveBtn) saveBtn.textContent = 'Update Order Status';
                                }
                            } else if (sel === 'processing') {
                                if (saveBtn) saveBtn.textContent = 'Update Order Status';
                            }
                        }
                        document.addEventListener('DOMContentLoaded', function() {
                            onStatusDropdownChange();
                        });
                        </script>
                    <?php endif; ?>
                </div>

                <?php if ($order->payment_status === 'paid' && $order->fulfillment_status !== 'fulfilled'): ?>
                    <div style="margin-top: 15px; border-top: 1px solid var(--admin-border, #ddd); padding-top: 15px;">
                        <form method="post" action="/admin/page/favorite-digital-orders">
                            <input type="hidden" name="_token" value="<?= htmlspecialchars((string)$csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="action" value="fulfill">
                            <input type="hidden" name="id" value="<?= (int)$order->id ?>">
                            <button type="submit" class="button button-secondary" style="width: 100%; font-weight: bold;">
                                ⚡ Fulfill / Retry Fulfillment
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
