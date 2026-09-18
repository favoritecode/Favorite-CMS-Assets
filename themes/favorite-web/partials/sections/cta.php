<?php
$cta = fw_cta_data();
?>
<section class="fw-section fw-cta" aria-labelledby="fw-cta-title">
    <div class="fw-cta__card">
        <span class="fw-cta__orb" aria-hidden="true"></span>
        <div class="fw-cta__content">
            <p class="fw-eyebrow fw-eyebrow--light"><span></span> <?php echo fw_e($cta['badge']); ?></p>
            <h2 class="fw-cta__title" id="fw-cta-title"><?php echo fw_e($cta['title']); ?></h2>
            <p class="fw-cta__desc"><?php echo fw_e($cta['description']); ?></p>
            <div class="fw-cta__actions">
                <?php if (!empty($cta['primary_text'])): ?>
                    <a class="button button--light fw-button" href="<?php echo fw_e(fw_url($cta['primary_url'])); ?>">
                        <?php echo fw_e($cta['primary_text']); ?>
                    </a>
                <?php endif; ?>
                <?php if (!empty($cta['secondary_text'])): ?>
                    <a class="button button--outline-light fw-button" href="<?php echo fw_e(fw_url($cta['secondary_url'])); ?>">
                        <?php echo fw_e($cta['secondary_text']); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

