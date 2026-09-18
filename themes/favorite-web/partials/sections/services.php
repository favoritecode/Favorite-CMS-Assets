<?php
/**
 * Services Section — professional service offerings with selectable icons and action links.
 */
$services = is_array($services ?? null) ? $services : fw_official_services();
if ($services === []) {
    return;
}

$srvCfg = function_exists('fw_get_section_config') ? fw_get_section_config('services') : [];
$servicesBadge = (string)($srvCfg['badge'] ?? (function_exists('get_theme_mod') ? get_theme_mod('services_badge', 'Professional expertise') : 'Professional expertise'));
$servicesTitle = (string)($srvCfg['title'] ?? (function_exists('get_theme_mod') ? get_theme_mod('services_title', 'Services built around your goals') : 'Services built around your goals'));
$servicesDesc  = (string)($srvCfg['desc'] ?? (function_exists('get_theme_mod') ? get_theme_mod('services_desc', 'From launching a website to improving daily digital work, we provide focused help with clear outcomes.') : 'From launching a website to improving daily digital work, we provide focused help with clear outcomes.'));

$icons = [
    'download' => '<path d="M12 3v11m0 0 4-4m-4 4-4-4M5 17v3h14v-3"/>',
    'growth'   => '<path d="M4 18 10 12l4 4 6-8M15 8h5v5"/>',
    'browser'  => '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 9h18M7 6.5h.01M10 6.5h.01"/>',
    'design'   => '<path d="m4 20 4.5-1 10-10a2.1 2.1 0 0 0-3-3l-10 10L4 20ZM13.5 8.5l3 3"/>',
    'document' => '<path d="M6 3h9l3 3v15H6zM14 3v5h5M9 12h6M9 16h6"/>',
    'support'  => '<path d="M4 13v-2a8 8 0 0 1 16 0v2M4 13h3v6H5a1 1 0 0 1-1-1v-5Zm16 0h-3v6h2a1 1 0 0 0 1-1v-5ZM17 19c-1 2-3 2-5 2"/>',
];
?>
<section class="fw-section fw-services" id="favorite-web-services" aria-labelledby="fw-services-title">
    <header class="fw-section__heading">
        <div>
            <p class="fw-eyebrow"><span></span> <?php echo fw_e($servicesBadge); ?></p>
            <h2 id="fw-services-title"><?php echo fw_e($servicesTitle); ?></h2>
        </div>
        <p><?php echo fw_e($servicesDesc); ?></p>
    </header>
    <div class="fw-services__grid">
        <?php foreach ($services as $service): ?>
            <?php $icon = (string)($service['icon'] ?? 'support'); ?>
            <article class="fw-service-card">
                <span class="fw-service-card__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><?php echo $icons[$icon] ?? $icons['support']; ?></svg>
                </span>
                <h3><?php echo fw_e($service['title'] ?? ''); ?></h3>
                <p><?php echo fw_e($service['description'] ?? ''); ?></p>
                <a href="<?php echo fw_e(fw_url((string)($service['url'] ?? '/store?product_type=service'))); ?>">View service <span aria-hidden="true">&#8594;</span></a>
            </article>
        <?php endforeach; ?>
    </div>
</section>
