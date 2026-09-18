<?php
/**
 * Packages Section — turnkey solution packages supplied by Favorite Digital.
 */
$pkgCfg = function_exists('fw_get_section_config') ? fw_get_section_config('packages') : [];
$limit = max(1, min(12, (int)($pkgCfg['limit'] ?? 3)));
$packages = is_array($packages ?? null) ? $packages : fw_packages($limit);
if (count($packages) > $limit) {
    $packages = array_slice($packages, 0, $limit);
}
if ($packages === []) {
    return;
}

$badge      = (string)($pkgCfg['badge'] ?? 'Tailored Solutions');
$title      = (string)($pkgCfg['title'] ?? 'Packages built for your success');
$actionText = (string)($pkgCfg['action_text'] ?? 'Browse all packages');
$actionUrl  = (string)($pkgCfg['action_url'] ?? '/store?product_type=package');
?>
<section class="fw-section fw-packages" id="favorite-web-packages" aria-labelledby="fw-packages-title">
    <header class="fw-section__heading fw-section__heading--action">
        <div>
            <p class="fw-eyebrow"><span></span> <?php echo fw_e($badge); ?></p>
            <h2 id="fw-packages-title"><?php echo fw_e($title); ?></h2>
        </div>
        <?php if ($actionText !== ''): ?>
            <a class="fw-text-link" href="<?php echo fw_e(fw_url($actionUrl)); ?>"><?php echo fw_e($actionText); ?> <span aria-hidden="true">&#8594;</span></a>
        <?php endif; ?>
    </header>

    <div class="fw-packages__grid">
        <?php foreach ($packages as $package): ?>
            <?php fw_partial('pricing-card', ['package' => $package]); ?>
        <?php endforeach; ?>
    </div>
</section>
