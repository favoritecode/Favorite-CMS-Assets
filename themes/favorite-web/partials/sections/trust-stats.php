<?php
$stats = is_array($stats ?? null) ? $stats : fw_trust_stats();
if ($stats === []) {
    return;
}
?>
<section class="fw-section fw-trust-stats" aria-label="Trust & Performance Metrics">
    <div class="fw-trust-stats__grid">
        <?php foreach ($stats as $stat): ?>
            <div class="fw-stat-card">
                <span class="fw-stat-card__number"><?php echo fw_e($stat['number'] ?? ''); ?></span>
                <span class="fw-stat-card__label"><?php echo fw_e($stat['label'] ?? ''); ?></span>
                <?php if (!empty($stat['desc'])): ?>
                    <span class="fw-stat-card__desc"><?php echo fw_e($stat['desc']); ?></span>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</section>

