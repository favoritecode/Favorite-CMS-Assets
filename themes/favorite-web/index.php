<?php
$isHome = !empty($isHome);
$fwHasSidebar = !$isHome;
if ($isHome) {
    $bodyClass = trim((string)($bodyClass ?? '') . ' favorite-web-home');
}
require __DIR__ . '/header.php';

$posts       = is_array($posts ?? null) ? $posts : [];
$currentPage = max(1, (int)($currentPage ?? 1));
$totalPages  = max(1, (int)($totalPages ?? 1));
$totalPosts  = (int)($totalPosts ?? count($posts));
$isFirstPage = $currentPage === 1;
$enabledSections = [];

if ($isHome) {
    try {
        $layoutService = new \FavoriteCMS\Themes\ThemeLayoutService(\FavoriteCMS\Core\Application::getInstance());
        $enabledSections = array_values(array_filter(
            $layoutService->getSections(),
            static fn(array $section): bool => !empty($section['enabled'])
        ));
    } catch (\Throwable) {
        $enabledSections = [];
    }
}
?>

<main class="site-main site-main--listing" id="main-content" tabindex="-1">
    <?php if (!empty($archiveTitle)): ?>
        <?php fw_partial('page-header', [
            'eyebrow'     => 'Browsing Archive',
            'title'       => $archiveTitle,
            'description' => $archiveDescription ?? null,
        ]); ?>
    <?php endif; ?>

    <?php if ($isHome && $isFirstPage): ?>
        <?php if ($enabledSections === []): ?>
            <h1 class="visually-hidden"><?php echo fw_e($siteTitle); ?></h1>
        <?php else: ?>
            <?php foreach ($enabledSections as $section): ?>
                <?php $secId = $section['id'] ?? ''; ?>
                <?php if ($secId === 'hero'): ?>
                    <?php fw_partial('sections/hero', ['siteTitle' => $siteTitle, 'siteTagline' => $siteTagline]); ?>
                    <?php if (has_region_widgets('homepage-after-hero')): ?>
                        <aside class="home-announcement" aria-label="Featured announcement">
                            <?php echo render_region('homepage-after-hero'); ?>
                        </aside>
                    <?php endif; ?>
                <?php elseif ($secId === 'trust-stats'): ?>
                    <?php fw_partial('sections/trust-stats', ['stats' => fw_trust_stats()]); ?>
                <?php elseif ($secId === 'about'): ?>
                    <?php fw_partial('sections/about', []); ?>
                <?php elseif ($secId === 'services'): ?>
                    <?php fw_partial('sections/services', ['services' => fw_official_services()]); ?>
                <?php elseif ($secId === 'digital-products'): ?>
                    <?php fw_partial('sections/products', ['products' => fw_store_products('digital', 6)]); ?>
                <?php elseif ($secId === 'packages'): ?>
                    <?php fw_partial('sections/packages', ['packages' => fw_packages(3)]); ?>
                <?php elseif ($secId === 'memberships'): ?>
                    <?php fw_partial('sections/memberships', ['products' => fw_store_products('membership', 3)]); ?>
                <?php elseif ($secId === 'latest-posts'): ?>
                    <?php fw_partial('sections/latest', [
                        'posts'        => $posts,
                        'currentPage'  => $currentPage,
                        'totalPages'   => $totalPages,
                        'hasAnyPosts'  => $totalPosts > 0,
                        'showHeading'  => true,
                        'headingLevel' => 3,
                        'eagerFirst'   => false,
                    ]); ?>
                <?php elseif ($secId === 'cta'): ?>
                    <?php fw_partial('sections/cta', []); ?>
                <?php elseif ($secId === 'official-platform'): ?>
                    <?php fw_partial('sections/platform', ['items' => fw_platform_links()]); ?>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php elseif ($isHome): ?>
        <?php fw_partial('page-header', [
            'eyebrow' => $siteTitle,
            'title'   => 'Latest Articles',
            'meta'    => 'Page ' . $currentPage . ' of ' . $totalPages,
        ]); ?>
        <?php fw_partial('sections/latest', [
            'posts'        => $posts,
            'currentPage'  => $currentPage,
            'totalPages'   => $totalPages,
            'hasAnyPosts'  => $totalPosts > 0,
            'showHeading'  => false,
            'headingLevel' => 2,
            'eagerFirst'   => true,
        ]); ?>
    <?php else: ?>
        <?php if (empty($archiveTitle)): ?>
            <h1 class="visually-hidden"><?php echo fw_e($siteTitle); ?></h1>
        <?php endif; ?>
        <?php fw_partial('post-grid', [
            'posts'      => $posts,
            'eagerFirst' => true,
            'emptyState' => [
                'title'   => 'No Articles Published Yet',
                'message' => 'New tutorials and updates will appear here as soon as they are published.',
                'actions' => !empty($_SESSION['auth_user_id']) ? [['label' => 'Write Your First Post', 'url' => '/admin/posts/new']] : [],
            ],
            'pagination' => ['currentPage' => $currentPage, 'totalPages' => $totalPages, 'label' => 'Posts pagination'],
        ]); ?>
    <?php endif; ?>
</main>

<?php
if (!$isHome) {
    require __DIR__ . '/sidebar.php';
}
require __DIR__ . '/footer.php';
