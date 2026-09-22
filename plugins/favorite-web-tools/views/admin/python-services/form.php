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
<div class="fwt-admin-wrap" style="max-width: 760px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
    <div style="margin-bottom: 20px;">
        <a href="/admin/page/favorite-web-tools-python-services" style="color: #2563eb; text-decoration: none; font-size: 13px;">← Back to Python Services</a>
        <h1 style="font-size: 22px; font-weight: 700; margin: 8px 0 4px 0; color: #1e293b;">
            <?php echo $isEdit ? 'Edit Python Service: ' . htmlspecialchars($service->name, ENT_QUOTES, 'UTF-8') : 'Register New Python Service'; ?>
        </h1>
        <p style="font-size: 13px; color: #64748b; margin: 0;">
            Configure external Python API microservices for use with the Universal Tool Builder.
        </p>
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

    <form method="POST" action="/admin/page/favorite-web-tools-python-services" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 24px; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
        <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?php echo (int)($service->id ?? 0); ?>">

        <!-- Row 1: Name & Slug -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #1e293b;">Service Name *</label>
                <input type="text" name="name" id="fwt-ps-name" value="<?php echo htmlspecialchars($service->name ?? '', ENT_QUOTES, 'UTF-8'); ?>" required
                       placeholder="e.g. Favorite Media Downloader API"
                       style="width: 100%; padding: 8px 12px; font-size: 13px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;">
            </div>

            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #1e293b;">Slug</label>
                <input type="text" name="slug" id="fwt-ps-slug" value="<?php echo htmlspecialchars($service->slug ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                       placeholder="favorite-media-downloader-api"
                       style="width: 100%; padding: 8px 12px; font-size: 13px; font-family: monospace; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;">
                <div style="font-size: 11px; color: #64748b; margin-top: 3px;">Unique identifier. Leave blank to auto-generate.</div>
            </div>
        </div>

        <!-- Description -->
        <div style="margin-bottom: 16px;">
            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #1e293b;">Description</label>
            <textarea name="description" rows="2" placeholder="e.g. External Python service providing multi-platform video/audio extraction."
                      style="width: 100%; padding: 8px 12px; font-size: 13px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;"><?php echo htmlspecialchars($service->description ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>

        <!-- Base URL -->
        <div style="margin-bottom: 16px;">
            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #1e293b;">Base URL *</label>
            <input type="url" name="base_url" value="<?php echo htmlspecialchars($service->base_url ?? '', ENT_QUOTES, 'UTF-8'); ?>" required
                   placeholder="https://server.favoriteweb.net"
                   style="width: 100%; padding: 8px 12px; font-size: 13px; font-family: monospace; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;">
            <div style="font-size: 11px; color: #64748b; margin-top: 4px;">
                HTTPS protocol required for external production services. Localhost, loopback, private IPs, and cloud metadata addresses are forbidden.
            </div>
        </div>

        <!-- Row 2: Default Endpoint Path & HTTP Method -->
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px; margin-bottom: 16px;">
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #1e293b;">Default Endpoint Path</label>
                <input type="text" name="default_endpoint_path" value="<?php echo htmlspecialchars($service->default_endpoint_path ?? '/download/api', ENT_QUOTES, 'UTF-8'); ?>"
                       placeholder="/download/api"
                       style="width: 100%; padding: 8px 12px; font-size: 13px; font-family: monospace; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;">
                <div style="font-size: 11px; color: #64748b; margin-top: 3px;">e.g. <code>/download/api</code></div>
            </div>

            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #1e293b;">HTTP Method</label>
                <select name="http_method" style="width: 100%; padding: 8px 12px; font-size: 13px; border: 1px solid #cbd5e1; border-radius: 6px; background: #fff; box-sizing: border-box;">
                    <option value="GET" <?php echo (strtoupper($service->http_method ?? 'GET') === 'GET') ? 'selected' : ''; ?>>GET (Query params)</option>
                    <option value="POST" <?php echo (strtoupper($service->http_method ?? 'GET') === 'POST') ? 'selected' : ''; ?>>POST (JSON Body)</option>
                </select>
            </div>
        </div>

        <!-- Row 3: Authentication -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #1e293b;">Authentication Mode</label>
                <select name="auth_type" style="width: 100%; padding: 8px 12px; font-size: 13px; border: 1px solid #cbd5e1; border-radius: 6px; background: #fff; box-sizing: border-box;">
                    <option value="none" <?php echo (($service->auth_type ?? 'none') === 'none') ? 'selected' : ''; ?>>None (Public / IP-gated)</option>
                    <option value="bearer" <?php echo (($service->auth_type ?? '') === 'bearer') ? 'selected' : ''; ?>>Bearer Token</option>
                    <option value="api_key" <?php echo (($service->auth_type ?? '') === 'api_key') ? 'selected' : ''; ?>>X-API-Key Header</option>
                </select>
            </div>

            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #1e293b;">API Key / Secret Token (Optional)</label>
                <input type="password" name="api_key" value="" placeholder="<?php echo !empty($service->api_key) ? '●●● Leave blank to keep existing key ●●●' : 'Enter API key if required'; ?>"
                       style="width: 100%; padding: 8px 12px; font-size: 13px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;">
            </div>
        </div>

        <!-- Row 4: Timeout & Status -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #1e293b;">Timeout (seconds)</label>
                <input type="number" name="timeout" value="<?php echo (int)($service->timeout ?? 30); ?>" min="1" max="120"
                       style="width: 100%; padding: 8px 12px; font-size: 13px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;">
                <div style="font-size: 11px; color: #64748b; margin-top: 3px;">Safe reasonable default is 30s.</div>
            </div>

            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #1e293b;">Status</label>
                <select name="status" style="width: 100%; padding: 8px 12px; font-size: 13px; border: 1px solid #cbd5e1; border-radius: 6px; background: #fff; box-sizing: border-box;">
                    <option value="active" <?php echo ($service->isActive()) ? 'selected' : ''; ?>>Active (Selectable in Tool Builder)</option>
                    <option value="disabled" <?php echo (!$service->isActive()) ? 'selected' : ''; ?>>Disabled</option>
                </select>
            </div>
        </div>

        <div style="display: flex; gap: 12px; align-items: center; border-top: 1px solid #f1f5f9; padding-top: 18px;">
            <button type="submit" style="padding: 10px 24px; font-size: 14px; font-weight: 600; background: #2563eb; color: #fff; border: none; border-radius: 6px; cursor: pointer;">
                <?php echo $isEdit ? 'Save Service' : 'Register Service'; ?>
            </button>
            <a href="/admin/page/favorite-web-tools-python-services" style="padding: 10px 18px; font-size: 14px; color: #475569; text-decoration: none;">Cancel</a>
        </div>
    </form>

    <?php if ($isEdit): ?>
        <!-- Test Connection Section -->
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
            <h3 style="font-size: 15px; font-weight: 700; margin: 0 0 8px 0; color: #1e293b;">Test Service Connectivity</h3>
            <p style="font-size: 12px; color: #64748b; margin: 0 0 14px 0;">
                Sends a test request using the configured HTTP method and credentials to verify connectivity and reachability.
            </p>
            <form method="POST" action="/admin/page/favorite-web-tools-python-services" style="display: flex; gap: 10px; align-items: center;">
                <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="action" value="test">
                <input type="hidden" name="id" value="<?php echo (int)$service->id; ?>">
                <input type="hidden" name="redirect" value="edit">
                <input type="text" name="test_endpoint" value="<?php echo htmlspecialchars($service->default_endpoint_path ?? '/download/api', ENT_QUOTES, 'UTF-8'); ?>"
                       placeholder="/download/api"
                       style="flex: 1; padding: 8px 12px; font-size: 13px; font-family: monospace; border: 1px solid #cbd5e1; border-radius: 6px;">
                <button type="submit" style="padding: 8px 18px; font-size: 13px; font-weight: 600; background: #0f766e; color: #fff; border: none; border-radius: 6px; cursor: pointer;">
                    Test Connection
                </button>
            </form>
        </div>
    <?php endif; ?>
</div>
