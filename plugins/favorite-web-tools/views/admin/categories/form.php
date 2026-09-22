<?php
/**
 * Admin Category Create/Edit Form View
 *
 * Variables:
 * - $category     : ToolCategory
 * - $csrfToken    : string
 * - $isEdit       : bool
 * - $flashSuccess : ?string
 * - $flashError   : ?string
 */
?>
<div class="fwt-admin-wrap" style="max-width: 700px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
    <div style="margin-bottom: 20px;">
        <a href="/admin/page/favorite-web-tools-categories" style="color: #2563eb; text-decoration: none; font-size: 13px;">← Back to Categories</a>
        <h1 style="font-size: 22px; font-weight: 700; margin: 8px 0 4px 0; color: #1e293b;">
            <?php echo $isEdit ? 'Edit Category: ' . htmlspecialchars($category->name, ENT_QUOTES, 'UTF-8') : 'Create New Category'; ?>
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

    <form method="POST" action="/admin/page/favorite-web-tools-categories" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 24px;">
        <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?php echo (int)($category->id ?? 0); ?>">

        <div style="margin-bottom: 16px;">
            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Category Name *</label>
            <input type="text" name="name" value="<?php echo htmlspecialchars($category->name ?? '', ENT_QUOTES, 'UTF-8'); ?>" required
                   style="width: 100%; padding: 8px 12px; font-size: 13px; border: 1px solid #cbd5e1; border-radius: 6px;">
        </div>

        <div style="margin-bottom: 16px;">
            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Slug</label>
            <input type="text" name="slug" value="<?php echo htmlspecialchars($category->slug ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="auto-generated if empty"
                   style="width: 100%; padding: 8px 12px; font-size: 13px; border: 1px solid #cbd5e1; border-radius: 6px;">
        </div>

        <div style="margin-bottom: 16px;">
            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Description</label>
            <textarea name="description" rows="3" style="width: 100%; padding: 8px 12px; font-size: 13px; border: 1px solid #cbd5e1; border-radius: 6px;"><?php echo htmlspecialchars($category->description ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Icon (Emoji)</label>
                <input type="text" name="icon" value="<?php echo htmlspecialchars($category->icon ?? '📁', ENT_QUOTES, 'UTF-8'); ?>"
                       style="width: 100%; padding: 8px 12px; font-size: 13px; border: 1px solid #cbd5e1; border-radius: 6px;">
            </div>

            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Display Order</label>
                <input type="number" name="display_order" value="<?php echo (int)($category->display_order ?? 0); ?>"
                       style="width: 100%; padding: 8px 12px; font-size: 13px; border: 1px solid #cbd5e1; border-radius: 6px;">
            </div>
        </div>

        <div style="margin-bottom: 24px;">
            <label style="display: flex; gap: 8px; align-items: center; font-size: 13px; cursor: pointer;">
                <input type="checkbox" name="is_active" value="1" <?php echo !empty($category->is_active) ? 'checked' : ''; ?> style="width: 16px; height: 16px;">
                <span>Active (visible in frontend tool discovery)</span>
            </label>
        </div>

        <div style="display: flex; gap: 12px; align-items: center;">
            <button type="submit" style="padding: 10px 24px; font-size: 14px; font-weight: 600; background: #2563eb; color: #fff; border: none; border-radius: 6px; cursor: pointer;">
                <?php echo $isEdit ? 'Save Changes' : 'Create Category'; ?>
            </button>
            <a href="/admin/page/favorite-web-tools-categories" style="padding: 10px 18px; font-size: 14px; color: #475569; text-decoration: none;">Cancel</a>
        </div>
    </form>
</div>

