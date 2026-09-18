<?php
$product = is_array($product ?? null) ? $product : [];
$compact = !empty($compact);
$title = trim((string)($product['title'] ?? 'Digital product'));
$slug = trim((string)($product['slug'] ?? ''));
$description = trim((string)($product['description'] ?? ''));
$image = fw_product_image($product);
$pricing = is_array($product['pricing'] ?? null) ? $product['pricing'] : [];
$price = (string)($pricing['formatted_final_price'] ?? 'View details');
$originalPrice = !empty($pricing['has_discount']) ? (string)($pricing['formatted_original_price'] ?? '') : '';
$type = (string)($product['product_type'] ?? 'digital');
$plan = is_array($product['plan_summary'] ?? null) ? $product['plan_summary'] : [];
$url = $slug !== '' ? '/store/' . rawurlencode($slug) : '/store?product_type=' . rawurlencode($type);
?>
<article class="fw-product-card<?php echo $compact ? ' fw-product-card--compact' : ''; ?>">
    <?php if (!$compact): ?>
        <a class="fw-product-card__media" href="<?php echo fw_e(fw_url($url)); ?>" tabindex="-1" aria-hidden="true">
            <?php if ($image !== ''): ?>
                <img src="<?php echo fw_e(fw_url($image)); ?>" alt="" loading="lazy" decoding="async">
            <?php else: ?>
                <span><?php echo fw_e(mb_strtoupper(mb_substr($title, 0, 1, 'UTF-8'), 'UTF-8')); ?></span>
            <?php endif; ?>
        </a>
    <?php endif; ?>
    <div class="fw-product-card__body">
        <span class="fw-product-card__type"><?php echo fw_e($type === 'membership' ? 'Membership' : 'Digital product'); ?></span>
        <h3><a href="<?php echo fw_e(fw_url($url)); ?>"><?php echo fw_e($title); ?></a></h3>
        <?php if ($description !== ''): ?>
            <p><?php echo fw_e(mb_strlen($description, 'UTF-8') > 115 ? rtrim(mb_substr($description, 0, 115, 'UTF-8')) . '…' : $description); ?></p>
        <?php endif; ?>
        <?php if ($type === 'membership' && $plan !== []): ?>
            <span class="fw-product-card__period"><?php echo (int)($plan['duration_count'] ?? 1); ?> <?php echo fw_e($plan['duration_unit'] ?? 'month'); ?></span>
        <?php endif; ?>
        <div class="fw-product-card__footer">
            <span class="fw-product-card__price"><?php echo fw_e($price); ?></span>
            <?php if ($originalPrice !== ''): ?><del><?php echo fw_e($originalPrice); ?></del><?php endif; ?>
            <a href="<?php echo fw_e(fw_url($url)); ?>" aria-label="View <?php echo fw_e($title); ?>">View <span aria-hidden="true">&#8594;</span></a>
        </div>
    </div>
</article>
