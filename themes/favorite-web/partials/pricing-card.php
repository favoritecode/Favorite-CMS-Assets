<?php
$package = is_array($package ?? null) ? $package : [];
$title = trim((string)($package['title'] ?? 'Solution Package'));
$description = trim((string)($package['description'] ?? ''));
$pricing = is_array($package['pricing'] ?? null) ? $package['pricing'] : [];
$price = (string)($pricing['formatted_final_price'] ?? 'View details');
$originalPrice = !empty($pricing['has_discount']) ? (string)($pricing['formatted_original_price'] ?? '') : '';
$slug = trim((string)($package['slug'] ?? ''));
$url = $slug !== '' ? '/store/' . rawurlencode($slug) : '/store?product_type=package';
$isPopular = !empty($package['is_popular']) || !empty($package['is_featured']);
$features = is_array($package['features'] ?? null) ? $package['features'] : [];
?>
<article class="fw-pricing-card<?php echo $isPopular ? ' fw-pricing-card--popular' : ''; ?>">
    <?php if ($isPopular): ?>
        <span class="fw-pricing-card__badge">Most Popular</span>
    <?php endif; ?>

    <div class="fw-pricing-card__header">
        <h3 class="fw-pricing-card__title"><?php echo fw_e($title); ?></h3>
        <?php if ($description !== ''): ?>
            <p class="fw-pricing-card__desc"><?php echo fw_e($description); ?></p>
        <?php endif; ?>
    </div>

    <div class="fw-pricing-card__price-box">
        <span class="fw-pricing-card__price"><?php echo fw_e($price); ?></span>
        <?php if ($originalPrice !== ''): ?>
            <del class="fw-pricing-card__original-price"><?php echo fw_e($originalPrice); ?></del>
        <?php endif; ?>
    </div>

    <?php if ($features !== []): ?>
        <ul class="fw-pricing-card__features" aria-label="Package features">
            <?php foreach ($features as $feature): ?>
                <li>
                    <svg class="icon icon-check" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                    <span><?php echo fw_e((string)$feature); ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <div class="fw-pricing-card__actions">
        <a class="button <?php echo $isPopular ? 'button--primary' : 'button--light'; ?> fw-button fw-button--full" href="<?php echo fw_e(fw_url($url)); ?>">
            Get Started
        </a>
    </div>
</article>

