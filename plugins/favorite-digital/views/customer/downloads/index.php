<?php
/**
 * Customer Digital Downloads View
 */
?>
<div class="customer-downloads" style="max-width: 900px; margin: 30px auto; padding: 25px; background: var(--surface, #ffffff); border: 1px solid var(--border, #e2e8f0); border-radius: 12px;">
    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid var(--border-strong, #cbd5e1); padding-bottom: 15px;">
        <div>
            <h1 style="margin: 0; font-size: 24px; font-weight: 800; color: var(--heading, #0f172a);">My Digital Downloads</h1>
            <p style="margin: 5px 0 0 0; color: var(--muted, #64748b);">Access your purchased digital files and membership resources</p>
        </div>
        <div>
            <a href="/account/orders" style="display: inline-block; padding: 8px 14px; background: var(--surface-muted, #f1f5f9); color: var(--text, #334155); border: 1px solid var(--border, #e2e8f0); border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 600;">&larr; My Orders</a>
        </div>
    </div>

    <?php if (empty($items)): ?>
        <div style="padding: 48px 20px; text-align: center; color: var(--muted, #64748b);">
            <p style="font-size: 16px; margin: 0;">You do not have any downloadable digital files available yet.</p>
        </div>
    <?php else: ?>
        <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
            <thead>
                <tr style="border-bottom: 2px solid var(--border-strong, #cbd5e1); text-align: left; background: var(--surface-muted, #f8fafc);">
                    <th style="padding: 12px; color: var(--muted, #64748b); font-size: 12px; font-weight: 700; text-transform: uppercase;">Product</th>
                    <th style="padding: 12px; color: var(--muted, #64748b); font-size: 12px; font-weight: 700; text-transform: uppercase;">Access Type</th>
                    <th style="padding: 12px; text-align: center; color: var(--muted, #64748b); font-size: 12px; font-weight: 700; text-transform: uppercase;">Downloads Used</th>
                    <th style="padding: 12px; text-align: right; color: var(--muted, #64748b); font-size: 12px; font-weight: 700; text-transform: uppercase;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr style="border-bottom: 1px solid var(--border, #e2e8f0);">
                        <td style="padding: 12px; color: var(--text, #1e293b);">
                            <strong style="color: var(--heading, #0f172a); font-size: 15px;"><?= htmlspecialchars($item['product_title'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <?php if (!empty($item['file_size_formatted'])): ?>
                                <div style="font-size: 12px; color: var(--muted, #64748b); margin-top: 3px;">
                                    Size: <strong><?= htmlspecialchars($item['file_size_formatted'], ENT_QUOTES, 'UTF-8') ?></strong>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($item['expires_at'])): ?>
                                <div style="font-size: 12px; color: var(--muted, #64748b); margin-top: 2px;">Expires: <?= htmlspecialchars((string)$item['expires_at'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 12px;">
                            <?php if ($item['is_membership']): ?>
                                <span style="background: var(--success-soft, #ecfdf5); color: var(--success, #059669); border: 1px solid var(--success-border, #a7f3d0); padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: 700;">Membership</span>
                            <?php else: ?>
                                <span style="background: var(--accent-soft, #eff6ff); color: var(--accent, #2563eb); border: 1px solid var(--border, #bfdbfe); padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: 700;"><?= strtoupper(htmlspecialchars($item['source_type'], ENT_QUOTES, 'UTF-8')) ?></span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 12px; text-align: center;">
                            <?php if ($item['is_membership']): ?>
                                <span style="color: var(--success, #059669); font-weight: 700;">Unlimited</span>
                                <div style="font-size: 11px; color: var(--muted, #64748b);"><?= (int)$item['download_count'] ?> used</div>
                            <?php else: ?>
                                <strong style="color: var(--heading, #0f172a);"><?= (int)$item['download_count'] ?> / <?= (int)$item['max_limit'] ?></strong>
                                <div style="font-size: 11px; color: <?= $item['remaining'] > 0 ? 'var(--muted, #64748b)' : 'var(--danger, #dc2626)' ?>;">
                                    <?= $item['remaining'] > 0 ? $item['remaining'] . ' remaining' : 'Limit reached' ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 12px; text-align: right;">
                            <?php if ($item['is_exhausted']): ?>
                                <button disabled style="padding: 7px 14px; background: var(--surface-muted, #f1f5f9); border: 1px solid var(--border, #e2e8f0); border-radius: 6px; color: var(--muted, #94a3b8); cursor: not-allowed; font-size: 13px; font-weight: 600;">
                                    Limit Reached
                                </button>
                            <?php else: ?>
                                <a href="/download/<?= htmlspecialchars($item['token'], ENT_QUOTES, 'UTF-8') ?>"
                                   target="<?= !empty($item['is_external']) ? '_blank' : '_self' ?>"
                                   style="display: inline-block; padding: 7px 14px; background: var(--accent, #2563eb); color: #fff; text-decoration: none; border-radius: 6px; font-size: 13px; font-weight: 600;">
                                    Download File
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
