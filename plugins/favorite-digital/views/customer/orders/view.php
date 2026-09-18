<?php
/**
 * Customer Order Receipt View
 */
?>
<div class="customer-order-detail" style="max-width: 800px; margin: 30px auto; padding: 25px; background: var(--surface, #ffffff); border: 1px solid var(--border, #e2e8f0); border-radius: 12px;">
    <a href="/account/orders" style="display: inline-block; margin-bottom: 15px; color: var(--accent, #2563eb); text-decoration: none; font-weight: 600;">&larr; Back to My Orders</a>
    
    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid var(--border-strong, #cbd5e1); padding-bottom: 15px;">
        <div>
            <h1 style="margin: 0; font-size: 24px; font-weight: 800; color: var(--heading, #0f172a);">Order Receipt</h1>
            <p style="margin: 5px 0 0 0; color: var(--muted, #64748b);">Order #<?= htmlspecialchars((string)$order->order_number, ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <div style="text-align: right;">
            <p style="margin: 0; font-size: 14px; color: var(--muted, #64748b);">Placed on: <?= htmlspecialchars(fdig_format_datetime($order->created_at), ENT_QUOTES, 'UTF-8') ?></p>
            <p style="margin: 5px 0 0 0;">
                <span class="badge" style="background: var(--surface-muted, #f1f5f9); color: var(--heading, #0f172a); border: 1px solid var(--border, #e2e8f0); padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: 700;"><?= strtoupper(htmlspecialchars((string)$order->status, ENT_QUOTES, 'UTF-8')) ?></span>
            </p>
        </div>
    </div>

    <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
        <thead>
            <tr style="border-bottom: 1px solid var(--border-strong, #cbd5e1); text-align: left; background: var(--surface-muted, #f8fafc);">
                <th style="padding: 10px 12px; color: var(--muted, #64748b); font-size: 12px; font-weight: 700; text-transform: uppercase;">Item</th>
                <th style="padding: 10px 12px; color: var(--muted, #64748b); font-size: 12px; font-weight: 700; text-transform: uppercase;">Type</th>
                <th style="padding: 10px 12px; text-align: right; color: var(--muted, #64748b); font-size: 12px; font-weight: 700; text-transform: uppercase;">Price</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($order->items as $item): ?>
                <?php $snapshot = $item->snapshot ?? []; ?>
                <tr style="border-bottom: 1px solid var(--border, #e2e8f0);">
                    <td style="padding: 12px; color: var(--text, #1e293b);">
                        <strong style="color: var(--heading, #0f172a);"><?= htmlspecialchars((string)($snapshot['title'] ?? 'Product #' . $item->product_id), ENT_QUOTES, 'UTF-8') ?></strong>
                        <?php if ($item->product_type === 'service'): ?>
                            <div style="margin-top: 4px;">
                                <?php
                                $statusIcon = match ($order->status) {
                                    'processing' => '⚙️',
                                    'completed', 'complete' => '✅',
                                    default => '⏳',
                                };
                                ?>
                                <span style="font-size: 12px; font-weight: 600; padding: 2px 8px; border-radius: 4px; background: var(--accent-soft, #e0f2fe); color: var(--accent, #0369a1); border: 1px solid var(--border, #bae6fd);">
                                    Service Status: <?= $statusIcon ?> <?= ucfirst(htmlspecialchars((string)$order->status, ENT_QUOTES, 'UTF-8')) ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td style="padding: 12px; color: var(--text, #1e293b);"><?= strtoupper(htmlspecialchars((string)$item->product_type, ENT_QUOTES, 'UTF-8')) ?></td>
                    <td style="padding: 12px; text-align: right; font-weight: bold; color: var(--heading, #0f172a);"><?= htmlspecialchars((string)$item->currency, ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars((string)$item->final_price, ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div style="margin-top: 20px; text-align: right; border-top: 2px solid var(--border-strong, #cbd5e1); padding-top: 15px; color: var(--text, #1e293b);">
        <p style="margin: 4px 0; color: var(--muted, #64748b);">Subtotal: <span style="color: var(--heading, #0f172a); font-weight: 600;"><?= htmlspecialchars((string)$order->currency, ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars((string)$order->subtotal_amount, ENT_QUOTES, 'UTF-8') ?></span></p>
        <?php if ((float)$order->discount_amount > 0): ?>
            <p style="margin: 4px 0; color: var(--danger, #dc2626);">Discount: -<?= htmlspecialchars((string)$order->currency, ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars((string)$order->discount_amount, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <h2 style="margin: 8px 0; font-size: 20px; color: var(--heading, #0f172a);">Total: <?= htmlspecialchars((string)$order->currency, ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars((string)$order->total_amount, ENT_QUOTES, 'UTF-8') ?></h2>
    </div>

    <?php if (!empty($refunds)): ?>
        <div style="margin-top: 25px; padding: 15px 20px; background: var(--danger-soft, #fef2f2); border: 1px solid var(--danger-border, #fecaca); border-radius: 8px;">
            <h3 style="margin-top: 0; color: var(--danger, #dc2626); font-size: 16px;">Refund Details</h3>
            <?php foreach ($refunds as $ref): ?>
                <div style="font-size: 14px; color: var(--text, #1e293b); line-height: 1.6;">
                    <p style="margin: 4px 0;">
                        <strong>Refund Status:</strong> <span class="badge badge-refunded" style="background: var(--danger, #dc2626); color: #fff; padding: 2px 8px; border-radius: 4px; font-size: 12px;"><?= strtoupper(htmlspecialchars((string)$ref->status, ENT_QUOTES, 'UTF-8')) ?></span>
                    </p>
                    <p style="margin: 4px 0;">
                        <strong>Refund Amount:</strong> <?= htmlspecialchars((string)$ref->currency, ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars((string)$ref->refund_amount, ENT_QUOTES, 'UTF-8') ?>
                    </p>
                    <p style="margin: 4px 0;">
                        <strong>Refund Destination:</strong> Favorite Digital Wallet
                    </p>
                    <p style="margin: 4px 0;">
                        <strong>Processed Date:</strong> <?= htmlspecialchars(fdig_format_datetime($ref->processed_at), ENT_QUOTES, 'UTF-8') ?>
                    </p>
                    <?php if (!empty($ref->reason)): ?>
                        <p style="margin: 4px 0;">
                            <strong>Reason:</strong> <?= htmlspecialchars((string)$ref->reason, ENT_QUOTES, 'UTF-8') ?>
                        </p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
