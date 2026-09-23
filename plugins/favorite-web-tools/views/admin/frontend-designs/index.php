<?php
/**
 * Admin Frontend Designs List View
 *
 * Variables:
 * - $designs      : array<FrontendDesign>
 * - $usageCounts  : array<string, int>
 * - $csrfToken    : string
 * - $flashSuccess : ?string
 * - $flashError   : ?string
 */
?>
<div class="fwt-admin-wrap" style="max-width: 1200px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
    <?php if (!empty($flashSuccess)): ?>
        <div style="background: #e7f7ed; border-left: 4px solid #28a745; padding: 12px 16px; margin-bottom: 20px; border-radius: 4px; color: #155724; font-size: 14px;">
            <strong>Success:</strong> <?php echo htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($flashError)): ?>
        <div style="background: #fdf2f2; border-left: 4px solid #dc3545; padding: 12px 16px; margin-bottom: 20px; border-radius: 4px; color: #721c24; font-size: 14px;">
            <strong>Error:</strong> <?php echo htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <!-- Top Action Bar -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 20px;">
        <div style="display: flex; gap: 10px; align-items: center;">
            <h1 style="font-size: 22px; font-weight: 700; margin: 0; color: #1e293b;">Universal Frontend Designs</h1>
            <span style="background: #f1f5f9; color: #475569; padding: 3px 10px; border-radius: 12px; font-size: 12px; font-weight: 600;">
                <?php echo count($designs); ?> available
            </span>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="/admin/page/favorite-web-tools-frontend-designs?action=import" style="display: inline-flex; align-items: center; gap: 6px; background: #0284c7; color: #fff; text-decoration: none; padding: 9px 16px; border-radius: 6px; font-weight: 600; font-size: 13px;">
                📥 Import ZIP Package
            </a>
            <a href="/admin/page/favorite-web-tools-frontend-designs?action=create" style="display: inline-flex; align-items: center; gap: 6px; background: #2563eb; color: #fff; text-decoration: none; padding: 9px 16px; border-radius: 6px; font-weight: 600; font-size: 13px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                + Create Design
            </a>
        </div>
    </div>

    <!-- Frontend Designs Table -->
    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
        <table style="width: 100%; border-collapse: collapse; font-size: 13px; text-align: left;">
            <thead>
                <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; color: #475569;">
                    <th style="padding: 12px 16px;">Design Name & Slug</th>
                    <th style="padding: 12px 16px;">Category</th>
                    <th style="padding: 12px 16px;">Type</th>
                    <th style="padding: 12px 16px;">Version</th>
                    <th style="padding: 12px 16px;">Assigned Tools</th>
                    <th style="padding: 12px 16px;">Status</th>
                    <th style="padding: 12px 16px; text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($designs)): ?>
                    <tr>
                        <td colspan="7" style="padding: 40px; text-align: center; color: #64748b;">
                            No frontend designs registered.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($designs as $design): ?>
                        <?php 
                            $slug = $design->getSlug();
                            $inUse = ($usageCounts[$slug] ?? 0);
                        ?>
                        <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.15s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                            <td style="padding: 12px 16px;">
                                <div style="font-weight: 600; color: #0f172a; font-size: 14px;">
                                    <?php echo htmlspecialchars($design->getName(), ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                                <div style="color: #64748b; font-family: monospace; font-size: 12px; margin-top: 2px;">
                                    <?php echo htmlspecialchars($slug, ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                            </td>
                            <td style="padding: 12px 16px; color: #475569;">
                                <?php echo htmlspecialchars($design->getCategory(), ENT_QUOTES, 'UTF-8'); ?>
                            </td>
                            <td style="padding: 12px 16px;">
                                <?php if ($design->isBuiltin()): ?>
                                    <span style="background: #e0f2fe; color: #0369a1; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 600;">
                                        Built-in
                                    </span>
                                <?php else: ?>
                                    <span style="background: #fef3c7; color: #92400e; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 600;">
                                        Custom
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 12px 16px; color: #64748b; font-size: 12px;">
                                v<?php echo htmlspecialchars($design->getVersion(), ENT_QUOTES, 'UTF-8'); ?>
                            </td>
                            <td style="padding: 12px 16px;">
                                <span style="display: inline-block; background: <?php echo $inUse > 0 ? '#dcfce7; color: #15803d' : '#f1f5f9; color: #64748b'; ?>; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: 600;">
                                    <?php echo $inUse; ?> <?php echo $inUse === 1 ? 'tool' : 'tools'; ?>
                                </span>
                            </td>
                            <td style="padding: 12px 16px;">
                                <?php if ($design->isActive()): ?>
                                    <span style="display: inline-flex; align-items: center; gap: 5px; color: #16a34a; font-weight: 600; font-size: 12px;">
                                        <span style="width: 7px; height: 7px; background: #16a34a; border-radius: 50%;"></span> Active
                                    </span>
                                <?php else: ?>
                                    <span style="display: inline-flex; align-items: center; gap: 5px; color: #94a3b8; font-weight: 600; font-size: 12px;">
                                        <span style="width: 7px; height: 7px; background: #94a3b8; border-radius: 50%;"></span> Disabled
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 12px 16px; text-align: right;">
                                <div style="display: inline-flex; gap: 6px; align-items: center;">
                                    <a href="/admin/page/favorite-web-tools-frontend-designs?action=preview&id=<?php echo (int)$design->getId(); ?>"
                                       style="color: #0284c7; text-decoration: none; font-weight: 500; font-size: 12px; padding: 4px 8px; border-radius: 4px; background: #e0f2fe;">
                                        Preview
                                    </a>
                                    <a href="/admin/page/favorite-web-tools-frontend-designs?action=edit&id=<?php echo (int)$design->getId(); ?>"
                                       style="color: #2563eb; text-decoration: none; font-weight: 500; font-size: 12px; padding: 4px 8px; border-radius: 4px; background: #eff6ff;">
                                        Edit
                                    </a>
                                    <a href="/admin/page/favorite-web-tools-frontend-designs?action=export&id=<?php echo (int)$design->getId(); ?>"
                                       style="color: #475569; text-decoration: none; font-weight: 500; font-size: 12px; padding: 4px 8px; border-radius: 4px; background: #f1f5f9;">
                                        Export
                                    </a>

                                    <form method="POST" action="/admin/page/favorite-web-tools-frontend-designs" style="display: inline; margin: 0;">
                                        <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="action" value="duplicate">
                                        <input type="hidden" name="id" value="<?php echo (int)$design->getId(); ?>">
                                        <button type="submit" style="color: #475569; background: #f1f5f9; border: none; font-weight: 500; font-size: 12px; padding: 4px 8px; border-radius: 4px; cursor: pointer;">
                                            Copy
                                        </button>
                                    </form>

                                    <?php if (!$design->isBuiltin() && $inUse === 0): ?>
                                        <form method="POST" action="/admin/page/favorite-web-tools-frontend-designs" style="display: inline; margin: 0;" onsubmit="return confirm('Are you sure you want to delete this custom design?');">
                                            <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo (int)$design->getId(); ?>">
                                            <button type="submit" style="color: #dc2626; background: #fee2e2; border: none; font-weight: 500; font-size: 12px; padding: 4px 8px; border-radius: 4px; cursor: pointer;">
                                                Delete
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

