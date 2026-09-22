<?php
/**
 * Admin Dashboard View
 *
 * Variables:
 * - $totalTools           : int
 * - $activeTools          : int
 * - $draftTools           : int
 * - $disabledTools        : int
 * - $categoriesCount      : int
 * - $pythonServicesCount  : int
 * - $activePythonServices : int
 * - $recentTools          : array<Tool>
 * - $categories           : array<ToolCategory>
 */
?>
<div class="fwt-admin-wrap" style="max-width: 1200px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
    <!-- Top Action Bar -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 24px;">
        <div>
            <h1 style="font-size: 22px; font-weight: 700; margin: 0 0 4px 0; color: #1e293b;">Web Tools Overview</h1>
            <p style="font-size: 14px; color: #64748b; margin: 0;">Manage your online tools, categories, and Python API microservices.</p>
        </div>
        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <a href="/admin/page/favorite-web-tools?action=create" style="display: inline-flex; align-items: center; gap: 6px; background: #2563eb; color: #fff; text-decoration: none; padding: 8px 16px; border-radius: 6px; font-weight: 600; font-size: 13px;">
                + Add Tool
            </a>
            <a href="/admin/page/favorite-web-tools-categories?action=create" style="display: inline-flex; align-items: center; gap: 6px; background: #f1f5f9; color: #334155; text-decoration: none; padding: 8px 16px; border-radius: 6px; font-weight: 600; font-size: 13px; border: 1px solid #cbd5e1;">
                + Add Category
            </a>
            <a href="/tools" target="_blank" style="display: inline-flex; align-items: center; gap: 6px; background: #f1f5f9; color: #334155; text-decoration: none; padding: 8px 16px; border-radius: 6px; font-weight: 600; font-size: 13px; border: 1px solid #cbd5e1;">
                View Catalog ↗
            </a>
        </div>
    </div>

    <!-- Stats Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 28px;">
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 18px;">
            <div style="font-size: 12px; font-weight: 600; color: #64748b; text-transform: uppercase;">Total Tools</div>
            <div style="font-size: 28px; font-weight: 800; color: #0f172a; margin-top: 4px;"><?php echo (int)$totalTools; ?></div>
        </div>

        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 18px; border-top: 3px solid #16a34a;">
            <div style="font-size: 12px; font-weight: 600; color: #16a34a; text-transform: uppercase;">Active Tools</div>
            <div style="font-size: 28px; font-weight: 800; color: #16a34a; margin-top: 4px;"><?php echo (int)$activeTools; ?></div>
        </div>

        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 18px; border-top: 3px solid #f59e0b;">
            <div style="font-size: 12px; font-weight: 600; color: #d97706; text-transform: uppercase;">Draft Tools</div>
            <div style="font-size: 28px; font-weight: 800; color: #d97706; margin-top: 4px;"><?php echo (int)$draftTools; ?></div>
        </div>

        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 18px; border-top: 3px solid #94a3b8;">
            <div style="font-size: 12px; font-weight: 600; color: #64748b; text-transform: uppercase;">Disabled Tools</div>
            <div style="font-size: 28px; font-weight: 800; color: #64748b; margin-top: 4px;"><?php echo (int)$disabledTools; ?></div>
        </div>

        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 18px; border-top: 3px solid #6366f1;">
            <div style="font-size: 12px; font-weight: 600; color: #6366f1; text-transform: uppercase;">Categories</div>
            <div style="font-size: 28px; font-weight: 800; color: #4338ca; margin-top: 4px;"><?php echo (int)$categoriesCount; ?></div>
        </div>

        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 18px; border-top: 3px solid #06b6d4;">
            <div style="font-size: 12px; font-weight: 600; color: #0891b2; text-transform: uppercase;">Python Services</div>
            <div style="font-size: 28px; font-weight: 800; color: #0e7490; margin-top: 4px;"><?php echo (int)$pythonServicesCount; ?></div>
        </div>
    </div>

    <!-- Recent Tools Table -->
    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 20px; margin-bottom: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h2 style="font-size: 16px; font-weight: 700; margin: 0; color: #0f172a;">Recently Updated Tools</h2>
            <a href="/admin/page/favorite-web-tools" style="font-size: 13px; color: #2563eb; text-decoration: none; font-weight: 600;">View All Tools →</a>
        </div>

        <?php if (empty($recentTools)): ?>
            <p style="color: #64748b; font-size: 14px; margin: 0;">No tools have been added yet.</p>
        <?php else: ?>
            <table style="width: 100%; border-collapse: collapse; font-size: 13px; text-align: left;">
                <thead>
                    <tr style="border-bottom: 1px solid #e2e8f0; color: #64748b;">
                        <th style="padding: 10px 12px;">Tool</th>
                        <th style="padding: 10px 12px;">Engine</th>
                        <th style="padding: 10px 12px;">Access</th>
                        <th style="padding: 10px 12px;">Status</th>
                        <th style="padding: 10px 12px; text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentTools as $t): ?>
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 10px 12px;">
                                <div style="display: flex; gap: 8px; align-items: center;">
                                    <span><?php echo htmlspecialchars($t->icon ?: '🛠️', ENT_QUOTES, 'UTF-8'); ?></span>
                                    <div>
                                        <a href="/admin/page/favorite-web-tools?action=edit&id=<?php echo (int)$t->id; ?>" style="font-weight: 600; color: #1e293b; text-decoration: none;">
                                            <?php echo htmlspecialchars($t->name, ENT_QUOTES, 'UTF-8'); ?>
                                        </a>
                                        <div style="font-size: 11px; color: #94a3b8; font-family: monospace;">/tools/<?php echo htmlspecialchars($t->slug, ENT_QUOTES, 'UTF-8'); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 10px 12px;">
                                <span style="font-family: monospace; font-size: 12px; background: #f1f5f9; padding: 2px 6px; border-radius: 4px;">
                                    <?php echo htmlspecialchars($t->engine, ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </td>
                            <td style="padding: 10px 12px;">
                                <?php if ($t->access_mode === 'FREE'): ?>
                                    <span style="color: #16a34a; font-weight: 500;">Free</span>
                                <?php elseif ($t->access_mode === 'LOGIN_REQUIRED'): ?>
                                    <span style="color: #2563eb; font-weight: 500;">Login</span>
                                <?php elseif ($t->access_mode === 'MEMBERSHIP_REQUIRED'): ?>
                                    <span style="color: #d97706; font-weight: 500;">⭐ Member</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 10px 12px;">
                                <?php if ($t->isActive()): ?>
                                    <span style="color: #16a34a; font-weight: 600;">ACTIVE</span>
                                <?php elseif ($t->isDraft()): ?>
                                    <span style="color: #d97706; font-weight: 600;">DRAFT</span>
                                <?php else: ?>
                                    <span style="color: #64748b; font-weight: 600;">DISABLED</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 10px 12px; text-align: right;">
                                <a href="/admin/page/favorite-web-tools?action=edit&id=<?php echo (int)$t->id; ?>" style="color: #2563eb; text-decoration: none; margin-right: 8px;">Edit</a>
                                <a href="/admin/page/favorite-web-tools?action=test&id=<?php echo (int)$t->id; ?>" style="color: #0891b2; text-decoration: none;">Test</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

