<?php
/**
 * Customer Order Receipt View
 */
?>
<div class="customer-order-detail" style="max-width: 800px; margin: 30px auto; padding: 25px; background: var(--surface, #ffffff); border: 1px solid var(--border, #e2e8f0); border-radius: 12px;">
    <style>
        .customer-order-detail .customer-revocation-notice {
            margin-bottom: 15px;
            padding: 12px 16px;
            background: var(--danger-bg, #fef2f2);
            border: 1px solid var(--danger-border, #fecaca);
            border-radius: 6px;
            color: var(--danger-text, #dc2626);
            font-size: 13px;
            font-weight: 600;
        }
        .customer-order-detail .customer-refund-card {
            margin-top: 25px;
            padding: 16px 20px;
            background: var(--surface-muted, #f8fafc);
            border: 1px solid var(--border, #e2e8f0);
            border-left: 4px solid var(--danger-text, #dc2626);
            border-radius: 8px;
            box-sizing: border-box;
        }
        .customer-order-detail .customer-refund-title {
            margin: 0 0 12px 0;
            color: var(--danger-text, #dc2626);
            font-size: 16px;
            font-weight: 700;
        }
        .customer-order-detail .customer-refund-item {
            font-size: 14px;
            color: var(--text, #1e293b);
            line-height: 1.6;
        }
        .customer-order-detail .customer-refund-item p {
            margin: 5px 0;
            color: var(--text, #1e293b);
        }
        .customer-order-detail .customer-refund-label {
            color: var(--heading, #0f172a);
            font-weight: 600;
        }
        .customer-order-detail .customer-refund-val {
            color: var(--text, #1e293b);
        }
        .customer-order-detail .customer-refund-reason {
            color: var(--muted, #64748b);
        }
        .customer-order-detail .badge-refund-completed {
            background: var(--success-bg, #ecfdf5);
            color: var(--success-text, #059669);
            border: 1px solid var(--success-border, #a7f3d0);
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.02em;
            display: inline-block;
        }
        .customer-order-detail .badge-refund-pending {
            background: var(--surface-muted, #fef3c7);
            color: var(--text, #92400e);
            border: 1px solid var(--border, #fde68a);
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.02em;
            display: inline-block;
        }
        .customer-order-detail .badge-refund-danger {
            background: var(--danger-bg, #fef2f2);
            color: var(--danger-text, #dc2626);
            border: 1px solid var(--danger-border, #fecaca);
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.02em;
            display: inline-block;
        }

        /* Dark mode overrides */
        [data-theme="dark"] .customer-order-detail .customer-revocation-notice,
        .dark .customer-order-detail .customer-revocation-notice,
        html.dark .customer-order-detail .customer-revocation-notice,
        body.dark .customer-order-detail .customer-revocation-notice,
        [data-admin-theme="dark"] .customer-order-detail .customer-revocation-notice {
            background: rgba(239, 68, 68, 0.12) !important;
            border-color: rgba(239, 68, 68, 0.3) !important;
            color: #f87171 !important;
        }
        [data-theme="dark"] .customer-order-detail .customer-refund-card,
        .dark .customer-order-detail .customer-refund-card,
        html.dark .customer-order-detail .customer-refund-card,
        body.dark .customer-order-detail .customer-refund-card,
        [data-admin-theme="dark"] .customer-order-detail .customer-refund-card {
            background: var(--surface-muted, #1e293b) !important;
            border-color: var(--border, #334155) !important;
            border-left-color: #ef4444 !important;
            color: var(--text, #cbd5e1) !important;
        }
        [data-theme="dark"] .customer-order-detail .customer-refund-title,
        .dark .customer-order-detail .customer-refund-title,
        html.dark .customer-order-detail .customer-refund-title,
        body.dark .customer-order-detail .customer-refund-title,
        [data-admin-theme="dark"] .customer-order-detail .customer-refund-title {
            color: #f87171 !important;
        }
        [data-theme="dark"] .customer-order-detail .customer-refund-item,
        .dark .customer-order-detail .customer-refund-item,
        html.dark .customer-order-detail .customer-refund-item,
        body.dark .customer-order-detail .customer-refund-item,
        [data-admin-theme="dark"] .customer-order-detail .customer-refund-item,
        [data-theme="dark"] .customer-order-detail .customer-refund-item p,
        .dark .customer-order-detail .customer-refund-item p,
        html.dark .customer-order-detail .customer-refund-item p,
        body.dark .customer-order-detail .customer-refund-item p,
        [data-admin-theme="dark"] .customer-order-detail .customer-refund-item p {
            color: var(--text, #cbd5e1) !important;
        }
        [data-theme="dark"] .customer-order-detail .customer-refund-label,
        .dark .customer-order-detail .customer-refund-label,
        html.dark .customer-order-detail .customer-refund-label,
        body.dark .customer-order-detail .customer-refund-label,
        [data-admin-theme="dark"] .customer-order-detail .customer-refund-label {
            color: var(--heading, #f8fafc) !important;
        }
        [data-theme="dark"] .customer-order-detail .customer-refund-val,
        .dark .customer-order-detail .customer-refund-val,
        html.dark .customer-order-detail .customer-refund-val,
        body.dark .customer-order-detail .customer-refund-val,
        [data-admin-theme="dark"] .customer-order-detail .customer-refund-val {
            color: var(--text, #cbd5e1) !important;
        }
        [data-theme="dark"] .customer-order-detail .customer-refund-reason,
        .dark .customer-order-detail .customer-refund-reason,
        html.dark .customer-order-detail .customer-refund-reason,
        body.dark .customer-order-detail .customer-refund-reason,
        [data-admin-theme="dark"] .customer-order-detail .customer-refund-reason {
            color: var(--muted, #94a3b8) !important;
        }
        [data-theme="dark"] .customer-order-detail .badge-refund-completed,
        .dark .customer-order-detail .badge-refund-completed,
        html.dark .customer-order-detail .badge-refund-completed,
        body.dark .customer-order-detail .badge-refund-completed,
        [data-admin-theme="dark"] .customer-order-detail .badge-refund-completed {
            background: rgba(16, 185, 129, 0.16) !important;
            color: #34d399 !important;
            border-color: rgba(16, 185, 129, 0.35) !important;
        }

        @media (prefers-color-scheme: dark) {
            :root:not([data-theme="light"]) .customer-order-detail .customer-revocation-notice {
                background: rgba(239, 68, 68, 0.12);
                border-color: rgba(239, 68, 68, 0.3);
                color: #f87171;
            }
            :root:not([data-theme="light"]) .customer-order-detail .customer-refund-card {
                background: var(--surface-muted, #1e293b);
                border-color: var(--border, #334155);
                border-left-color: #ef4444;
                color: var(--text, #cbd5e1);
            }
            :root:not([data-theme="light"]) .customer-order-detail .customer-refund-title {
                color: #f87171;
            }
            :root:not([data-theme="light"]) .customer-order-detail .customer-refund-item,
            :root:not([data-theme="light"]) .customer-order-detail .customer-refund-item p {
                color: var(--text, #cbd5e1);
            }
            :root:not([data-theme="light"]) .customer-order-detail .customer-refund-label {
                color: var(--heading, #f8fafc);
            }
            :root:not([data-theme="light"]) .customer-order-detail .customer-refund-val {
                color: var(--text, #cbd5e1);
            }
            :root:not([data-theme="light"]) .customer-order-detail .customer-refund-reason {
                color: var(--muted, #94a3b8);
            }
            :root:not([data-theme="light"]) .customer-order-detail .badge-refund-completed {
                background: rgba(16, 185, 129, 0.16);
                color: #34d399;
                border-color: rgba(16, 185, 129, 0.35);
            }
        }
    </style>
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

    <?php
    $hasServiceItem = false;
    foreach ($order->items ?? [] as $item) {
        if (($item->product_type ?? '') === 'service') {
            $hasServiceItem = true;
            break;
        }
    }
    ?>

    <?php if ($hasServiceItem || !empty($deliverables)): ?>
        <?php $isRefundedOrRevoked = ($order->status === 'refunded' || ($order->fulfillment_status ?? '') === 'revoked'); ?>
        <div id="service-deliverables" style="margin-top: 25px; padding: 20px; background: var(--surface, #ffffff); border: 1px solid var(--border, #e2e8f0); border-radius: 8px;">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border, #e2e8f0); padding-bottom: 10px; margin-bottom: 15px;">
                <h3 style="margin: 0; color: var(--heading, #0f172a); font-size: 17px; font-weight: 700;">Service Deliverables</h3>
                <span class="badge" style="background: var(--accent-soft, #e0f2fe); color: var(--accent, #0369a1); font-size: 11px; padding: 2px 8px; border-radius: 4px; font-weight: 600;">
                    Order Status: <?= strtoupper(htmlspecialchars((string)$order->status, ENT_QUOTES, 'UTF-8')) ?>
                </span>
            </div>

            <?php if ($isRefundedOrRevoked): ?>
                <div class="customer-revocation-notice" style="margin-bottom: 15px; padding: 12px 16px; background: var(--danger-bg, #fef2f2); border: 1px solid var(--danger-border, #fecaca); border-radius: 6px; color: var(--danger-text, #dc2626); font-size: 13px; font-weight: 600;">
                    ⚠️ Access to deliverables has been revoked due to order refund / cancellation.
                </div>
            <?php endif; ?>

            <?php if (!empty($deliverables)): ?>
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <?php foreach ($deliverables as $deliv): ?>
                        <?php $isDelivReleased = (!empty($deliv->is_released) && (int)$deliv->is_released === 1); ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; background: var(--surface-muted, #f8fafc); border: 1px solid var(--border, #e2e8f0); border-radius: 6px; flex-wrap: wrap; gap: 10px;">
                            <div style="flex: 1; min-width: 250px;">
                                <div style="font-weight: 700; font-size: 14px; color: var(--heading, #0f172a); display: flex; align-items: center; flex-wrap: wrap; gap: 6px;">
                                    <span><?= ($deliv->resource_type === 'url' ? '🔗 ' : '📄 ') ?><?= htmlspecialchars((string)$deliv->title, ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php if ($isDelivReleased): ?>
                                        <span class="badge badge-released" style="background: var(--success-soft, #ecfdf5); color: var(--success, #059669); border: 1px solid var(--success-border, #a7f3d0); font-size: 11px; padding: 2px 8px; border-radius: 4px; font-weight: 700;">Released</span>
                                    <?php else: ?>
                                        <span class="badge badge-unreleased" style="background: var(--surface-muted, #fef3c7); color: var(--text, #92400e); border: 1px solid var(--border, #fde68a); font-size: 11px; padding: 2px 8px; border-radius: 4px; font-weight: 700;">Not released yet</span>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($deliv->description)): ?>
                                    <div style="font-size: 12px; color: var(--muted, #64748b); margin-top: 4px;">
                                        <?= htmlspecialchars((string)$deliv->description, ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                <?php endif; ?>
                                <div style="font-size: 11px; color: var(--muted, #64748b); margin-top: 4px;">
                                    Type: <strong><?= $deliv->resource_type === 'url' ? 'External Resource' : 'Direct Download' ?></strong>
                                    <?php if ((int)$deliv->file_size > 0): ?>
                                        &bull; Size: <strong><?= htmlspecialchars(fdig_format_bytes((int)$deliv->file_size), ENT_QUOTES, 'UTF-8') ?></strong>
                                    <?php endif; ?>
                                    <?php if ($isDelivReleased && (!empty($deliv->updated_at) || !empty($deliv->created_at))): ?>
                                        &bull; Released: <strong><?= htmlspecialchars(fdig_format_datetime($deliv->updated_at ?: $deliv->created_at), ENT_QUOTES, 'UTF-8') ?></strong>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div>
                                <?php if ($isRefundedOrRevoked): ?>
                                    <button type="button" disabled style="padding: 7px 16px; background: var(--surface-muted, #f1f5f9); color: var(--muted, #94a3b8); border: 1px solid var(--border, #e2e8f0); border-radius: 6px; font-size: 13px; font-weight: 600; cursor: not-allowed;">
                                        Access Revoked
                                    </button>
                                <?php elseif (!$isDelivReleased): ?>
                                    <button type="button" disabled style="padding: 7px 16px; background: var(--surface-muted, #f1f5f9); color: var(--muted, #94a3b8); border: 1px solid var(--border, #e2e8f0); border-radius: 6px; font-size: 13px; font-weight: 600; cursor: not-allowed;">
                                        Not released yet
                                    </button>
                                <?php elseif ($deliv->resource_type === 'url'): ?>
                                    <a href="/download/<?= htmlspecialchars((string)$deliv->download_token, ENT_QUOTES, 'UTF-8') ?>"
                                       target="_blank"
                                       style="display: inline-block; padding: 7px 16px; background: var(--accent, #2563eb); color: #fff; text-decoration: none; border-radius: 6px; font-size: 13px; font-weight: 600;">
                                        Open / Download &nearr;
                                    </a>
                                <?php else: ?>
                                    <a href="/download/<?= htmlspecialchars((string)$deliv->download_token, ENT_QUOTES, 'UTF-8') ?>"
                                       style="display: inline-block; padding: 7px 16px; background: var(--accent, #2563eb); color: #fff; text-decoration: none; border-radius: 6px; font-size: 13px; font-weight: 600;">
                                        Download
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="padding: 24px; text-align: center; background: var(--surface-muted, #f8fafc); border-radius: 6px; color: var(--muted, #64748b);">
                    <p style="margin: 0; font-size: 14px;">No deliverables available yet.</p>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($refunds)): ?>
        <div class="customer-refund-card" style="margin-top: 25px; padding: 16px 20px; background: var(--surface-muted, #f8fafc); border: 1px solid var(--border, #e2e8f0); border-left: 4px solid var(--danger-text, #dc2626); border-radius: 8px;">
            <h3 class="customer-refund-title" style="margin-top: 0; margin-bottom: 12px; color: var(--danger-text, #dc2626); font-size: 16px; font-weight: 700;">Refund Details</h3>
            <?php foreach ($refunds as $ref): ?>
                <?php
                $statusUpper = strtoupper((string)($ref->status ?? ''));
                $isCompleted = ($statusUpper === 'COMPLETED');
                $isPending = in_array($statusUpper, ['PENDING', 'PROCESSING'], true);
                $badgeClass = $isCompleted ? 'badge-refund-completed' : ($isPending ? 'badge-refund-pending' : 'badge-refund-danger');
                ?>
                <div class="customer-refund-item" style="font-size: 14px; line-height: 1.6;">
                    <p style="margin: 5px 0;">
                        <strong class="customer-refund-label" style="color: var(--heading, #0f172a);">Refund Status:</strong>
                        <span class="badge badge-refunded <?= $badgeClass ?>" style="padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 700;"><?= htmlspecialchars($statusUpper, ENT_QUOTES, 'UTF-8') ?></span>
                    </p>
                    <p style="margin: 5px 0;">
                        <strong class="customer-refund-label" style="color: var(--heading, #0f172a);">Refund Amount:</strong>
                        <span class="customer-refund-val" style="color: var(--text, #1e293b);"><?= htmlspecialchars((string)$ref->currency, ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars((string)$ref->refund_amount, ENT_QUOTES, 'UTF-8') ?></span>
                    </p>
                    <p style="margin: 5px 0;">
                        <strong class="customer-refund-label" style="color: var(--heading, #0f172a);">Refund Destination:</strong>
                        <span class="customer-refund-val" style="color: var(--text, #1e293b);">Favorite Digital Wallet</span>
                    </p>
                    <p style="margin: 5px 0;">
                        <strong class="customer-refund-label" style="color: var(--heading, #0f172a);">Processed Date:</strong>
                        <span class="customer-refund-val" style="color: var(--text, #1e293b);"><?= htmlspecialchars(fdig_format_datetime($ref->processed_at), ENT_QUOTES, 'UTF-8') ?></span>
                    </p>
                    <?php if (!empty($ref->reason)): ?>
                        <p style="margin: 5px 0;">
                            <strong class="customer-refund-label" style="color: var(--heading, #0f172a);">Reason:</strong>
                            <span class="customer-refund-reason" style="color: var(--muted, #64748b);"><?= htmlspecialchars((string)$ref->reason, ENT_QUOTES, 'UTF-8') ?></span>
                        </p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
