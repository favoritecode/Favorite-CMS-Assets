<?php
declare(strict_types=1);
$root = dirname(__DIR__);
$controller = (string) file_get_contents($root . '/src/Controllers/AdminProductController.php');
$form = (string) file_get_contents($root . '/views/admin/products/form.php');
$checks = [
    'controller uses the CMS Media Library' => str_contains($controller, 'MediaService::class)->upload($coverUpload, $userId)'),
    'upload checks image MIME server-side' => str_contains($controller, 'finfo(FILEINFO_MIME_TYPE)') && str_contains($controller, 'str_starts_with($mime'),
    'local media URL path is constrained' => str_contains($controller, "'/uploads/'") && str_contains($controller, "'..'"),
    'form supports multipart uploads' => str_contains($form, 'enctype="multipart/form-data"'),
    'form has image file input' => str_contains($form, 'name="cover_image"') && str_contains($form, 'type="file"'),
    'URL fallback remains available' => str_contains($form, 'name="cover_image_url"'),
    'local media URLs can be edited without browser URL rejection' => str_contains($form, 'id="cover_image_url" name="cover_image_url" type="text" inputmode="url"'),
];
$failed = [];
foreach ($checks as $label => $passed) {
    echo ($passed ? 'PASS' : 'FAIL') . ': ' . $label . PHP_EOL;
    if (!$passed) $failed[] = $label;
}
if ($failed) exit(1);
