<?php
/**
 * Listing page header (archives, search, paginated homepage).
 *
 * @var string      $title
 * @var string|null $eyebrow
 * @var string|null $description
 * @var string|null $meta
 * @var array|null  $search Variables for an embedded search form
 */
?>
<header class="page-header">
    <?php if (!empty($eyebrow)): ?>
        <p class="page-header__eyebrow"><?php echo fw_e($eyebrow); ?></p>
    <?php endif; ?>
    <h1 class="page-header__title"><?php echo fw_e($title ?? ''); ?></h1>
    <?php if (!empty($description)): ?>
        <p class="page-header__description"><?php echo fw_e($description); ?></p>
    <?php endif; ?>
    <?php if (!empty($meta)): ?>
        <p class="page-header__meta"><?php echo fw_e($meta); ?></p>
    <?php endif; ?>
    <?php if (!empty($search)) { fw_partial('search-form', $search); } ?>
</header>
