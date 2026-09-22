<?php
/**
 * Admin Python Service Create/Edit Form View
 *
 * Variables:
 * - $service      : PythonService
 * - $csrfToken    : string
 * - $isEdit       : bool
 * - $flashSuccess : ?string
 * - $flashError   : ?string
 */
?>
<div class="fwt-admin-wrap" style="max-width: 700px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
    <div style="margin-bottom: 20px;">
        <a href="/admin/page/favorite-web-tools-python-services" style="color: #2563eb; text-decoration: none; font-size: 13px;">← Back to Python Services</a>
        <h1 style="font-size: 22px; font-weight: 700; margin: 8px 0 4px 0; color: #1e293b;">
            <?php echo $isEdit ? 'Edit Python Service: ' . htmlspecialchars($service->name, ENT_QUOTES, 'UTF-8') : 'Configure New Python Service'; ?>
        </h1>
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

    <form method="POST" action="/admin/page/favorite-web-tools-python-services" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 24px; margin-bottom: 24px;">
        <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?php echo (int)($service->id ?? 0); ?>">

        <div style="margin-bottom: 16px;">
            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Service Name *</label>
            <input type="text" name="name" value="<?php echo htmlspecialchars($service->name ?? '', ENT_QUOTES, 'UTF-8'); ?>" required
                   placeholder="e.g. Core AI Microservice"
                   style="width: 100%; padding: 8px 12px; font-size: 13px; border: 1px solid #cbd5e1; border-radius: 6px;">
        </div>

        <div style="margin-bottom: 16px;">
            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Base URL *</label>
            <input type="url" name="base_url" value="<?php echo htmlspecialchars($service->base_url ?? '', ENT_QUOTES, 'UTF-8'); ?>" required
                   placeholder="http://127.0.0.1:8000"
                   style="width: 100%; padding: 8px 12px; font-size: 13px; font-family: monospace; border: 1px solid #cbd5e1; border-radius: 6px;">
            <div style="font-size: 12px; color: #64748b; margin-top: 4px;">Private API base URL. Protected from public exposure.</div>
        </div>

        <div style="margin-bottom: 16px;">
            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">API Key / Secret Token (Optional)</label>
            <input type="password" name="api_key" value="" placeholder="<?php echo !empty($service->api_key) ? '●●● Leave blank to keep existing key ●●●' : 'Enter API key if required'; ?>"
                   style="width: 100%; padding: 8px 12px; font-size: 13px; border: 1px solid #cbd5e1; border-radius: 6px;">
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Timeout (seconds)</label>
                <input type="number" name="timeout" value="<?php echo (int)($service->timeout ?? 15); ?>" min="1" max="120"
                       style="width: 100%; padding: 8px 12px; font-size: 13px; border: 1px solid #cbd5e1; border-radius: 6px;">
            </div>

            <div style="display: flex; align-items: flex-end; padding-bottom: 8px;">
                <label style="display: flex; gap: 8px; align-items: center; font-size: 13px; cursor: pointer;">
                    <input type="checkbox" name="is_active" value="1" <?php echo !empty($service->is_active) ? 'checked' : ''; ?> style="width: 16px; height: 16px;">
                    <span>Active & Available</span>
                </label>
            </div>
        </div>

        <div style="display: flex; gap: 12px; align-items: center;">
            <button type="submit" style="padding: 10px 24px; font-size: 14px; font-weight: 600; background: #2563eb; color: #fff; border: none; border-radius: 6px; cursor: pointer;">
                <?php echo $isEdit ? 'Save Service' : 'Create Service'; ?>
            </button>
            <a href="/admin/page/favorite-web-tools-python-services" style="padding: 10px 18px; font-size: 14px; color: #475569; text-decoration: none;">Cancel</a>
        </div>
    </form>

    <?php if ($isEdit): ?>
        <!-- Test Connection Section -->
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 24px;">
            <h3 style="font-size: 15px; font-weight: 700; margin: 0 0 12px 0; color: #1e293b;">Test Endpoint Connectivity</h3>
            <form method="POST" action="/admin/page/favorite-web-tools-python-services" style="display: flex; gap: 10px; align-items: center;">
                <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="action" value="test">
                <input type="hidden" name="id" value="<?php echo (int)$service->id; ?>">
                <input type="text" name="test_endpoint" value="/health" placeholder="/health" style="flex: 1; padding: 8px 12px; font-size: 13px; font-family: monospace; border: 1px solid #cbd5e1; border-radius: 6px;">
                <button type="submit" style="padding: 8px 18px; font-size: 13px; font-weight: 600; background: #0891b2; color: #fff; border: none; border-radius: 6px; cursor: pointer;">
                    Test Health
                </button>
            </form>
        </div>
    <?php endif; ?>
</div>

