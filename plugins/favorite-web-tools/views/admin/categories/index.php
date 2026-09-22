<?php
/**
 * Admin Categories List View
 *
 * Variables:
 * - $categories   : array<ToolCategory>
 * - $toolCounts   : array<int, int>
 * - $csrfToken    : string
 * - $flashSuccess : ?string
 * - $flashError   : ?string
 */
?>
<div class="fwt-admin-wrap" style="max-width: 1100px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
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
            <h1 style="font-size: 22px; font-weight: 700; margin: 0; color: #1e293b;">Tool Categories</h1>
            <span style="background: #f1f5f9; color: #475569; padding: 3px 10px; border-radius: 12px; font-size: 12px; font-weight: 600;">
                <?php echo count($categories); ?> total
            </span>
        </div>
        <div>
            <a href="/admin/page/favorite-web-tools-categories?action=create" style="display: inline-flex; align-items: center; gap: 6px; background: #2563eb; color: #fff; text-decoration: none; padding: 8px 16px; border-radius: 6px; font-weight: 600; font-size: 13px;">
                + Add New Category
            </a>
        </div>
    </div>

    <!-- Categories Table -->
    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden;">
        <table style="width: 100%; border-collapse: collapse; font-size: 13px; text-align: left;">
            <thead>
                <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; color: #475569;">
                    <th style="padding: 12px 16px;">Category</th>
                    <th style="padding: 12px 16px;">Slug</th>
                    <th style="padding: 12px 16px;">Tools</th>
                    <th style="padding: 12px 16px;">Order</th>
                    <th style="padding: 12px 16px;">Status</th>
                    <th style="padding: 12px 16px; text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($categories)): ?>
                    <tr>
                        <td colspan="6" style="padding: 40px; text-align: center; color: #64748b;">
                            No categories created yet.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($categories as $cat): ?>
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 12px 16px;">
                                <div style="display: flex; gap: 10px; align-items: center;">
                                    <?php echo \FavoriteCMS\Tools\Support\IconRenderer::render($cat->icon ?: '📁', 'fwt-admin-cat-icon', 20); ?>
                                    <div>
                                        <div style="font-weight: 600; color: #0f172a;">
                                            <a href="/admin/page/favorite-web-tools-categories?action=edit&id=<?php echo (int)$cat->id; ?>" style="color: inherit; text-decoration: none;">
                                                <?php echo htmlspecialchars($cat->name, ENT_QUOTES, 'UTF-8'); ?>
                                            </a>
                                        </div>
                                        <div style="font-size: 12px; color: #64748b;">
                                            <?php echo htmlspecialchars($cat->description ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 12px 16px; font-family: monospace; color: #475569;">
                                <?php echo htmlspecialchars($cat->slug, ENT_QUOTES, 'UTF-8'); ?>
                            </td>
                            <td style="padding: 12px 16px;">
                                <span style="background: #f1f5f9; padding: 2px 8px; border-radius: 10px; font-weight: 600;">
                                    <?php echo (int)($toolCounts[$cat->id] ?? 0); ?>
                                </span>
                            </td>
                            <td style="padding: 12px 16px; color: #64748b;">
                                <?php echo (int)$cat->display_order; ?>
                            </td>
                            <td style="padding: 12px 16px;">
                                <?php if ($cat->isActive()): ?>
                                    <span style="background: #e6f4ea; color: #137333; font-weight: 600; padding: 2px 8px; border-radius: 10px; font-size: 11px;">ACTIVE</span>
                                <?php else: ?>
                                    <span style="background: #f1f5f9; color: #64748b; font-weight: 600; padding: 2px 8px; border-radius: 10px; font-size: 11px;">INACTIVE</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 12px 16px; text-align: right; white-space: nowrap;">
                                <a href="/admin/page/favorite-web-tools-categories?action=edit&id=<?php echo (int)$cat->id; ?>" style="color: #2563eb; text-decoration: none; margin-right: 8px;">Edit</a>
                                <a href="/tools/category/<?php echo urlencode($cat->slug); ?>" target="_blank" style="color: #475569; text-decoration: none; margin-right: 8px;" title="View category in frontend">↗</a>
                                <form method="POST" action="/admin/page/favorite-web-tools-categories" style="display: inline-block;" onsubmit="return confirm('Delete this category?');">
                                    <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo (int)$cat->id; ?>">
                                    <button type="submit" style="background: none; border: none; color: #dc2626; cursor: pointer; padding: 0; font-size: 13px;">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

