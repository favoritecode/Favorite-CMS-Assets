<?php
/**
 * Membership Section — member-only benefits showcase and pricing cards from Favorite Digital.
 */
$memCfg = function_exists('fw_get_section_config') ? fw_get_section_config('memberships') : [];
$limit = max(1, min(12, (int)($memCfg['limit'] ?? 3)));
$products = is_array($products ?? null) ? $products : fw_store_products('membership', $limit);
if (count($products) > $limit) {
    $products = array_slice($products, 0, $limit);
}
if ($products === []) {
    return;
}

$badge       = (string)($memCfg['badge'] ?? 'Membership Benefits');
$title       = (string)($memCfg['title'] ?? 'Exclusive access for members');
$description = (string)($memCfg['description'] ?? 'Unlock premium tutorials, member-only tools, and priority updates with an active membership plan.');
$ctaText     = (string)($memCfg['cta_text'] ?? 'Explore Memberships');
$ctaUrl      = (string)($memCfg['cta_url'] ?? '/store?product_type=membership');

$defaultArt  = function_exists('theme_asset_url')
    ? theme_asset_url('assets/images/illustrations/digital-products.svg')
    : fw_url('/themes/favorite-web/assets/images/illustrations/digital-products.svg');
$customArt   = trim((string)($memCfg['image_url'] ?? ''));
$membershipArt = $customArt !== '' ? fw_url($customArt) : $defaultArt;
?>
<section class="fw-membership" aria-labelledby="fw-membership-title">
    <div class="fw-membership__intro">
        <p class="fw-eyebrow fw-eyebrow--light"><span></span> <?php echo fw_e($badge); ?></p>
        <h2 id="fw-membership-title"><?php echo fw_e($title); ?></h2>
        <p><?php echo fw_e($description); ?></p>
        <?php if ($ctaText !== ''): ?>
            <div class="fw-membership__actions">
                <a class="button button--light fw-button" href="<?php echo fw_e(fw_url($ctaUrl)); ?>"><?php echo fw_e($ctaText); ?></a>
            </div>
        <?php endif; ?>
        <img src="<?php echo fw_e($membershipArt); ?>" alt="<?php echo fw_e($title); ?>" width="380" height="280" loading="lazy" decoding="async">
    </div>
    <div class="fw-membership__plans">
        <?php foreach ($products as $product): ?>
            <?php fw_partial('product-card', ['product' => $product, 'compact' => true]); ?>
        <?php endforeach; ?>
    </div>
</section>
