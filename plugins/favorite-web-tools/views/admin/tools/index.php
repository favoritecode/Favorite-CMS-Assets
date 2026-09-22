<?php
/**
 * Admin Tools List View
 *
 * Variables:
 * - $tools        : array<Tool>
 * - $counts       : array<string, int>
 * - $categories   : array<ToolCategory>
 * - $categoryMap  : array<int, string>
 * - $statusFilter : ?string
 * - $catFilter    : ?int
 * - $search       : string
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
            <h1 style="font-size: 22px; font-weight: 700; margin: 0; color: #1e293b;">Web Tools</h1>
            <span style="background: #f1f5f9; color: #475569; padding: 3px 10px; border-radius: 12px; font-size: 12px; font-weight: 600;">
                <?php echo (int)($counts['all'] ?? count($tools)); ?> total
            </span>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="/admin/page/favorite-web-tools?action=create" style="display: inline-flex; align-items: center; gap: 6px; background: #2563eb; color: #fff; text-decoration: none; padding: 8px 16px; border-radius: 6px; font-weight: 600; font-size: 13px;">
                + Add New Tool
            </a>
        </div>
    </div>

    <!-- Status Tabs & Filter Toolbar -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 20px;">
        <!-- Status Filters -->
        <div style="display: flex; gap: 6px; font-size: 13px;">
            <a href="/admin/page/favorite-web-tools" style="text-decoration: none; padding: 6px 12px; border-radius: 6px; <?php echo ($statusFilter === null) ? 'background: #2563eb; color: #fff; font-weight: 600;' : 'color: #334155; background: #f1f5f9;'; ?>">
                All (<?php echo (int)($counts['all'] ?? 0); ?>)
            </a>
            <a href="/admin/page/favorite-web-tools?status=active" style="text-decoration: none; padding: 6px 12px; border-radius: 6px; <?php echo ($statusFilter === 'active') ? 'background: #2563eb; color: #fff; font-weight: 600;' : 'color: #334155; background: #f1f5f9;'; ?>">
                Active (<?php echo (int)($counts['active'] ?? 0); ?>)
            </a>
            <a href="/admin/page/favorite-web-tools?status=draft" style="text-decoration: none; padding: 6px 12px; border-radius: 6px; <?php echo ($statusFilter === 'draft') ? 'background: #2563eb; color: #fff; font-weight: 600;' : 'color: #334155; background: #f1f5f9;'; ?>">
                Draft (<?php echo (int)($counts['draft'] ?? 0); ?>)
            </a>
            <a href="/admin/page/favorite-web-tools?status=disabled" style="text-decoration: none; padding: 6px 12px; border-radius: 6px; <?php echo ($statusFilter === 'disabled') ? 'background: #2563eb; color: #fff; font-weight: 600;' : 'color: #334155; background: #f1f5f9;'; ?>">
                Disabled (<?php echo (int)($counts['disabled'] ?? 0); ?>)
            </a>
        </div>

        <!-- Search & Category Filters Form -->
        <form method="GET" action="/admin/page/favorite-web-tools" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            <?php if ($statusFilter): ?>
                <input type="hidden" name="status" value="<?php echo htmlspecialchars($statusFilter, ENT_QUOTES, 'UTF-8'); ?>">
            <?php endif; ?>

            <select name="category_id" onchange="this.form.submit();" style="padding: 6px 12px; font-size: 13px; border: 1px solid #cbd5e1; border-radius: 6px; background: #fff;">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo (int)$cat->id; ?>" <?php echo $catFilter === (int)$cat->id ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat->name, ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <input type="text" name="search" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Search tools..."
                   style="padding: 6px 12px; font-size: 13px; border: 1px solid #cbd5e1; border-radius: 6px; width: 180px;">

            <button type="submit" style="padding: 6px 12px; font-size: 13px; border: 1px solid #cbd5e1; border-radius: 6px; background: #f8fafc; cursor: pointer;">
                Filter
            </button>
            <?php if ($search !== '' || $catFilter !== null || $statusFilter !== null): ?>
                <a href="/admin/page/favorite-web-tools" style="font-size: 12px; color: #2563eb; text-decoration: none;">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Tools Table -->
    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden;">
        <table style="width: 100%; border-collapse: collapse; font-size: 13px; text-align: left;">
            <thead>
                <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; color: #475569;">
                    <th style="padding: 12px 16px;">Tool</th>
                    <th style="padding: 12px 16px;">Category</th>
                    <th style="padding: 12px 16px;">Engine</th>
                    <th style="padding: 12px 16px;">Access Mode</th>
                    <th style="padding: 12px 16px;">Status</th>
                    <th style="padding: 12px 16px; text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tools)): ?>
                    <tr>
                        <td colspan="6" style="padding: 40px; text-align: center; color: #64748b;">
                            No tools found matching your criteria.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($tools as $tool): ?>
                        <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.1s;">
                            <td style="padding: 12px 16px;">
                                <div style="display: flex; gap: 10px; align-items: center;">
                                    <span style="font-size: 20px;"><?php echo htmlspecialchars($tool->icon ?: '🛠️', ENT_QUOTES, 'UTF-8'); ?></span>
                                    <div>
                                        <div style="font-weight: 600; color: #0f172a;">
                                            <a href="/admin/page/favorite-web-tools?action=edit&id=<?php echo (int)$tool->id; ?>" style="color: inherit; text-decoration: none;">
                                                <?php echo htmlspecialchars($tool->name, ENT_QUOTES, 'UTF-8'); ?>
                                            </a>
                                        </div>
                                        <div style="font-size: 11px; color: #64748b; font-family: monospace;">
                                            /tools/<?php echo htmlspecialchars($tool->slug, ENT_QUOTES, 'UTF-8'); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 12px 16px; color: #334155;">
                                <?php echo htmlspecialchars($categoryMap[$tool->category_id] ?? 'None', ENT_QUOTES, 'UTF-8'); ?>
                            </td>
                            <td style="padding: 12px 16px;">
                                <span style="font-family: monospace; font-size: 12px; background: #f1f5f9; padding: 2px 6px; border-radius: 4px;">
                                    <?php echo htmlspecialchars($tool->engine, ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </td>
                            <td style="padding: 12px 16px;">
                                <?php if ($tool->access_mode === 'FREE'): ?>
                                    <span style="color: #16a34a; font-weight: 500;">Free</span>
                                <?php elseif ($tool->access_mode === 'LOGIN_REQUIRED'): ?>
                                    <span style="color: #2563eb; font-weight: 500;">Login Required</span>
                                <?php elseif ($tool->access_mode === 'MEMBERSHIP_REQUIRED'): ?>
                                    <span style="color: #d97706; font-weight: 500;">⭐ Member Required</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 12px 16px;">
                                <?php if ($tool->isActive()): ?>
                                    <span style="background: #e6f4ea; color: #137333; font-weight: 600; padding: 2px 8px; border-radius: 10px; font-size: 11px;">ACTIVE</span>
                                <?php elseif ($tool->isDraft()): ?>
                                    <span style="background: #fef7e0; color: #b06000; font-weight: 600; padding: 2px 8px; border-radius: 10px; font-size: 11px;">DRAFT</span>
                                <?php else: ?>
                                    <span style="background: #f1f5f9; color: #475569; font-weight: 600; padding: 2px 8px; border-radius: 10px; font-size: 11px;">DISABLED</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 12px 16px; text-align: right; white-space: nowrap;">
                                <a href="/admin/page/favorite-web-tools?action=edit&id=<?php echo (int)$tool->id; ?>" style="color: #2563eb; text-decoration: none; margin-right: 8px; font-weight: 500;">Edit</a>
                                <a href="/admin/page/favorite-web-tools?action=test&id=<?php echo (int)$tool->id; ?>" style="color: #0891b2; text-decoration: none; margin-right: 8px; font-weight: 500;">Test</a>
                                <a href="/tools/<?php echo urlencode($tool->slug); ?>" target="_blank" style="color: #475569; text-decoration: none; margin-right: 8px;" title="View public page">↗</a>

                                <?php if (!$tool->isActive()): ?>
                                    <form method="POST" action="/admin/page/favorite-web-tools" style="display: inline-block; margin-right: 4px;">
                                        <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="action" value="activate">
                                        <input type="hidden" name="id" value="<?php echo (int)$tool->id; ?>">
                                        <button type="submit" style="background: none; border: none; color: #16a34a; cursor: pointer; padding: 0; font-size: 13px;">Activate</button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" action="/admin/page/favorite-web-tools" style="display: inline-block; margin-right: 4px;">
                                        <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="action" value="disable">
                                        <input type="hidden" name="id" value="<?php echo (int)$tool->id; ?>">
                                        <button type="submit" style="background: none; border: none; color: #d97706; cursor: pointer; padding: 0; font-size: 13px;">Disable</button>
                                    </form>
                                <?php endif; ?>

                                <form method="POST" action="/admin/page/favorite-web-tools" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to delete this tool?');">
                                    <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo (int)$tool->id; ?>">
                                    <button type="submit" style="background: none; border: none; color: #dc2626; cursor: pointer; padding: 0; font-size: 13px; margin-left: 4px;">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

