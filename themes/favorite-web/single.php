<?php
require __DIR__ . '/header.php';

$author       = $post->getAuthor();
$cats         = $post->getTaxonomies('category');
$tags         = $post->getTaxonomies('tag');
$featImg      = $post->getFeaturedImage();
$comments     = $post->getComments('approved');
$previousPost = $previousPost ?? null;
$nextPost     = $nextPost ?? null;
$authorBio    = $author ? trim((string)($author->bio ?? '')) : '';

if (function_exists('fw_track_post_view')) {
    fw_track_post_view($post, (bool)($isPreview ?? false));
}
?>

<main class="site-main site-main--singular" id="main-content" tabindex="-1">
    <nav class="breadcrumb-nav" aria-label="Breadcrumbs">
        <a href="<?php echo fw_e(fw_url('/')); ?>">Home</a>
        <span class="breadcrumb-separator" aria-hidden="true">/</span>
        <?php if (!empty($cats[0])): ?>
            <a href="<?php echo fw_e(fw_url('/category/' . $cats[0]->slug)); ?>"><?php echo fw_e($cats[0]->name); ?></a>
            <span class="breadcrumb-separator" aria-hidden="true">/</span>
        <?php endif; ?>
        <span class="breadcrumb-current" aria-current="page"><?php echo fw_e(mb_strimwidth($post->title, 0, 45, '…', 'UTF-8')); ?></span>
    </nav>

    <article class="entry entry--post" id="post-<?php echo (int)$post->id; ?>">
        <header class="entry-header">
            <?php if (!empty($cats)): ?>
                <p class="entry-categories">
                    <?php foreach ($cats as $cat): ?>
                        <a class="category-pill" href="<?php echo fw_e(fw_url('/category/' . $cat->slug)); ?>"><?php echo fw_e($cat->name); ?></a>
                    <?php endforeach; ?>
                </p>
            <?php endif; ?>

            <h1 class="entry-title"><?php echo fw_e($post->title); ?></h1>

            <?php fw_partial('entry-meta', ['post' => $post, 'context' => 'single', 'commentCount' => count($comments)]); ?>
        </header>

        <?php if ($featImg && !empty($featImg->url)): ?>
            <figure class="entry-media">
                <img src="<?php echo fw_e(fw_url((string)$featImg->url)); ?>"
                     alt="<?php echo fw_e($featImg->alt_text ?: $post->title); ?>"<?php echo fw_image_dimensions($featImg); ?>
                     loading="eager" fetchpriority="high" decoding="async">
            </figure>
        <?php endif; ?>

        <div class="entry-content">
            <?php echo fw_prepare_content($post->content ?? ''); ?>
        </div>

        <?php if (!empty($tags)): ?>
            <footer class="entry-footer">
                <p class="entry-tags">
                    <span class="entry-tags__label">Tags:</span>
                    <?php foreach ($tags as $tag): ?>
                        <a class="tag-badge" href="<?php echo fw_e(fw_url('/tag/' . $tag->slug)); ?>">#<?php echo fw_e($tag->name); ?></a>
                    <?php endforeach; ?>
                </p>
            </footer>
        <?php endif; ?>

        <?php if ($author): ?>
            <?php $authorAvatar = method_exists($author, 'getAvatarUrl') ? $author->getAvatarUrl() : null; ?>
            <section class="author-box" aria-label="About the author">
                <span class="avatar avatar--large" aria-hidden="true">
                    <?php if ($authorAvatar): ?>
                        <img src="<?php echo fw_e(fw_url($authorAvatar)); ?>" alt="" width="56" height="56" loading="lazy" decoding="async">
                    <?php else: ?>
                        <?php echo fw_e(fw_initial(fw_display_name($author))); ?>
                    <?php endif; ?>
                </span>
                <div class="author-box__body">
                    <p class="author-box__label">Written by</p>
                    <p class="author-box__name"><?php echo fw_e(fw_display_name($author)); ?></p>
                    <?php if ($authorBio !== ''): ?>
                        <p class="author-box__bio"><?php echo nl2br(fw_e($authorBio)); ?></p>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>
    </article>

    <?php fw_partial('post-navigation', ['previousPost' => $previousPost, 'nextPost' => $nextPost]); ?>

    <?php fw_partial('comments', ['post' => $post, 'comments' => $comments, 'commentNotice' => $commentNotice ?? null]); ?>
</main>

<?php
require __DIR__ . '/sidebar.php';
require __DIR__ . '/footer.php';
