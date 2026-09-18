<?php
/**
 * Post card used by every listing (homepage, archives, search).
 *
 * @var object      $post
 * @var string|null $variant      'default', 'lead' or 'compact'
 * @var int|null    $headingLevel Heading level of the title (2-4)
 * @var bool|null   $eager        Load the image eagerly (above the fold)
 */
$variant      = in_array($variant ?? 'default', ['default', 'lead', 'compact'], true) ? ($variant ?? 'default') : 'default';
$headingTag   = 'h' . max(2, min(4, (int)($headingLevel ?? 2)));
$href         = fw_url('/post/' . $post->slug);
$image        = $post->getFeaturedImage();
$categories   = $variant === 'compact' ? [] : array_slice($post->getTaxonomies('category'), 0, 2);
$excerpt      = $variant === 'compact' ? '' : fw_excerpt($post, $variant === 'lead' ? 220 : 150);
$loadingAttrs = !empty($eager) ? ' loading="eager" fetchpriority="high"' : ' loading="lazy"';
?>
<article class="post-card post-card--<?php echo $variant; ?><?php echo ($image && !empty($image->url)) ? ' has-media' : ''; ?>">
    <?php if ($image && !empty($image->url)): ?>
        <a class="post-card__media" href="<?php echo fw_e($href); ?>" tabindex="-1" aria-hidden="true">
            <img src="<?php echo fw_e(fw_url((string)$image->url)); ?>" alt=""<?php echo fw_image_dimensions($image); ?><?php echo $loadingAttrs; ?> decoding="async">
        </a>
    <?php endif; ?>
    <div class="post-card__body">
        <?php if ($categories !== []): ?>
            <p class="post-card__categories">
                <?php foreach ($categories as $cat): ?>
                    <a class="category-pill" href="<?php echo fw_e(fw_url('/category/' . $cat->slug)); ?>"><?php echo fw_e($cat->name); ?></a>
                <?php endforeach; ?>
            </p>
        <?php endif; ?>
        <<?php echo $headingTag; ?> class="post-card__title"><a href="<?php echo fw_e($href); ?>"><?php echo fw_e($post->title); ?></a></<?php echo $headingTag; ?>>
        <?php if ($excerpt !== ''): ?>
            <p class="post-card__excerpt"><?php echo fw_e($excerpt); ?></p>
        <?php endif; ?>
        <?php fw_partial('entry-meta', ['post' => $post, 'context' => 'card']); ?>
    </div>
</article>
