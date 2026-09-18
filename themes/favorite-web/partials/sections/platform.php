<?php $items = is_array($items ?? null) ? $items : []; ?>
<?php if ($items !== []): ?>
<section class="fw-platform" aria-label="Favorite Web platform">
    <?php foreach ($items as $index => $item): ?>
        <article class="fw-platform__item">
            <span class="fw-platform__number" aria-hidden="true"><?php echo str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT); ?></span>
            <div>
                <h2><?php echo fw_e($item['title'] ?? ''); ?></h2>
                <p><?php echo fw_e($item['description'] ?? ''); ?></p>
                <a href="<?php echo fw_e(fw_url((string)($item['url'] ?? '/'))); ?>"><?php echo fw_e($item['label'] ?? 'Explore'); ?> <span aria-hidden="true">&#8599;</span></a>
            </div>
        </article>
    <?php endforeach; ?>
</section>
<?php endif; ?>
