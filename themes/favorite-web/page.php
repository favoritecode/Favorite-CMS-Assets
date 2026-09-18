<?php
require __DIR__ . '/header.php';
$featImg = $page->getFeaturedImage();
?>

<main class="site-main site-main--singular" id="main-content" tabindex="-1">
    <nav class="breadcrumb-nav" aria-label="Breadcrumbs">
        <a href="<?php echo fw_e(fw_url('/')); ?>">Home</a>
        <span class="breadcrumb-separator" aria-hidden="true">/</span>
        <span class="breadcrumb-current" aria-current="page"><?php echo fw_e(mb_strimwidth($page->title, 0, 45, '…', 'UTF-8')); ?></span>
    </nav>

    <article class="entry entry--page" id="page-<?php echo (int)$page->id; ?>">
        <header class="entry-header">
            <h1 class="entry-title"><?php echo fw_e($page->title); ?></h1>
        </header>

        <?php if ($featImg && !empty($featImg->url)): ?>
            <figure class="entry-media">
                <img src="<?php echo fw_e(fw_url((string)$featImg->url)); ?>"
                     alt="<?php echo fw_e($featImg->alt_text ?: $page->title); ?>"<?php echo fw_image_dimensions($featImg); ?>
                     loading="eager" fetchpriority="high" decoding="async">
            </figure>
        <?php endif; ?>

        <div class="entry-content">
            <?php echo fw_prepare_content(clean_post_content($page->content ?? '')); ?>
        </div>
    </article>
</main>

<?php
require __DIR__ . '/sidebar.php';
require __DIR__ . '/footer.php';
