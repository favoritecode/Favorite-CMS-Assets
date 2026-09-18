<?php
/**
 * Responsive grid of post cards with optional empty state and pagination.
 *
 * @var array<int, object> $posts
 * @var int|null           $headingLevel Card title heading level
 * @var bool|null          $eagerFirst   Load the first card image eagerly
 * @var array|null         $emptyState   Variables for the empty-state partial when there are no posts
 * @var array|null         $pagination   Variables for the pagination partial
 */
$posts = is_array($posts ?? null) ? $posts : [];
?>
<?php if ($posts === []): ?>
    <?php if (!empty($emptyState)) { fw_partial('empty-state', $emptyState); } ?>
<?php else: ?>
    <div class="post-grid">
        <?php foreach ($posts as $index => $gridPost): ?>
            <?php fw_partial('post-card', [
                'post'         => $gridPost,
                'variant'      => 'default',
                'headingLevel' => $headingLevel ?? 2,
                'eager'        => !empty($eagerFirst) && $index === 0,
            ]); ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php if (!empty($pagination)) { fw_partial('pagination', $pagination); } ?>
