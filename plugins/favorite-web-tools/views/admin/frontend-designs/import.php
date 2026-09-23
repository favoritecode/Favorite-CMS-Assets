<?php
/**
 * Admin Frontend Design Import View
 *
 * Variables:
 * - $csrfToken  : string
 * - $flashError : ?string
 */
?>
<div class="fwt-admin-wrap" style="max-width: 800px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
    <?php if (!empty($flashError)): ?>
        <div style="background: #fdf2f2; border-left: 4px solid #dc3545; padding: 12px 16px; margin-bottom: 20px; border-radius: 4px; color: #721c24; font-size: 14px;">
            <strong>Error:</strong> <?php echo htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <div style="margin-bottom: 20px;">
        <a href="/admin/page/favorite-web-tools-frontend-designs" style="color: #64748b; text-decoration: none; font-size: 13px;">
            ← Back to Frontend Designs
        </a>
        <h1 style="font-size: 22px; font-weight: 700; margin: 6px 0 0 0; color: #1e293b;">
            Import Design Package (.ZIP)
        </h1>
    </div>

    <!-- Package Specification Card -->
    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 20px; margin-bottom: 24px; font-size: 13px; color: #334155; line-height: 1.6;">
        <div style="font-weight: 700; color: #0f172a; margin-bottom: 8px;">📦 Package Structure Requirements:</div>
        <div>Your ZIP archive must contain:</div>
        <ul style="margin: 8px 0 12px 20px; padding: 0;">
            <li><code>design.json</code> — Required manifest (name, slug, version, category, description)</li>
            <li><code>template.html</code> — Required HTML template markup</li>
            <li><code>styles.css</code> — Optional CSS (strictly scoped, no global tags)</li>
            <li><code>script.js</code> — Optional JS (disabled by default)</li>
        </ul>
        <div style="color: #64748b; font-size: 12px;">
            🔒 Security Notice: Executable files (.php, .exe, .sh, .py, etc.) and path-traversal entries are rejected during import.
        </div>
    </div>

    <form method="POST" action="/admin/page/favorite-web-tools-frontend-designs" enctype="multipart/form-data"
          style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
        <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="action" value="import">

        <div style="margin-bottom: 20px;">
            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 8px;">Select Design ZIP Package *</label>
            <input type="file" name="package" accept=".zip" required
                   style="width: 100%; padding: 10px; font-size: 13px; border: 2px dashed #cbd5e1; border-radius: 8px; background: #f8fafc; cursor: pointer; box-sizing: border-box;">
        </div>

        <div style="margin-bottom: 24px;">
            <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; cursor: pointer; color: #334155;">
                <input type="checkbox" name="overwrite" value="1">
                Overwrite existing design if slug already exists
            </label>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center;">
            <a href="/admin/page/favorite-web-tools-frontend-designs" style="color: #64748b; text-decoration: none; font-size: 13px;">Cancel</a>
            <button type="submit" style="background: #0284c7; color: #ffffff; border: none; padding: 10px 24px; border-radius: 6px; font-weight: 600; font-size: 14px; cursor: pointer;">
                Upload & Import Design
            </button>
        </div>
    </form>
</div>

