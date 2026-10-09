<?php
declare(strict_types=1);

$controller = file_get_contents(__DIR__ . '/../src/Controllers/CustomerShopController.php');
if (!is_string($controller)) {
    throw new RuntimeException('Could not read storefront controller.');
}
$checks = [
    'uses active theme header' => "require \$themeDir . '/header.php';",
    'uses active theme footer' => "require \$themeDir . '/footer.php';",
    'uses active theme sidebar when present' => "\$sidebar = \$themeDir . '/sidebar.php';",
    'sets theme-scoped body class' => "\$bodyClass = 'favorite-shop-page';",
    'does not emit a separate HTML document' => !str_contains($controller, "'<!doctype html>"),
];
foreach ($checks as $label => $needle) {
    $passed = is_bool($needle) ? $needle : str_contains($controller, $needle);
    if (!$passed) {
        throw new RuntimeException('Theme integration check failed: ' . $label);
    }
}
echo "Theme integration checks passed.\n";
