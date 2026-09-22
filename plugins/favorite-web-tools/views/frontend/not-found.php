<?php
/**
 * 404 Tool / Category Not Found View
 *
 * Variables:
 * - $message : string
 */
?>
<div class="fwt-not-found-container" style="max-width: 800px; margin: 60px auto; text-align: center; padding: 40px 20px;">
    <div style="font-size: 64px; margin-bottom: 16px;">🔍</div>
    <h1 style="font-size: 32px; font-weight: 700; margin-bottom: 16px;">Item Not Found</h1>
    <p style="font-size: 16px; color: var(--text-muted, #64748b); max-width: 500px; margin: 0 auto 30px;">
        <?php echo htmlspecialchars($message ?? 'The requested tool or category is unavailable or does not exist.', ENT_QUOTES, 'UTF-8'); ?>
    </p>
    <a href="/tools" class="fwt-btn fwt-btn-primary" style="display: inline-flex; align-items: center; gap: 8px; text-decoration: none; padding: 10px 24px; border-radius: 8px; font-weight: 600; background: var(--primary, #2563eb); color: #fff;">
        ← Back to All Tools
    </a>
</div>

