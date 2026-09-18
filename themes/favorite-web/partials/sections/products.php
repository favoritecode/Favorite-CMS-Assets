<?php
/**
 * Digital Products Section — showcase published digital items supplied by Favorite Digital.
 */
$prodCfg = function_exists('fw_get_section_config') ? fw_get_section_config('products') : [];
$limit = max(1, min(12, (int)($prodCfg['limit'] ?? 6)));
$products = is_array($products ?? null) ? $products : fw_store_products('digital', $limit);
if (count($products) > $limit) {
    $products = array_slice($products, 0, $limit);
}
if ($products === []) {
    return;
}

$badge      = (string)($prodCfg['badge'] ?? 'Ready when you are');
$title      = (string)($prodCfg['title'] ?? 'Digital products for smarter work');
$actionText = (string)($prodCfg['action_text'] ?? 'Browse all products');
$actionUrl  = (string)($prodCfg['action_url'] ?? '/store?product_type=digital');
$columns    = max(2, min(4, (int)($prodCfg['columns'] ?? 3)));
?>
<section class="fw-section fw-products" aria-labelledby="fw-products-title">
    <header class="fw-section__heading fw-section__heading--action">
        <div>
            <p class="fw-eyebrow"><span></span> <?php echo fw_e($badge); ?></p>
            <h2 id="fw-products-title"><?php echo fw_e($title); ?></h2>
        </div>
        <?php if ($actionText !== ''): ?>
            <a class="fw-text-link" href="<?php echo fw_e(fw_url($actionUrl)); ?>"><?php echo fw_e($actionText); ?> <span aria-hidden="true">&#8594;</span></a>
        <?php endif; ?>
    </header>

    <div class="fw-product-grid fw-product-grid--cols-<?php echo $columns; ?>">
        <?php foreach ($products as $product): ?>
            <?php fw_partial('product-card', ['product' => $product]); ?>
        <?php endforeach; ?>
    </div>
</section>
