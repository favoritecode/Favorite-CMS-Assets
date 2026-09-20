<?php
/**
 * Admin Orders List View — Polished UI
 *
 * Variables:
 * - $orders            : array of order objects
 * - $total             : int
 * - $page              : int
 * - $totalPages        : int
 * - $statusFilter      : string
 * - $paymentFilter     : string
 * - $fulfillmentFilter : string
 * - $search            : string
 * - $csrfToken         : string
 * - $flashSuccess      : ?string
 * - $flashError        : ?string
 */
?>
<div class="fd-admin-wrap" style="max-width: 1300px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1e1e1e;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <h1 style="margin: 0; font-size: 22px; font-weight: 700; color: #1d2327;">Favorite Digital — Orders</h1>
            <span style="background: #f0f0f1; color: #50575e; padding: 4px 10px; border-radius: 12px; font-size: 13px; font-weight: 600;">
                <?= (int)$total ?> <?= ((int)$total === 1) ? 'order' : 'orders' ?>
            </span>
        </div>
    </div>

    <?php if (!empty($flashSuccess)): ?>
        <div style="background: #e7f7ed; border-left: 4px solid #28a745; padding: 12px 16px; margin-bottom: 20px; border-radius: 4px; color: #155724; font-size: 13px;">
            <strong>Success:</strong> <?= htmlspecialchars((string)$flashSuccess, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($flashError)): ?>
        <div style="background: #fdf2f2; border-left: 4px solid #dc3545; padding: 12px 16px; margin-bottom: 20px; border-radius: 4px; color: #721c24; font-size: 13px;">
            <strong>Error:</strong> <?= htmlspecialchars((string)$flashError, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <!-- Filter & Search Toolbar -->
    <div style="background: #fff; border: 1px solid #c3c4c7; border-radius: 6px; padding: 14px 16px; margin-bottom: 18px; box-shadow: 0 1px 2px rgba(0,0,0,0.03);">
        <form method="get" action="/admin/page/favorite-digital-orders" style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin: 0;">
            <div style="flex: 1; min-width: 220px;">
                <input type="text" name="search" placeholder="Search Order # or Notes..." value="<?= htmlspecialchars((string)($search ?? ''), ENT_QUOTES, 'UTF-8') ?>" style="width: 100%; box-sizing: border-box; padding: 7px 12px; border: 1px solid #8c8f94; border-radius: 4px; font-size: 13px;">
            </div>
            <div>
                <select name="status" style="padding: 7px 12px; border: 1px solid #8c8f94; border-radius: 4px; font-size: 13px; background: #fff;">
                    <option value="all" <?= ($statusFilter === 'all') ? 'selected' : '' ?>>All Order Statuses</option>
                    <option value="pending" <?= ($statusFilter === 'pending') ? 'selected' : '' ?>>Pending</option>
                    <option value="processing" <?= ($statusFilter === 'processing') ? 'selected' : '' ?>>Processing</option>
                    <option value="partial" <?= ($statusFilter === 'partial') ? 'selected' : '' ?>>Partial</option>
                    <option value="completed" <?= ($statusFilter === 'completed') ? 'selected' : '' ?>>Completed</option>
                    <option value="failed" <?= ($statusFilter === 'failed') ? 'selected' : '' ?>>Failed</option>
                    <option value="cancelled" <?= ($statusFilter === 'cancelled') ? 'selected' : '' ?>>Cancelled</option>
                    <option value="refunded" <?= ($statusFilter === 'refunded') ? 'selected' : '' ?>>Refunded</option>
                </select>
            </div>
            <div>
                <select name="payment_status" style="padding: 7px 12px; border: 1px solid #8c8f94; border-radius: 4px; font-size: 13px; background: #fff;">
                    <option value="all" <?= ($paymentFilter === 'all') ? 'selected' : '' ?>>All Payment Statuses</option>
                    <option value="unpaid" <?= ($paymentFilter === 'unpaid') ? 'selected' : '' ?>>Unpaid</option>
                    <option value="pending" <?= ($paymentFilter === 'pending') ? 'selected' : '' ?>>Pending</option>
                    <option value="partially_paid" <?= ($paymentFilter === 'partially_paid') ? 'selected' : '' ?>>Partially Paid</option>
                    <option value="paid" <?= ($paymentFilter === 'paid') ? 'selected' : '' ?>>Paid</option>
                    <option value="partially_refunded" <?= ($paymentFilter === 'partially_refunded') ? 'selected' : '' ?>>Partially Refunded</option>
                    <option value="failed" <?= ($paymentFilter === 'failed') ? 'selected' : '' ?>>Failed</option>
                    <option value="refunded" <?= ($paymentFilter === 'refunded') ? 'selected' : '' ?>>Refunded</option>
                </select>
            </div>
            <div>
                <select name="fulfillment_status" style="padding: 7px 12px; border: 1px solid #8c8f94; border-radius: 4px; font-size: 13px; background: #fff;">
                    <option value="all" <?= ($fulfillmentFilter === 'all') ? 'selected' : '' ?>>All Fulfillment Statuses</option>
                    <option value="unfulfilled" <?= ($fulfillmentFilter === 'unfulfilled') ? 'selected' : '' ?>>Unfulfilled</option>
                    <option value="partially_fulfilled" <?= ($fulfillmentFilter === 'partially_fulfilled') ? 'selected' : '' ?>>Partially Fulfilled</option>
                    <option value="fulfilled" <?= ($fulfillmentFilter === 'fulfilled') ? 'selected' : '' ?>>Fulfilled</option>
                    <option value="cancelled" <?= ($fulfillmentFilter === 'cancelled') ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>
            <button type="submit" class="button" style="padding: 7px 16px; background: #2271b1; color: #fff; border: 1px solid #2271b1; border-radius: 4px; cursor: pointer; font-size: 13px; font-weight: 500;">Filter</button>
            <?php if (!empty($search) || $statusFilter !== 'all' || $paymentFilter !== 'all' || $fulfillmentFilter !== 'all'): ?>
                <a href="/admin/page/favorite-digital-orders" class="button" style="padding: 7px 14px; background: #f6f7f7; color: #50575e; border: 1px solid #8c8f94; border-radius: 4px; text-decoration: none; font-size: 13px;">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Bulk Action & List Form -->
    <form id="fd-orders-bulk-form" method="POST" action="/admin/page/favorite-digital-orders">
        <input type="hidden" name="_token" value="<?= htmlspecialchars((string)($csrfToken ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="action" value="bulk_action">
        <input type="hidden" name="redirect_to" value="<?= htmlspecialchars((string)($_SERVER['REQUEST_URI'] ?? '/admin/page/favorite-digital-orders'), ENT_QUOTES, 'UTF-8') ?>">

        <!-- Bulk Action Toolbar -->
        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
            <select name="bulk_action" style="padding: 6px 12px; border: 1px solid #8c8f94; border-radius: 4px; font-size: 13px; background: #fff;">
                <option value="">Bulk Actions</option>
                <option value="processing">Mark Processing</option>
                <option value="completed">Mark Completed</option>
                <option value="cancelled">Cancel Orders</option>
            </select>
            <button type="submit" class="button action" style="padding: 6px 14px; background: #f6f7f7; border: 1px solid #8c8f94; border-radius: 4px; cursor: pointer; font-size: 13px; font-weight: 500;">Apply</button>
            <span class="bulk-count-badge" style="font-size: 12px; color: #646970; margin-left: 6px; font-weight: 500;"></span>
        </div>

        <!-- Orders Table Card -->
        <div style="background: #fff; border: 1px solid #c3c4c7; border-radius: 6px; overflow-x: auto; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
            <table class="wp-list-table widefat fixed striped" style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13px;">
                <thead>
                    <tr style="background: #f8f9fa; border-bottom: 1px solid #c3c4c7; color: #50575e;">
                        <th style="width: 44px; text-align: center; padding: 12px 10px;">
                            <input type="checkbox" data-select-all aria-label="Select all orders on this page">
                        </th>
                        <th style="width: 200px; padding: 12px 14px; font-weight: 600;">Order #</th>
                        <th style="width: 110px; padding: 12px 14px; font-weight: 600;">Customer</th>
                        <th style="width: 130px; padding: 12px 14px; font-weight: 600;">Order Status</th>
                        <th style="width: 130px; padding: 12px 14px; font-weight: 600;">Payment</th>
                        <th style="width: 130px; padding: 12px 14px; font-weight: 600;">Fulfillment</th>
                        <th style="text-align: right; width: 130px; padding: 12px 14px; font-weight: 600;">Total</th>
                        <th style="width: 180px; padding: 12px 14px; font-weight: 600;">Created Date</th>
                        <th style="width: 90px; text-align: center; padding: 12px 14px; font-weight: 600;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 36px 20px; color: #646970; font-size: 14px;">
                                No orders found matching criteria.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                            <?php
                            $status = (string)$order->status;
                            $payStatus = (string)$order->payment_status;
                            $fulStatus = (string)$order->fulfillment_status;

                            // Status badge colors
                            $orderBadgeStyle = match ($status) {
                                'completed' => 'background: #e6f7ec; color: #155724; border: 1px solid #b7ebc7;',
                                'processing' => 'background: #e8f4fd; color: #0c5460; border: 1px solid #bee5eb;',
                                'partial' => 'background: #fef3c7; color: #92400e; border: 1px solid #fde68a;',
                                'pending' => 'background: #fff8e5; color: #856404; border: 1px solid #ffeeba;',
                                'cancelled' => 'background: #fdf2f2; color: #721c24; border: 1px solid #f5c6cb;',
                                'failed' => 'background: #fdf2f2; color: #721c24; border: 1px solid #f5c6cb;',
                                'refunded' => 'background: #f3e8fd; color: #4a154b; border: 1px solid #e0c4f8;',
                                default => 'background: #f0f0f1; color: #50575e; border: 1px solid #dcdcde;',
                            };

                            $payBadgeStyle = match ($payStatus) {
                                'paid' => 'background: #e6f7ec; color: #155724; border: 1px solid #b7ebc7;',
                                'pending' => 'background: #fff8e5; color: #856404; border: 1px solid #ffeeba;',
                                'partially_paid' => 'background: #e8f4fd; color: #0c5460; border: 1px solid #bee5eb;',
                                'partially_refunded' => 'background: #ede9fe; color: #5b21b6; border: 1px solid #ddd6fe;',
                                'failed' => 'background: #fdf2f2; color: #721c24; border: 1px solid #f5c6cb;',
                                'refunded' => 'background: #f3e8fd; color: #4a154b; border: 1px solid #e0c4f8;',
                                default => 'background: #f0f0f1; color: #50575e; border: 1px solid #dcdcde;', // unpaid
                            };

                            $fulBadgeStyle = match ($fulStatus) {
                                'fulfilled' => 'background: #e6f7ec; color: #155724; border: 1px solid #b7ebc7;',
                                'partially_fulfilled' => 'background: #e8f4fd; color: #0c5460; border: 1px solid #bee5eb;',
                                'cancelled' => 'background: #fdf2f2; color: #721c24; border: 1px solid #f5c6cb;',
                                default => 'background: #f0f0f1; color: #50575e; border: 1px solid #dcdcde;', // unfulfilled
                            };

                            $formattedDate = fdig_format_datetime($order->created_at, 'd M Y, h:i A');
                            ?>
                            <tr style="border-bottom: 1px solid #f0f0f1;">
                                <td style="text-align: center; padding: 12px 10px; vertical-align: middle;">
                                    <input type="checkbox" name="ids[]" value="<?= (int)$order->id ?>" aria-label="Select order #<?= (int)$order->id ?>">
                                </td>
                                <td style="padding: 12px 14px; vertical-align: middle;">
                                    <strong>
                                        <a href="/admin/page/favorite-digital-orders?action=view&id=<?= (int)$order->id ?>" style="color: #2271b1; text-decoration: none; font-weight: 600; font-family: monospace; font-size: 13px;">
                                            <?= htmlspecialchars((string)$order->order_number, ENT_QUOTES, 'UTF-8') ?>
                                        </a>
                                    </strong>
                                    <?php if (!empty($order->notes)): ?>
                                        <div style="font-size: 11px; color: #646970; font-style: italic; margin-top: 2px; max-width: 220px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= htmlspecialchars((string)$order->notes, ENT_QUOTES, 'UTF-8') ?>">
                                            <?= htmlspecialchars((string)$order->notes, ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 12px 14px; vertical-align: middle; color: #50575e;">
                                    User #<?= (int)$order->user_id ?>
                                </td>
                                <td style="padding: 12px 14px; vertical-align: middle;">
                                    <span class="badge badge-<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>" style="display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; text-transform: uppercase; <?= $orderBadgeStyle ?>">
                                        <?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td style="padding: 12px 14px; vertical-align: middle;">
                                    <span class="badge badge-pay-<?= htmlspecialchars($payStatus, ENT_QUOTES, 'UTF-8') ?>" style="display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; text-transform: uppercase; <?= $payBadgeStyle ?>">
                                        <?= htmlspecialchars($payStatus, ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td style="padding: 12px 14px; vertical-align: middle;">
                                    <span class="badge badge-ful-<?= htmlspecialchars($fulStatus, ENT_QUOTES, 'UTF-8') ?>" style="display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; text-transform: uppercase; <?= $fulBadgeStyle ?>">
                                        <?= htmlspecialchars($fulStatus, ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td style="text-align: right; padding: 12px 14px; vertical-align: middle; font-weight: 600; font-family: -apple-system, BlinkMacSystemFont, monospace; color: #1d2327;">
                                    <?= htmlspecialchars((string)$order->currency, ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars(number_format((float)$order->total_amount, 2), ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td style="padding: 12px 14px; vertical-align: middle; color: #50575e; white-space: nowrap;">
                                    <?= htmlspecialchars($formattedDate, ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td style="text-align: center; padding: 12px 14px; vertical-align: middle;">
                                    <a href="/admin/page/favorite-digital-orders?action=view&id=<?= (int)$order->id ?>" class="button button-small" style="padding: 4px 10px; font-size: 12px; border-radius: 3px; text-decoration: none; display: inline-block;">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </form>

    <!-- Pagination -->
    <?php if (($totalPages ?? 1) > 1): ?>
        <div class="tablenav" style="margin-top: 18px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
            <div class="tablenav-pages" style="display: flex; align-items: center; gap: 6px;">
                <span class="displaying-num" style="color: #646970; font-size: 13px; margin-right: 8px;"><?= (int)$total ?> orders</span>
                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <?php if ($p === (int)$page): ?>
                        <span class="button button-primary disabled" style="background: #2271b1; color: #fff; border-color: #2271b1; font-weight: 600; padding: 4px 10px; font-size: 13px;"><?= $p ?></span>
                    <?php else: ?>
                        <a href="/admin/page/favorite-digital-orders?page=<?= $p ?>&status=<?= urlencode((string)$statusFilter) ?>&payment_status=<?= urlencode((string)$paymentFilter) ?>&fulfillment_status=<?= urlencode((string)$fulfillmentFilter) ?>&search=<?= urlencode((string)$search) ?>" class="button" style="padding: 4px 10px; font-size: 13px; color: #2271b1; background: #f6f7f7; border: 1px solid #c3c4c7; text-decoration: none; border-radius: 3px;"><?= $p ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof window.initAdminMultiSelect === 'function') {
        window.initAdminMultiSelect('fd-orders-bulk-form', {
            itemType: 'order',
            confirmMessages: {
                cancelled: 'Are you sure you want to cancel the selected orders? Eligible orders will be cancelled and refunded where applicable.'
            }
        });
    }
});
</script>
