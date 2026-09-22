<?php
/**
 * Frontend Access Gate View
 *
 * Variables:
 * - $mode : 'LOGIN_REQUIRED' | 'MEMBERSHIP_REQUIRED'
 * - $tool : Tool
 */
?>
<div class="fwt-access-gate" style="border: 1px solid var(--border-color, #e2e8f0); border-radius: 12px; padding: 32px 24px; text-align: center; background: var(--card-bg, #f8fafc); margin: 24px 0;">
    <?php if ($mode === 'LOGIN_REQUIRED'): ?>
        <div style="font-size: 40px; margin-bottom: 12px;">🔒</div>
        <h3 style="font-size: 20px; font-weight: 700; margin-bottom: 8px;">Login Required</h3>
        <p style="color: var(--text-muted, #64748b); font-size: 15px; max-width: 480px; margin: 0 auto 20px;">
            You must be logged in to use <strong><?php echo htmlspecialchars($tool->name, ENT_QUOTES, 'UTF-8'); ?></strong>.
        </p>
        <div style="display: flex; gap: 12px; justify-content: center; align-items: center; flex-wrap: wrap;">
            <a href="/login?redirect=<?php echo urlencode('/tools/' . $tool->slug); ?>" class="fwt-btn fwt-btn-primary" style="display: inline-flex; align-items: center; gap: 6px; padding: 10px 22px; border-radius: 8px; font-weight: 600; text-decoration: none; background: var(--primary, #2563eb); color: #fff;">
                Log In to Continue
            </a>
            <a href="/register?redirect=<?php echo urlencode('/tools/' . $tool->slug); ?>" class="fwt-btn fwt-btn-secondary" style="display: inline-flex; align-items: center; gap: 6px; padding: 10px 22px; border-radius: 8px; font-weight: 600; text-decoration: none; border: 1px solid var(--border-color, #cbd5e1); color: inherit;">
                Create Account
            </a>
        </div>
    <?php else: ?>
        <div style="font-size: 40px; margin-bottom: 12px;">⭐</div>
        <h3 style="font-size: 20px; font-weight: 700; margin-bottom: 8px;">Membership Required</h3>
        <p style="color: var(--text-muted, #64748b); font-size: 15px; max-width: 480px; margin: 0 auto 20px;">
            An active membership is required to access <strong><?php echo htmlspecialchars($tool->name, ENT_QUOTES, 'UTF-8'); ?></strong>. Members enjoy unlimited access to all tools.
        </p>
        <div style="display: flex; gap: 12px; justify-content: center; align-items: center; flex-wrap: wrap;">
            <?php if (empty($_SESSION['auth_user_id'])): ?>
                <a href="/login?redirect=<?php echo urlencode('/tools/' . $tool->slug); ?>" class="fwt-btn fwt-btn-primary" style="display: inline-flex; align-items: center; gap: 6px; padding: 10px 22px; border-radius: 8px; font-weight: 600; text-decoration: none; background: var(--primary, #2563eb); color: #fff;">
                    Log In with Account
                </a>
            <?php endif; ?>
            <a href="/account/membership" class="fwt-btn fwt-btn-primary" style="display: inline-flex; align-items: center; gap: 6px; padding: 10px 22px; border-radius: 8px; font-weight: 600; text-decoration: none; background: #eab308; color: #000;">
                View Membership Plans
            </a>
        </div>
    <?php endif; ?>
</div>

