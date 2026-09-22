<?php
/**
 * Post metadata: author, date, reading time and (on single posts) comment count.
 *
 * @var object      $post
 * @var string|null $context      'card' or 'single'
 * @var int|null    $commentCount Approved comment count (single posts)
 */
$context    = ($context ?? 'card') === 'single' ? 'single' : 'card';
$author     = method_exists($post, 'getAuthor') ? $post->getAuthor() : null;
$authorName = fw_display_name($author);
$avatarUrl  = ($author && method_exists($author, 'getAvatarUrl')) ? $author->getAvatarUrl() : null;
$postDate   = $post->published_at ?? $post->created_at;
$viewCount  = function_exists('fw_get_post_views') ? fw_get_post_views($post) : 0;
?>
<ul class="entry-meta entry-meta--<?php echo $context; ?>">
    <li class="entry-meta__author">
        <span class="avatar" aria-hidden="true">
            <?php if ($avatarUrl): ?>
                <img src="<?php echo fw_e(fw_url($avatarUrl)); ?>" alt="" width="28" height="28" loading="lazy" decoding="async">
            <?php else: ?>
                <?php echo fw_e(fw_initial($authorName)); ?>
            <?php endif; ?>
        </span>
        <span class="entry-meta__author-name"><?php echo fw_e($authorName); ?></span>
    </li>
    <li><time datetime="<?php echo fw_e(format_date($postDate, 'c')); ?>"><?php echo fw_e(format_date($postDate, $context === 'single' ? 'F j, Y' : 'M j, Y')); ?></time></li>
    <li class="entry-meta__views">
        <svg class="entry-meta__icon" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="flex-shrink: 0; opacity: 0.75;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
        <span><?php echo fw_e(function_exists('fw_format_views') ? fw_format_views($viewCount) : "{$viewCount} Views"); ?></span>
    </li>
    <?php if ($context === 'single' && !empty($commentCount)): ?>
        <li><a href="#comments"><?php echo (int)$commentCount; ?> <?php echo (int)$commentCount === 1 ? 'Comment' : 'Comments'; ?></a></li>
    <?php endif; ?>
</ul>
