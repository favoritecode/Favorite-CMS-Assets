<?php
/**
 * Hero Section — configurable headline, actions, metrics, and media (image or video).
 */
$tagline = trim((string)($siteTagline ?? ''));
$heroCfg = (!empty($heroCfg) && is_array($heroCfg)) ? $heroCfg : (function_exists('fw_get_section_config') ? fw_get_section_config('hero') : []);

$heroEyebrow      = (string)($heroCfg['eyebrow'] ?? 'Official Digital Platform');
$heroTitle        = (string)($heroCfg['title'] ?? 'Learn, create, and grow your digital presence.');
$heroLead         = (string)($heroCfg['lead'] ?? ($tagline !== '' ? $tagline : 'Curated tutorials, smart web tools, trusted digital products, and professional online services.'));
$heroBtnPrimary   = (string)($heroCfg['primary_text'] ?? 'Explore Digital Store');
$heroUrlPrimary   = (string)($heroCfg['primary_url'] ?? '/store');
$heroBtnSecondary = (string)($heroCfg['secondary_text'] ?? 'View Our Services');
$heroUrlSecondary = (string)($heroCfg['secondary_url'] ?? '#favorite-web-services');

$heroMediaType    = (string)($heroCfg['media_type'] ?? 'image');
$heroImageUrl     = trim((string)($heroCfg['image_url'] ?? ''));
$heroImageAlt     = (string)($heroCfg['image_alt'] ?? '');
$heroImageLink    = trim((string)($heroCfg['image_link'] ?? ''));
$heroImageTarget  = ($heroCfg['image_target'] ?? '_self') === '_blank' ? '_blank' : '_self';
$heroImageFit     = ($heroCfg['image_fit'] ?? 'contain') === 'cover' ? 'cover' : 'contain';

$heroVideoType    = (string)($heroCfg['video_type'] ?? 'mp4');
$heroVideoUrl     = trim((string)($heroCfg['video_url'] ?? ''));
$heroVideoPoster  = trim((string)($heroCfg['video_poster'] ?? ''));
$heroVideoAutoplay= !empty($heroCfg['video_autoplay']);
$heroVideoMuted   = !isset($heroCfg['video_muted']) || !empty($heroCfg['video_muted']);
$heroVideoLoop    = !isset($heroCfg['video_loop']) || !empty($heroCfg['video_loop']);
$heroVideoControls= !isset($heroCfg['video_controls']) || !empty($heroCfg['video_controls']);

$trustItems       = is_array($heroCfg['trust_items'] ?? null) ? $heroCfg['trust_items'] : [
    ['title' => 'Verified', 'desc' => 'Curated quality'],
    ['title' => 'Reliable', 'desc' => 'Professional support'],
    ['title' => 'Instant',  'desc' => 'Digital delivery'],
];

$defaultArt = function_exists('theme_asset_url')
    ? theme_asset_url('assets/images/illustrations/platform.svg')
    : fw_url('/themes/favorite-web/assets/images/illustrations/platform.svg');
