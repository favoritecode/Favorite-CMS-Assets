<?php
/**
 * Customer "My Orders" View
 */
?>
<div class="customer-orders-container" style="max-width: 900px; margin: 30px auto; padding: 24px; background: var(--surface, #ffffff); border: 1px solid var(--border, #e2e8f0); border-radius: 12px;">
    <style>
        .customer-orders-container h1 { margin: 0 0 6px; font-size: 24px; font-weight: 800; color: var(--heading, #0f172a); }
        .customer-orders-container p.desc { margin: 0 0 20px; font-size: 14px; color: var(--muted, #64748b); }
        .orders-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .orders-table th { padding: 12px 14px; background: var(--surface-muted, #f8fafc); border-bottom: 2px solid var(--border-strong, #cbd5e1); color: var(--muted, #64748b); font-size: 12px; font-weight: 700; text-transform: uppercase; }
        .orders-table td { padding: 12px 14px; color: var(--text, #1e293b); border-bottom: 1px solid var(--border, #e2e8f0); font-size: 14px; }
        .orders-table tr:last-child td { border-bottom: none; }
        .order-badge { background: var(--surface-muted, #f1f5f9); color: var(--text, #334155); border: 1px solid var(--border, #e2e8f0); padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: 700; }
        .btn-download {
            background: var(--surface-muted, #f1f5f9);
            color: var(--text, #1e293b);
            border: 1px solid var(--border, #cbd5e1);
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            display: inline-block;
            margin-right: 6px;
            transition: all 0.15s ease;
        }
        .btn-download:hover:not(:disabled):not(.btn-disabled) {
            background: var(--accent-soft, #e0f2fe);
            border-color: var(--accent, #2563eb);
            color: var(--accent, #2563eb);
        }
        .btn-download:disabled, .btn-download.btn-disabled {
            opacity: 0.55;
            cursor: not-allowed;
            background: var(--surface-muted, #f1f5f9);
            color: var(--muted, #94a3b8);
            border-color: var(--border, #e2e8f0);
            pointer-events: none;
        }
        .btn-receipt { background: var(--accent, #2563eb); color: #fff; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 600; display: inline-block; }
        .btn-receipt:hover { opacity: 0.9; }
        .empty-orders { padding: 32px 20px; text-align: center; background: var(--surface-muted, #f8fafc); border: 1px solid var(--border, #e2e8f0); border-radius: 8px; color: var(--muted, #64748b); }
    </style>

    <h1>My Orders</h1>
    <p class="desc">View your purchase history and order receipts.</p>

    <?php if (empty($orders)): ?>
        <div class="empty-orders">
            You have not placed any orders yet.
        </div>
    <?php else: ?>
        <table class="orders-table">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Payment</th>
                    <th style="text-align: right;">Total</th>
                    <th style="text-align: center;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <?php
                    $isService = !empty($order->has_service);
                    if (!$isService && isset($order->items)) {
                        foreach ($order->items as $item) {
                            if (($item->product_type ?? '') === 'service') {
                                $isService = true;
                                break;
                            }
                        }
                    }

                    $isRefundedOrRevoked = ($order->status === 'refunded' || ($order->fulfillment_status ?? '') === 'revoked');
                    $releasedCount = (int)($order->released_deliverables_count ?? 0);
                    $isEligibleStatus = in_array($order->status, ['processing', 'partial', 'completed', 'complete'], true);

                    if ($isService) {
                        $canDownload = (!$isRefundedOrRevoked && $releasedCount > 0 && $isEligibleStatus);
                        $downloadUrl = '/account/orders/' . urlencode((string)$order->order_number) . '#service-deliverables';
                        $disabledTitle = $isRefundedOrRevoked
                            ? 'Access to deliverables has been revoked'
                            : ($releasedCount === 0 ? 'No released deliverables available' : 'Deliverables currently unavailable');
                    } else {
                        $hasDigital = !empty($order->has_digital);
                        if (!$hasDigital && isset($order->items)) {
                            foreach ($order->items as $item) {
                                if (($item->product_type ?? '') === 'digital') {
                                    $hasDigital = true;
                                    break;
                                }
                            }
                        }
                        $canDownload = (!$isRefundedOrRevoked && $isEligibleStatus);
                        $downloadUrl = '/account/downloads';
                        $disabledTitle = $isRefundedOrRevoked ? 'Access revoked' : 'Downloads currently unavailable';
                    }
                    ?>
                    <tr>
                        <td><strong style="color: var(--heading, #0f172a);"><?= htmlspecialchars((string)$order->order_number, ENT_QUOTES, 'UTF-8') ?></strong></td>
                        <td><?= htmlspecialchars(fdig_format_date($order->created_at, 'd M Y'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><span class="order-badge"><?= strtoupper(htmlspecialchars((string)$order->status, ENT_QUOTES, 'UTF-8')) ?></span></td>
                        <td><span class="order-badge"><?= strtoupper(htmlspecialchars((string)$order->payment_status, ENT_QUOTES, 'UTF-8')) ?></span></td>
                        <td style="text-align: right; font-weight: bold; color: var(--heading, #0f172a);"><?= htmlspecialchars((string)$order->currency, ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars((string)$order->total_amount, ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="text-align: center; white-space: nowrap;">
                            <?php if ($canDownload): ?>
                                <a href="<?= htmlspecialchars($downloadUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn-download">Download</a>
                            <?php else: ?>
                                <button type="button" class="btn-download btn-disabled" disabled title="<?= htmlspecialchars($disabledTitle, ENT_QUOTES, 'UTF-8') ?>">Download</button>
                            <?php endif; ?>
                            <a href="/account/orders/<?= urlencode((string)$order->order_number) ?>" class="btn-receipt">View Receipt</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
