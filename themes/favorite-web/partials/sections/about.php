<?php
/**
 * About Section — company narrative, capability highlights, and visual media (image or video).
 */
$about = fw_about_data();
$defaultArt = function_exists('theme_asset_url')
    ? theme_asset_url('assets/images/illustrations/digital-products.svg')
    : fw_url('/themes/favorite-web/assets/images/illustrations/digital-products.svg');

$aboutMediaUrl = trim((string)($about['image_url'] ?? ''));
$aboutArt = $aboutMediaUrl !== '' ? fw_url($aboutMediaUrl) : $defaultArt;
$aboutMediaType = (string)($about['media_type'] ?? 'image');
$aboutVideoUrl = trim((string)($about['video_url'] ?? ''));
$aboutVideoType = (string)($about['video_type'] ?? 'mp4');
?>
<section class="fw-section fw-about" id="about-us" aria-labelledby="fw-about-title">
    <div class="fw-about__grid<?php echo $aboutMediaType === 'none' ? ' fw-about__grid--no-media' : ''; ?>">
        <div class="fw-about__content">
            <p class="fw-eyebrow"><span></span> <?php echo fw_e($about['badge']); ?></p>
            <h2 id="fw-about-title"><?php echo fw_e($about['title']); ?></h2>
            <p class="fw-about__desc"><?php echo fw_e($about['description']); ?></p>

            <?php if (!empty($about['highlights'])): ?>
                <ul class="fw-about__highlights" aria-label="Key capability highlights">
                    <?php foreach ($about['highlights'] as $highlight): ?>
                        <li>
                            <svg class="icon icon-check" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                            <span><?php echo fw_e($highlight); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <?php if (!empty($about['cta_text'])): ?>
                <div class="fw-about__actions">
                    <a class="button button--primary fw-button" href="<?php echo fw_e(fw_url($about['cta_url'])); ?>">
                        <?php echo fw_e($about['cta_text']); ?> <span aria-hidden="true">&#8594;</span>
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($aboutMediaType !== 'none'): ?>
            <div class="fw-about__visual" aria-hidden="true">
                <div class="fw-about__visual-card">
                    <?php if ($aboutMediaType === 'video' && $aboutVideoUrl !== ''): ?>
                        <?php if ($aboutVideoType === 'youtube'): ?>
                            <?php $ytId = fw_extract_youtube_id($aboutVideoUrl); ?>
                            <?php if ($ytId !== null): ?>
                                <iframe src="https://www.youtube-nocookie.com/embed/<?php echo fw_e($ytId); ?>?rel=0" title="About Video" width="380" height="280" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen style="border-radius: 12px; width: 100%; height: 100%; min-height: 240px;"></iframe>
                            <?php endif; ?>
                        <?php elseif ($aboutVideoType === 'vimeo'): ?>
                            <?php $vmId = fw_extract_vimeo_id($aboutVideoUrl); ?>
                            <?php if ($vmId !== null): ?>
                                <iframe src="https://player.vimeo.com/video/<?php echo fw_e($vmId); ?>" title="About Video" width="380" height="280" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen style="border-radius: 12px; width: 100%; height: 100%; min-height: 240px;"></iframe>
                            <?php endif; ?>
                        <?php else: ?>
                            <video src="<?php echo fw_e(fw_url($aboutVideoUrl)); ?>" controls playsinline width="380" height="280" style="border-radius: 12px; width: 100%; height: auto;"></video>
                        <?php endif; ?>
                    <?php else: ?>
                        <img src="<?php echo fw_e($aboutArt); ?>" alt="<?php echo fw_e($about['about_image_alt'] ?? 'About visual'); ?>" width="380" height="280" loading="lazy" decoding="async">
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>
