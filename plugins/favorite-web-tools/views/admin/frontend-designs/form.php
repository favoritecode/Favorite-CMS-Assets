<?php
/**
 * Admin Frontend Design Create/Edit Form View
 *
 * Variables:
 * - $design       : FrontendDesign
 * - $isEdit       : bool
 * - $usageCount   : int
 * - $csrfToken    : string
 * - $flashSuccess : ?string
 * - $flashError   : ?string
 */
?>
<div class="fwt-admin-wrap" style="max-width: 1000px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
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

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div>
            <a href="/admin/web-tools/frontend-designs" style="color: #64748b; text-decoration: none; font-size: 13px;">
                ← Back to Frontend Designs
            </a>
            <h1 style="font-size: 22px; font-weight: 700; margin: 6px 0 0 0; color: #1e293b;">
                <?php echo $isEdit ? 'Edit Design: ' . htmlspecialchars($design->getName(), ENT_QUOTES, 'UTF-8') : 'Create Frontend Design'; ?>
            </h1>
        </div>

        <?php if ($isEdit): ?>
            <div>
                <a href="/admin/web-tools/frontend-designs?action=preview&id=<?php echo (int)$design->getId(); ?>"
                   style="display: inline-flex; align-items: center; gap: 6px; background: #0284c7; color: #fff; text-decoration: none; padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: 600;">
                    👁️ Test & Preview
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Security & Guidelines Notice -->
    <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 16px; margin-bottom: 24px; font-size: 13px; color: #166534;">
        <div style="font-weight: 700; margin-bottom: 4px;">🛡️ Safe Template Rules & Architecture</div>
        <div>
            • <strong>Automatic HTML Escaping:</strong> All standard variables (<code>{{title}}</code>, <code>{{filesize}}</code>) are strictly HTML escaped.<br>
            • <strong>Safe Typed URL Binding:</strong> Download URLs MUST use <code>{{download_url}}</code> or <code>{{thumbnail}}</code>; invalid schemes (<code>javascript:</code>, <code>data:</code>) are blocked.<br>
            • <strong>CSS Scoping:</strong> CSS is automatically scoped to <code>.fwt-design-container[data-design="<?php echo htmlspecialchars($design->getSlug() ?: 'slug', ENT_QUOTES, 'UTF-8'); ?>"]</code>. Global selectors like <code>body</code> or <code>*</code> are forbidden.<br>
            • <strong>JavaScript Policy:</strong> Untrusted JS is disabled by default. Prefer CSS-driven interactive UI.
        </div>
    </div>

    <form method="POST" action="/admin/web-tools/frontend-designs" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?php echo (int)$design->getId(); ?>">

        <!-- Section 1: Basic Information -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Design Name *</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($design->getName(), ENT_QUOTES, 'UTF-8'); ?>" required
                       style="width: 100%; padding: 9px 12px; font-size: 13px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;">
            </div>

            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Slug (Unique Key) *</label>
                <input type="text" name="slug" value="<?php echo htmlspecialchars($design->getSlug(), ENT_QUOTES, 'UTF-8'); ?>"
                       <?php echo ($isEdit && $design->isBuiltin()) ? 'readonly style="width: 100%; padding: 9px 12px; font-size: 13px; border: 1px solid #e2e8f0; background: #f8fafc; color: #64748b; border-radius: 6px; box-sizing: border-box;"' : 'style="width: 100%; padding: 9px 12px; font-size: 13px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;"'; ?>>
            </div>
        </div>

        <div style="margin-bottom: 16px;">
            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Description</label>
            <input type="text" name="description" value="<?php echo htmlspecialchars($design->getDescription(), ENT_QUOTES, 'UTF-8'); ?>"
                   style="width: 100%; padding: 9px 12px; font-size: 13px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;">
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-bottom: 24px;">
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Category</label>
                <input type="text" name="category" value="<?php echo htmlspecialchars($design->getCategory(), ENT_QUOTES, 'UTF-8'); ?>"
                       style="width: 100%; padding: 9px 12px; font-size: 13px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;">
            </div>

            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Version</label>
                <input type="text" name="version" value="<?php echo htmlspecialchars($design->getVersion(), ENT_QUOTES, 'UTF-8'); ?>"
                       style="width: 100%; padding: 9px 12px; font-size: 13px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;">
            </div>

            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Author</label>
                <input type="text" name="author" value="<?php echo htmlspecialchars($design->getAuthor(), ENT_QUOTES, 'UTF-8'); ?>"
                       style="width: 100%; padding: 9px 12px; font-size: 13px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;">
            </div>
        </div>

        <!-- Section 2: HTML Template -->
        <div style="margin-bottom: 20px;">
            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">
                HTML Template * (Logic-less Mustache Grammar)
            </label>
            <div style="font-size: 12px; color: #64748b; margin-bottom: 6px;">
                Available tokens: <code>{{title}}</code>, <code>{{thumbnail}}</code>, <code>{{duration}}</code>, <code>{{#formats}}...{{/formats}}</code>, <code>{{download_url}}</code>, <code>{{quality}}</code>, <code>{{ext}}</code>, <code>{{filesize_formatted}}</code>, <code>{{#if has_download_url}}...{{/if}}</code>
            </div>
            <textarea name="template_html" rows="14" required
                      style="width: 100%; font-family: Consolas, monospace; font-size: 13px; padding: 12px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; background: #0f172a; color: #f8fafc; line-height: 1.4;"><?php echo htmlspecialchars($design->getTemplateHtml(), ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>

        <!-- Section 3: Scoped CSS -->
        <div style="margin-bottom: 20px;">
            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">
                CSS Stylesheet (Automatically Scoped)
            </label>
            <div style="font-size: 12px; color: #64748b; margin-bottom: 6px;">
                Rules will be prefixed to isolate them from the parent theme. Global selectors (<code>body</code>, <code>*</code>, <code>html</code>) are forbidden.
            </div>
            <textarea name="css_content" rows="10"
                      style="width: 100%; font-family: Consolas, monospace; font-size: 13px; padding: 12px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; background: #1e293b; color: #f8fafc; line-height: 1.4;"><?php echo htmlspecialchars($design->getCssContent(), ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>

        <!-- Section 4: Optional JavaScript -->
        <div style="margin-bottom: 24px; background: #fffbeb; border: 1px solid #fef3c7; border-radius: 8px; padding: 16px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <label style="font-size: 13px; font-weight: 600; color: #92400e;">
                    Optional Script (Advanced / Untrusted)
                </label>
                <label style="display: flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600; color: #92400e; cursor: pointer;">
                    <input type="checkbox" name="js_enabled" value="1" <?php echo $design->isJsEnabled() ? 'checked' : ''; ?>>
                    Enable JavaScript Execution (Disabled by default)
                </label>
            </div>
            <div style="font-size: 12px; color: #b45309; margin-bottom: 8px;">
                ⚠️ Note: Imported JS runs in the user's browser. Do NOT enable unless you have audited the code. Tokens, cookies, and admin credentials are never passed to design runtime.
            </div>
            <textarea name="js_content" rows="6"
                      style="width: 100%; font-family: Consolas, monospace; font-size: 13px; padding: 12px; border: 1px solid #fcd34d; border-radius: 6px; box-sizing: border-box; background: #1e293b; color: #fde68a; line-height: 1.4;"><?php echo htmlspecialchars($design->getJsContent(), ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>

        <!-- Status -->
        <div style="display: flex; gap: 20px; align-items: center; margin-bottom: 24px; padding-top: 16px; border-top: 1px solid #e2e8f0;">
            <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; cursor: pointer;">
                <input type="checkbox" name="is_active" value="1" <?php echo $design->isActive() ? 'checked' : ''; ?>>
                Active (Available for tools to select)
            </label>
        </div>

        <!-- Submit Bar -->
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <a href="/admin/web-tools/frontend-designs" style="color: #64748b; text-decoration: none; font-size: 13px;">Cancel</a>
            <button type="submit" style="background: #2563eb; color: #ffffff; border: none; padding: 10px 24px; border-radius: 6px; font-weight: 600; font-size: 14px; cursor: pointer; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                <?php echo $isEdit ? 'Save Changes' : 'Create Design'; ?>
            </button>
        </div>
    </form>
</div>

