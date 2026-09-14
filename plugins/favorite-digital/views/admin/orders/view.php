<?php
/**
 * Admin Single Order View
 */
?>
<div class="wrap">
    <h1>Order <?= htmlspecialchars((string)$order->order_number, ENT_QUOTES, 'UTF-8') ?></h1>
    <a href="/admin/page/favorite-digital-orders" class="button" style="margin-bottom: 15px;">&larr; Back to Orders</a>

    <?php if (!empty($flashSuccess)): ?>
        <div class="notice notice-success is-dismissible"><p><?= htmlspecialchars((string)$flashSuccess, ENT_QUOTES, 'UTF-8') ?></p></div>
    <?php endif; ?>
    <?php if (!empty($flashError)): ?>
        <div class="notice notice-error is-dismissible"><p><?= htmlspecialchars((string)$flashError, ENT_QUOTES, 'UTF-8') ?></p></div>
    <?php endif; ?>

    <div style="display: flex; gap: 20px; flex-wrap: wrap;">
        <!-- Left details column -->
        <div style="flex: 2; min-width: 320px;">
            <div class="postbox" style="padding: 15px;">
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
                                        <div style="font-size: 11px; color: #666;">Slug: <?= htmlspecialchars((string)$snapshot['slug'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($snapshot['attributes'])): ?>
                                        <details style="margin-top: 5px; font-size: 11px; color: #444;">
                                            <summary>Snapshot Attributes</summary>
                                            <pre style="margin: 3px 0; background: #f8f8f8; padding: 5px; border-radius: 3px;"><?= htmlspecialchars(json_encode($snapshot['attributes'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?></pre>
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

                <div style="margin-top: 20px; text-align: right; border-top: 1px solid #ddd; padding-top: 10px;">
                    <p style="margin: 4px 0;"><strong>Subtotal:</strong> <?= htmlspecialchars((string)$order->currency, ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars((string)$order->subtotal_amount, ENT_QUOTES, 'UTF-8') ?></p>
                    <p style="margin: 4px 0; color: #c00;"><strong>Discount:</strong> -<?= htmlspecialchars((string)$order->currency, ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars((string)$order->discount_amount, ENT_QUOTES, 'UTF-8') ?></p>
                    <h3 style="margin: 6px 0;"><strong>Total:</strong> <?= htmlspecialchars((string)$order->currency, ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars((string)$order->total_amount, ENT_QUOTES, 'UTF-8') ?></h3>
                </div>
            </div>

            <?php
            $totalPaidMinor = 0;
            if (!empty($order->payments)) {
                foreach ($order->payments as $p) {
                    if ($p->status === 'completed' || $p->status === 'paid') {
                        $totalPaidMinor += (int)round((float)$p->amount_paid * 100);
                    }
                }
            }
            $orderTotalMinor = (int)round((float)$order->total_amount * 100);
            $remainingMinor = max(0, $orderTotalMinor - $totalPaidMinor);
            $remainingFormatted = number_format($remainingMinor / 100, 2, '.', '');
            $paidFormatted = number_format($totalPaidMinor / 100, 2, '.', '');
            ?>

            <div class="postbox" style="padding: 15px; margin-top: 20px;">
                <h2>Payment Settlements</h2>
                <p>
                    <strong>Total Settled:</strong> ৳<?= htmlspecialchars($paidFormatted, ENT_QUOTES, 'UTF-8') ?> BDT &nbsp;|&nbsp;
                    <strong>Remaining Balance:</strong> ৳<?= htmlspecialchars($remainingFormatted, ENT_QUOTES, 'UTF-8') ?> BDT
                </p>

                <?php if (!empty($order->payments)): ?>
                    <table class="widefat fixed striped" style="margin-top: 10px;">
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
                                    <td style="text-align: right; font-weight: bold;">৳<?= htmlspecialchars((string)$pay->amount_paid, ENT_QUOTES, 'UTF-8') ?></td>
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
                                    <td><?= htmlspecialchars((string)$pay->created_at, ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p><em>No payment records found for this order.</em></p>
                <?php endif; ?>
            </div>

            <?php if (!empty($entitlements)): ?>
                <div class="postbox" style="padding: 15px; margin-top: 20px;">
                    <h2>Granted Entitlements & Access</h2>
                    <table class="widefat fixed striped" style="margin-top: 10px;">
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
                                    <td><?= htmlspecialchars((string)$ent->granted_at, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= !empty($ent->expires_at) ? htmlspecialchars((string)$ent->expires_at, ENT_QUOTES, 'UTF-8') : '<em>Lifetime</em>' ?></td>
                                    <td><?= ($ent->product_type === 'digital') ? (isset($ent->download_count) ? (int)$ent->download_count . ' / 3 used' : '0 / 3 used') : '—' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <?php if (!empty($refunds)): ?>
                <div class="postbox" style="padding: 15px; margin-top: 20px; border-left: 4px solid #d63638;">
                    <h2>Refund Audit & History</h2>
                    <table class="widefat fixed striped" style="margin-top: 10px;">
                        <thead>
                            <tr>
                                <th>Refund ID</th>
                                <th>Amount</th>
                                <th>Destination</th>
                                <th>Wallet Tx ID</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th>Processed At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($refunds as $ref): ?>
                                <tr>
                                    <td>#<?= (int)$ref->id ?></td>
                                    <td><strong><?= htmlspecialchars((string)$ref->currency, ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars((string)$ref->refund_amount, ENT_QUOTES, 'UTF-8') ?></strong></td>
                                    <td><span class="badge badge-wallet"><?= htmlspecialchars(strtoupper((string)$ref->destination), ENT_QUOTES, 'UTF-8') ?></span></td>
                                    <td>
                                        <?php if (!empty($ref->wallet_transaction_id)): ?>
                                            <code>Tx #<?= (int)$ref->wallet_transaction_id ?></code>
                                        <?php else: ?>
                                            <em>N/A</em>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars((string)$ref->reason, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><span class="badge badge-completed"><?= htmlspecialchars(ucfirst((string)$ref->status), ENT_QUOTES, 'UTF-8') ?></span></td>
                                    <td><?= htmlspecialchars((string)$ref->processed_at, ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <?php if (!empty($order->notes)): ?>
                <div class="postbox" style="padding: 15px; margin-top: 20px;">
                    <h3>Order Notes</h3>
                    <p><?= nl2br(htmlspecialchars((string)$order->notes, ENT_QUOTES, 'UTF-8')) ?></p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Right sidebar column -->
        <div style="flex: 1; min-width: 260px;">
            <div class="postbox" style="padding: 15px;">
                <h2>Order Overview</h2>
                <p><strong>Order ID:</strong> #<?= (int)$order->id ?></p>
                <p><strong>Customer ID:</strong> User #<?= (int)$order->user_id ?></p>
                <p><strong>Created:</strong> <?= htmlspecialchars((string)$order->created_at, ENT_QUOTES, 'UTF-8') ?></p>
                <p><strong>Last Updated:</strong> <?= htmlspecialchars((string)$order->updated_at, ENT_QUOTES, 'UTF-8') ?></p>

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

                <?php if ($isPendingPayment && $order->status !== 'cancelled' && $order->status !== 'refunded'): ?>
                    <div style="margin: 15px 0; padding: 14px; background: #e0f2fe; border: 1px solid #7dd3fc; border-radius: 6px;">
                        <h3 style="margin: 0 0 8px; color: #0369a1; font-size: 15px;">✓ Accept / Confirm Order</h3>
                        <p style="font-size: 12px; color: #0c4a6e; margin: 0 0 10px; line-height: 1.4;">
                            Accept payment, grant access, and set the customer service/order progress:
                        </p>
                        <form method="post" action="/admin/page/favorite-digital-orders">
                            <input type="hidden" name="_token" value="<?= htmlspecialchars((string)$csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="action" value="accept_order">
                            <input type="hidden" name="id" value="<?= (int)$order->id ?>">

                            <div style="font-size: 13px; margin-bottom: 12px;">
                                <label style="display: block; margin-bottom: 6px; font-weight: 600; color: #0369a1;">Select Status:</label>
                                <label style="display: block; margin-bottom: 4px; cursor: pointer;">
                                    <input type="radio" name="chosen_status" value="pending" checked> <strong>⏳ Pending</strong> (Default for Services)
                                </label>
                                <label style="display: block; margin-bottom: 4px; cursor: pointer;">
                                    <input type="radio" name="chosen_status" value="processing"> <strong>⚙️ Processing</strong>
                                </label>
                                <label style="display: block; cursor: pointer;">
                                    <input type="radio" name="chosen_status" value="completed"> <strong>✅ Complete</strong>
                                </label>
                            </div>

                            <button type="submit" class="button button-primary" style="width: 100%; background: #0284c7; border-color: #0369a1; font-weight: bold;">
                                Accept Order & Confirm Payment
                            </button>
                        </form>
                    </div>
                <?php elseif ($hasServiceItem && $order->status !== 'cancelled' && $order->status !== 'refunded'): ?>
                    <div style="margin: 15px 0; padding: 14px; background: #f0fdf4; border: 1px solid #86efac; border-radius: 6px;">
                        <h3 style="margin: 0 0 8px; color: #166534; font-size: 15px;">⚙️ Service Progress Status</h3>
                        <p style="font-size: 12px; color: #14532d; margin: 0 0 10px; line-height: 1.4;">
                            Update the service progress visible to customer in their View Access screen:
                        </p>
                        <form method="post" action="/admin/page/favorite-digital-orders">
                            <input type="hidden" name="_token" value="<?= htmlspecialchars((string)$csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="action" value="update_service_status">
                            <input type="hidden" name="id" value="<?= (int)$order->id ?>">

                            <div style="font-size: 13px; margin-bottom: 12px;">
                                <label style="display: block; margin-bottom: 4px; cursor: pointer;">
                                    <input type="radio" name="chosen_status" value="pending" <?= $order->status === 'pending' ? 'checked' : '' ?>> <strong>⏳ Pending</strong>
                                </label>
                                <label style="display: block; margin-bottom: 4px; cursor: pointer;">
                                    <input type="radio" name="chosen_status" value="processing" <?= $order->status === 'processing' ? 'checked' : '' ?>> <strong>⚙️ Processing</strong>
                                </label>
                                <label style="display: block; cursor: pointer;">
                                    <input type="radio" name="chosen_status" value="completed" <?= $order->status === 'completed' ? 'checked' : '' ?>> <strong>✅ Complete</strong>
                                </label>
                            </div>

                            <button type="submit" class="button button-primary" style="width: 100%; background: #16a34a; border-color: #15803d; font-weight: bold;">
                                Update Service Status
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
                
                <hr>
                <h3>Update Statuses</h3>
                <form method="post" action="/admin/page/favorite-digital-orders">
                    <input type="hidden" name="_token" value="<?= htmlspecialchars((string)$csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="id" value="<?= (int)$order->id ?>">

                    <div style="margin-bottom: 10px;">
                        <label><strong>Order Status:</strong></label><br>
                        <select name="status" style="width: 100%;">
                            <option value="pending" <?= ($order->status === 'pending') ? 'selected' : '' ?>>Pending</option>
                            <option value="processing" <?= ($order->status === 'processing') ? 'selected' : '' ?>>Processing</option>
                            <option value="completed" <?= ($order->status === 'completed') ? 'selected' : '' ?>>Completed</option>
                            <option value="failed" <?= ($order->status === 'failed') ? 'selected' : '' ?>>Failed</option>
                            <option value="cancelled" <?= ($order->status === 'cancelled') ? 'selected' : '' ?>>Cancelled</option>
                            <option value="refunded" <?= ($order->status === 'refunded') ? 'selected' : '' ?>>Refunded</option>
                        </select>
                    </div>

                    <div style="margin-bottom: 10px;">
                        <label><strong>Payment Status:</strong></label><br>
                        <select name="payment_status" style="width: 100%;">
                            <option value="unpaid" <?= ($order->payment_status === 'unpaid') ? 'selected' : '' ?>>Unpaid</option>
                            <option value="pending" <?= ($order->payment_status === 'pending') ? 'selected' : '' ?>>Pending</option>
                            <option value="partially_paid" <?= ($order->payment_status === 'partially_paid') ? 'selected' : '' ?>>Partially Paid</option>
                            <option value="paid" <?= ($order->payment_status === 'paid') ? 'selected' : '' ?>>Paid</option>
                            <option value="failed" <?= ($order->payment_status === 'failed') ? 'selected' : '' ?>>Failed</option>
                            <option value="refunded" <?= ($order->payment_status === 'refunded') ? 'selected' : '' ?>>Refunded</option>
                        </select>
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label><strong>Fulfillment Status:</strong></label><br>
                        <select name="fulfillment_status" style="width: 100%;">
                            <option value="unfulfilled" <?= ($order->fulfillment_status === 'unfulfilled') ? 'selected' : '' ?>>Unfulfilled</option>
                            <option value="partially_fulfilled" <?= ($order->fulfillment_status === 'partially_fulfilled') ? 'selected' : '' ?>>Partially Fulfilled</option>
                            <option value="fulfilled" <?= ($order->fulfillment_status === 'fulfilled') ? 'selected' : '' ?>>Fulfilled</option>
                            <option value="cancelled" <?= ($order->fulfillment_status === 'cancelled') ? 'selected' : '' ?>>Cancelled</option>
                            <option value="revoked" <?= ($order->fulfillment_status === 'revoked') ? 'selected' : '' ?>>Revoked</option>
                        </select>
                    </div>

                    <button type="submit" class="button button-primary" style="width: 100%;">Save Status Updates</button>
                </form>

                <?php if ($order->status !== 'cancelled' && $order->status !== 'refunded'): ?>
                    <div style="margin-top: 15px; border-top: 1px solid #ddd; padding-top: 15px;">
                        <form method="post" action="/admin/page/favorite-digital-orders" onsubmit="return confirm('Are you sure you want to cancel this order? If payment is already confirmed, it will automatically be refunded to customer wallet.');">
                            <input type="hidden" name="_token" value="<?= htmlspecialchars((string)$csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="action" value="cancel_order">
                            <input type="hidden" name="id" value="<?= (int)$order->id ?>">
                            <input type="hidden" name="cancel_reason" value="Order cancelled by administrator.">
                            <button type="submit" class="button button-link-delete" style="width: 100%; text-align: center; color: #b32d2e; padding: 6px; font-weight: bold; border: 1px dashed #fca5a5; background: #fff5f5; border-radius: 4px;">
                                ❌ Cancel Order <?= ($order->payment_status === 'paid' ? '(& Refund to Wallet)' : '') ?>
                            </button>
                        </form>
                    </div>
                <?php endif; ?>

                <?php if ($order->payment_status === 'paid' && $order->fulfillment_status !== 'fulfilled'): ?>
                    <div style="margin-top: 15px; border-top: 1px solid #ddd; padding-top: 15px;">
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

                <?php
                $verifiedPaidAmount = 0.0;
                if (!empty($order->payments)) {
                    foreach ($order->payments as $p) {
                        if ($p->status === 'completed' || $p->status === 'paid') {
                            $verifiedPaidAmount += (float)$p->amount_paid;
                        }
                    }
                }
                $isRefundEligible = ($order->payment_status !== 'refunded' && $order->status !== 'refunded' && $verifiedPaidAmount > 0);
                ?>

                <?php if ($isRefundEligible): ?>
                    <div style="margin-top: 20px; border-top: 2px solid #d63638; padding-top: 15px;">
                        <h3 style="color: #d63638; margin-top: 0;">Issue Refund</h3>
                        <p style="font-size: 13px; color: #555; margin-bottom: 8px;">
                            Refunds are credited directly to the customer's <strong>Favorite Digital Wallet</strong> in <strong><?= htmlspecialchars((string)$order->currency, ENT_QUOTES, 'UTF-8') ?></strong> and revoke granted order access.
                        </p>
                        <p style="margin: 4px 0;"><strong>Verified Paid:</strong> <?= htmlspecialchars((string)$order->currency, ENT_QUOTES, 'UTF-8') ?> <?= number_format($verifiedPaidAmount, 2, '.', '') ?></p>
                        <p style="margin: 4px 0;"><strong>Refund Destination:</strong> Customer Wallet</p>

                        <form method="post" action="/admin/page/favorite-digital-orders" onsubmit="return confirm('Are you sure you want to issue a full wallet refund of <?= htmlspecialchars((string)$order->currency, ENT_QUOTES, 'UTF-8') ?> <?= number_format($verifiedPaidAmount, 2, '.', '') ?>? This will revoke access and cannot be undone.');">
                            <input type="hidden" name="_token" value="<?= htmlspecialchars((string)$csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="action" value="refund">
                            <input type="hidden" name="id" value="<?= (int)$order->id ?>">

                            <div style="margin: 10px 0;">
                                <label for="refund_reason"><strong>Refund Reason <span style="color:red;">*</span>:</strong></label><br>
                                <textarea name="reason" id="refund_reason" required rows="2" style="width: 100%; font-size: 12px;" placeholder="e.g. Seller failed to deliver service / customer dispute"></textarea>
                            </div>

                            <button type="submit" class="button button-danger" style="width: 100%; background: #d63638; color: #fff; border-color: #b32d2e; font-weight: bold;">
                                ↩ Issue Full Wallet Refund
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
