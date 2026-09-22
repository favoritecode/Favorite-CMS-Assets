<?php
/**
 * Admin Python Services List View
 *
 * Variables:
 * - $services     : array<PythonService>
 * - $toolCounts   : array<int, int>
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
            <h1 style="font-size: 22px; font-weight: 700; margin: 0; color: #1e293b;">Python API Services</h1>
            <span style="background: #f1f5f9; color: #475569; padding: 3px 10px; border-radius: 12px; font-size: 12px; font-weight: 600;">
                <?php echo count($services); ?> configured
            </span>
        </div>
        <div>
            <a href="/admin/page/favorite-web-tools-python-services?action=create" style="display: inline-flex; align-items: center; gap: 6px; background: #2563eb; color: #fff; text-decoration: none; padding: 9px 18px; border-radius: 6px; font-weight: 600; font-size: 13px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                + Add Python Service
            </a>
        </div>
    </div>

    <!-- Python Services Table -->
    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
        <table style="width: 100%; border-collapse: collapse; font-size: 13px; text-align: left;">
            <thead>
                <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; color: #475569;">
                    <th style="padding: 12px 16px;">Service Name & Slug</th>
                    <th style="padding: 12px 16px;">Base URL</th>
                    <th style="padding: 12px 16px;">Default Endpoint</th>
                    <th style="padding: 12px 16px;">Method</th>
                    <th style="padding: 12px 16px;">Timeout</th>
                    <th style="padding: 12px 16px;">Dependent Tools</th>
                    <th style="padding: 12px 16px;">Status</th>
                    <th style="padding: 12px 16px; text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($services)): ?>
                    <tr>
                        <td colspan="8" style="padding: 40px; text-align: center; color: #64748b;">
                            No Python API microservices configured yet. Click "+ Add Python Service" above to register one.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($services as $srv): ?>
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 12px 16px;">
                                <div style="font-weight: 600; color: #0f172a;">
                                    <a href="/admin/page/favorite-web-tools-python-services?action=edit&id=<?php echo (int)$srv->id; ?>" style="color: inherit; text-decoration: none;">
                                        <?php echo htmlspecialchars($srv->name, ENT_QUOTES, 'UTF-8'); ?>
                                    </a>
                                </div>
                                <div style="font-size: 11px; color: #64748b; font-family: monospace; margin-top: 2px;">
                                    <?php echo htmlspecialchars($srv->slug ?: 'service-' . $srv->id, ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                            </td>
                            <td style="padding: 12px 16px; font-family: monospace; color: #2563eb;">
                                <?php echo htmlspecialchars($srv->base_url, ENT_QUOTES, 'UTF-8'); ?>
                            </td>
                            <td style="padding: 12px 16px; font-family: monospace; color: #475569;">
                                <?php echo htmlspecialchars($srv->default_endpoint_path ?? '/download/api', ENT_QUOTES, 'UTF-8'); ?>
                            </td>
                            <td style="padding: 12px 16px;">
                                <span style="background: <?php echo ($srv->http_method === 'GET') ? '#e0f2fe; color: #0369a1;' : '#fef3c7; color: #92400e;'; ?> padding: 2px 8px; border-radius: 4px; font-weight: 700; font-size: 11px;">
                                    <?php echo htmlspecialchars($srv->http_method ?: 'GET', ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </td>
                            <td style="padding: 12px 16px; color: #64748b;">
                                <?php echo (int)$srv->timeout; ?>s
                            </td>
                            <td style="padding: 12px 16px;">
                                <span style="background: #f1f5f9; padding: 2px 8px; border-radius: 10px; font-weight: 600; color: #334155;">
                                    <?php echo (int)($toolCounts[$srv->id] ?? 0); ?>
                                </span>
                            </td>
                            <td style="padding: 12px 16px;">
                                <form method="POST" action="/admin/page/favorite-web-tools-python-services" style="display: inline;">
                                    <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="id" value="<?php echo (int)$srv->id; ?>">
                                    <?php if ($srv->isActive()): ?>
                                        <button type="submit" title="Click to disable" style="background: #e6f4ea; color: #137333; font-weight: 600; padding: 3px 8px; border-radius: 10px; font-size: 11px; border: 1px solid #ceead6; cursor: pointer;">
                                            ACTIVE ●
                                        </button>
                                    <?php else: ?>
                                        <button type="submit" title="Click to activate" style="background: #f1f5f9; color: #64748b; font-weight: 600; padding: 3px 8px; border-radius: 10px; font-size: 11px; border: 1px solid #e2e8f0; cursor: pointer;">
                                            DISABLED ○
                                        </button>
                                    <?php endif; ?>
                                </form>
                            </td>
                            <td style="padding: 12px 16px; text-align: right; white-space: nowrap;">
                                <a href="/admin/page/favorite-web-tools-python-services?action=edit&id=<?php echo (int)$srv->id; ?>" style="color: #2563eb; text-decoration: none; font-weight: 600; margin-right: 10px;">Edit</a>

                                <form method="POST" action="/admin/page/favorite-web-tools-python-services" style="display: inline-block; margin-right: 10px;">
                                    <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="action" value="test">
                                    <input type="hidden" name="id" value="<?php echo (int)$srv->id; ?>">
                                    <button type="submit" style="background: #f0fdfa; border: 1px solid #ccfbf1; color: #0f766e; cursor: pointer; padding: 3px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">Test Connection</button>
                                </form>

                                <form method="POST" action="/admin/page/favorite-web-tools-python-services" style="display: inline-block;" onsubmit="return confirm('Delete this Python service? Dependent tools will prevent deletion.');">
                                    <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo (int)$srv->id; ?>">
                                    <button type="submit" style="background: none; border: none; color: #dc2626; cursor: pointer; padding: 0; font-size: 12px;">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
