<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$controller = file_get_contents($root . '/src/Controllers/AdminProductController.php');
$form = file_get_contents($root . '/views/admin/products/form.php');

$checks = [
    'controller uses the CMS Media Library' => str_contains($controller, 'MediaService::class)->upload($coverUpload, $userId)'),
    'upload validates server-side MIME' => str_contains($controller, "finfo(FILEINFO_MIME_TYPE)") && str_contains($controller, "str_starts_with($mime, 'image/')"),
    'local URLs are restricted to uploads' => str_contains($controller, "str_starts_with($value, '/uploads/')") && str_contains($controller, "str_contains($value, '..')"),
    'form supports multipart uploads' => str_contains($form, 'enctype="multipart/form-data"'),
    'form has image file input' => str_contains($form, 'name="cover_image"') && str_contains($form, 'type="file"'),
    'URL fallback remains available' => str_contains($form, 'name="cover_image_url"'),
];

$failed = [];
foreach ($checks as $label => $passed) {
    echo ($passed ? 'PASS' : 'FAIL') . ': ' . $label . PHP_EOL;
    if (!$passed) $failed[] = $label;
}
if ($failed) exit(1);