$heroArt = $heroImageUrl !== '' ? fw_url($heroImageUrl) : $defaultArt;
?>
<section class="fw-hero<?php echo $heroMediaType === 'none' ? ' fw-hero--no-media' : ''; ?>" aria-labelledby="fw-hero-title">
    <div class="fw-hero__content">
        <p class="fw-eyebrow"><span></span> <?php echo fw_e($heroEyebrow); ?></p>
        <h1 class="fw-hero__title" id="fw-hero-title"><?php echo fw_e($heroTitle); ?></h1>
        <p class="fw-hero__lead"><?php echo fw_e($heroLead); ?></p>
        <div class="fw-hero__actions">
            <?php if ($heroBtnPrimary !== ''): ?>
                <a class="button button--primary fw-button" href="<?php echo fw_e(fw_url($heroUrlPrimary)); ?>"><?php echo fw_e($heroBtnPrimary); ?></a>
            <?php endif; ?>
            <?php if ($heroBtnSecondary !== ''): ?>
                <a class="button button--light fw-button" href="<?php echo fw_e(fw_url($heroUrlSecondary)); ?>"><?php echo fw_e($heroBtnSecondary); ?></a>
            <?php endif; ?>
        </div>
        <?php if (!empty($trustItems)): ?>
            <ul class="fw-hero__trust" aria-label="Key highlights">
                <?php foreach ($trustItems as $item): ?>
                    <li><strong><?php echo fw_e($item['title'] ?? ''); ?></strong><span><?php echo fw_e($item['desc'] ?? ''); ?></span></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
    <?php if ($heroMediaType !== 'none'): ?>
        <div class="fw-hero__visual" aria-hidden="true">
            <span class="fw-hero__orb fw-hero__orb--one"></span>
            <span class="fw-hero__orb fw-hero__orb--two"></span>
            <div class="fw-hero__art-wrap">
                <?php
                $heroVideoSource = null;
                if ($heroMediaType === 'video' && $heroVideoUrl !== '') {
                    $heroVideoSource = fw_resolve_hero_video_source($heroVideoUrl, [
                        'autoplay' => $heroVideoAutoplay,
                        'muted'    => $heroVideoMuted,
                    ]);
                }
                ?>
                <?php if ($heroVideoSource !== null && $heroVideoSource['type'] !== 'unknown'): ?>
                    <div class="fw-hero__video-container">
                        <?php if ($heroVideoSource['is_iframe']): ?>
                            <iframe
                                class="fw-hero__video"
                                src="<?php echo fw_e($heroVideoSource['src']); ?>"
                                title="Hero Video"
                                width="400"
                                height="300"
                                frameborder="0"
                                allow="autoplay; encrypted-media; fullscreen; picture-in-picture"
                                allowfullscreen
                                loading="eager"
                            ></iframe>
                        <?php else: ?>
                            <video
                                class="fw-hero__video"
                                src="<?php echo fw_e(fw_url($heroVideoSource['src'])); ?>"
                                <?php echo $heroVideoPoster !== '' ? ' poster="' . fw_e(fw_url($heroVideoPoster)) . '"' : ''; ?>
                                <?php echo $heroVideoAutoplay ? ' autoplay' : ''; ?>
                                <?php echo $heroVideoMuted ? ' muted' : ''; ?>
                                <?php echo $heroVideoLoop ? ' loop' : ''; ?>
                                <?php echo $heroVideoControls ? ' controls' : ''; ?>
                                playsinline
                                width="400"
                                height="300"
                            ></video>
                            <?php if ($heroVideoAutoplay): ?>
                            <script>
                            (function(){
                                var v = document.querySelector('.fw-hero__video-container video');
                                if (v && v.hasAttribute('autoplay')) {
                                    var p = v.play();
                                    if (p && typeof p.catch === 'function') {
                                        p.catch(function() { /* Autoplay prevented by browser policy; manual playback remains available */ });
                                    }
                                }
                            })();
                            </script>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <?php if ($heroImageLink !== ''): ?>
                        <a href="<?php echo fw_e(fw_url($heroImageLink)); ?>" target="<?php echo fw_e($heroImageTarget); ?>" tabindex="-1">
                            <img src="<?php echo fw_e($heroArt); ?>" alt="<?php echo fw_e($heroImageAlt); ?>" width="400" height="300" fetchpriority="high" decoding="async" style="object-fit: <?php echo fw_e($heroImageFit); ?>;">
                        </a>
                    <?php else: ?>
                        <img src="<?php echo fw_e($heroArt); ?>" alt="<?php echo fw_e($heroImageAlt); ?>" width="400" height="300" fetchpriority="high" decoding="async" style="object-fit: <?php echo fw_e($heroImageFit); ?>;">
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</section>
