<?php
require_once __DIR__ . '/functions.php';

$footerMenu      = fw_menu_items('footer', 3);
$footerItems     = $footerMenu['items'];
$footerUser      = $currentUser ?? (function_exists('current_user') ? current_user() : null);
$footerRegions   = array_values(array_filter(['footer-1', 'footer-2', 'footer-3'], static fn(string $region): bool => has_region_widgets($region)));

$cfg             = function_exists('fw_get_config') ? fw_get_config() : [];
$footerBrandName = !empty($cfg['footer_brand_name']) ? $cfg['footer_brand_name'] : \FavoriteCMS\Models\Setting::get('general', 'site_name', 'Favorite CMS');
$footerSummary   = array_key_exists('footer_summary', $cfg) && $cfg['footer_summary'] !== '' ? $cfg['footer_summary'] : 'Tutorials, tools, digital products, and professional services for growing online.';
$customCopyright = !empty($cfg['footer_copyright']) ? trim($cfg['footer_copyright']) : (function_exists('get_theme_mod') ? get_theme_mod('footer_copyright') : null);
$customCopyright = is_string($customCopyright) ? trim($customCopyright) : '';

$footerLinks = $footerItems;
$footerLinks[] = (object)['title' => 'Sitemap', 'url' => '/sitemap.xml', 'target' => '', 'children' => []];
if ($footerUser) {
    $footerLinks[] = (object)['title' => 'Admin Area', 'url' => '/admin', 'target' => '', 'children' => []];
}
$scriptUrl = function_exists('theme_asset_url') ? theme_asset_url('assets/js/main.js') : fw_url('/themes/favorite-web/assets/js/main.js');
?>
    </div><!-- /.layout -->
</div><!-- /.site-content -->

<footer class="site-footer" role="contentinfo">
    <?php if ($footerRegions !== []): ?>
        <div class="container footer-widgets footer-widgets--<?php echo count($footerRegions); ?>">
            <?php foreach ($footerRegions as $region): ?>
                <div class="footer-widget-col footer-col-<?php echo fw_e(substr($region, -1)); ?>">
                    <?php echo render_region($region); ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="container footer-bottom">
        <div class="footer-brand-block">
            <a class="footer-brand" href="<?php echo fw_e(fw_url('/')); ?>"><?php echo fw_e($footerBrandName); ?></a>
            <?php if (!empty($footerSummary)): ?>
                <p class="footer-summary"><?php echo fw_e($footerSummary); ?></p>
            <?php endif; ?>
            <p class="footer-copyright">
                &copy; <?php echo date('Y'); ?> <?php echo $customCopyright !== '' ? fw_e($customCopyright) : fw_e($footerBrandName) . '. All rights reserved.'; ?> Powered by <strong>Favorite CMS</strong>.
            </p>
        </div>

        <nav class="footer-nav" aria-label="Footer navigation">
            <?php fw_partial('nav-menu', [
                'items'       => $footerLinks,
                'menuClass'   => 'menu footer-menu',
                'prependHome' => !fw_menu_links_home($footerItems),
            ]); ?>
        </nav>
    </div>
</footer>

<script src="<?php echo fw_e($scriptUrl); ?>" defer></script>
</body>
</html>
