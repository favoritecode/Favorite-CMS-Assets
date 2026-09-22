<?php
/**
 * Admin Tool Test Screen View
 *
 * Variables:
 * - $tool         : Tool
 * - $csrfToken    : string
 * - $flashSuccess : ?string
 * - $flashError   : ?string
 */

$lastResult = $_SESSION['last_test_result'] ?? null;
unset($_SESSION['last_test_result']);
?>
<div class="fwt-admin-wrap" style="max-width: 900px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
    <div style="margin-bottom: 20px;">
        <a href="/admin/page/favorite-web-tools" style="color: #2563eb; text-decoration: none; font-size: 13px;">← Back to Tools List</a>
        <h1 style="font-size: 22px; font-weight: 700; margin: 8px 0 4px 0; color: #1e293b;">
            Test Tool: <?php echo htmlspecialchars($tool->name, ENT_QUOTES, 'UTF-8'); ?>
        </h1>
        <div style="font-size: 13px; color: #64748b;">
            Engine: <code><?php echo htmlspecialchars($tool->engine, ENT_QUOTES, 'UTF-8'); ?></code> |
            Access Mode: <code><?php echo htmlspecialchars($tool->access_mode, ENT_QUOTES, 'UTF-8'); ?></code> |
            Status: <code><?php echo htmlspecialchars($tool->status, ENT_QUOTES, 'UTF-8'); ?></code>
        </div>
    </div>

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

    <!-- Test Runner Form -->
    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 24px; margin-bottom: 24px;">
        <form method="POST" action="/admin/page/favorite-web-tools">
            <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="action" value="execute_test">
            <input type="hidden" name="id" value="<?php echo (int)$tool->id; ?>">

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">
                    Test Input Payload (JSON or Raw Text)
                </label>
                <textarea name="test_inputs" rows="6" placeholder='e.g. {"text": "sample text", "html": "<div>test</div>"}'
                          style="width: 100%; padding: 10px 12px; font-size: 13px; font-family: monospace; border: 1px solid #cbd5e1; border-radius: 6px;"></textarea>
            </div>

            <div style="display: flex; gap: 10px; align-items: center;">
                <button type="submit" style="padding: 10px 24px; font-size: 14px; font-weight: 600; background: #0891b2; color: #fff; border: none; border-radius: 6px; cursor: pointer;">
                    Execute Test
                </button>
                <a href="/admin/page/favorite-web-tools?action=edit&id=<?php echo (int)$tool->id; ?>" style="padding: 10px 18px; font-size: 14px; color: #2563eb; text-decoration: none;">
                    Edit Tool
                </a>
            </div>
        </form>
    </div>

    <!-- Last Test Result -->
    <?php if ($lastResult !== null): ?>
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 24px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                <h3 style="font-size: 16px; font-weight: 700; margin: 0; color: #0f172a;">Last Execution Test Result</h3>
                <?php if (!empty($lastResult['success'])): ?>
                    <span style="background: #e6f4ea; color: #137333; font-weight: 700; padding: 3px 10px; border-radius: 12px; font-size: 12px;">PASSED</span>
                <?php else: ?>
                    <span style="background: #fdf2f2; color: #dc2626; font-weight: 700; padding: 3px 10px; border-radius: 12px; font-size: 12px;">FAILED</span>
                <?php endif; ?>
            </div>

            <pre style="background: #0f172a; color: #f8fafc; padding: 16px; border-radius: 8px; font-family: monospace; font-size: 13px; overflow-x: auto; max-height: 400px; margin: 0; white-space: pre-wrap;"><?php echo htmlspecialchars(json_encode($lastResult, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8'); ?></pre>
        </div>
    <?php endif; ?>
</div>

