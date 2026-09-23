<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Services;

use FavoriteCMS\Tools\Models\FrontendDesign;
use FavoriteCMS\Tools\Repositories\FrontendDesignRepository;
use FavoriteCMS\Tools\Support\CssScoper;
use InvalidArgumentException;
use RuntimeException;
use ZipArchive;

class DesignPackageImporter
{
    private const FORBIDDEN_EXTENSIONS = [
        'php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'phps', 'phar',
        'exe', 'bat', 'cmd', 'sh', 'bin', 'pl', 'py', 'cgi', 'dll', 'so',
        'msi', 'com', 'scr', 'vbs', 'wsf', 'ps1',
    ];

    private FrontendDesignRepository $repository;

    public function __construct(FrontendDesignRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Import a design package from a ZIP file.
     *
     * @param string $zipFilePath Absolute path to the uploaded ZIP file
     * @param bool $overwrite If true, update existing design with matching slug
     * @return FrontendDesign
     * @throws InvalidArgumentException|RuntimeException
     */
    public function importFromZip(string $zipFilePath, bool $overwrite = false): FrontendDesign
    {
        if (!file_exists($zipFilePath)) {
            throw new InvalidArgumentException("ZIP file does not exist: {$zipFilePath}");
        }

        $zip = new ZipArchive();
        $openResult = $zip->open($zipFilePath);
        if ($openResult !== true) {
            throw new RuntimeException("Failed to open ZIP archive. Error code: {$openResult}");
        }

        // 1. Pre-scan ZIP entries for security: Zip-Slip and forbidden file extensions
        $numFiles = $zip->numFiles;
        for ($i = 0; $i < $numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            if ($filename === false) {
                continue;
            }

            // Path traversal check
            if (str_contains($filename, '..') || str_starts_with($filename, '/') || str_starts_with($filename, '\\')) {
                $zip->close();
                throw new InvalidArgumentException("Suspicious path detected in ZIP archive: '{$filename}'");
            }

            // Check forbidden extensions
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if (in_array($ext, self::FORBIDDEN_EXTENSIONS, true)) {
                $zip->close();
                throw new InvalidArgumentException("Prohibited file type '.{$ext}' in ZIP archive: '{$filename}'");
            }
        }

        // 2. Extract into safe temporary directory
        $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'fwt_design_' . bin2hex(random_bytes(8));
        if (!mkdir($tempDir, 0755, true) && !is_dir($tempDir)) {
            $zip->close();
            throw new RuntimeException("Failed to create temporary extraction directory.");
        }

        try {
            $zip->extractTo($tempDir);
            $zip->close();

            // Locate manifest: design.json or manifest.json may be at root or inside a single top-level folder
            $manifestNames = ['design.json', 'manifest.json'];
            $manifestPath = null;
            $baseDir = $tempDir;

            foreach ($manifestNames as $mName) {
                if (file_exists($tempDir . DIRECTORY_SEPARATOR . $mName)) {
                    $manifestPath = $tempDir . DIRECTORY_SEPARATOR . $mName;
                    break;
                }
            }

            if ($manifestPath === null) {
                $subdirs = glob($tempDir . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR);
                if (!empty($subdirs)) {
                    $baseDir = $subdirs[0];
                    foreach ($manifestNames as $mName) {
                        if (file_exists($baseDir . DIRECTORY_SEPARATOR . $mName)) {
                            $manifestPath = $baseDir . DIRECTORY_SEPARATOR . $mName;
                            break;
                        }
                    }
                }
            }

            if ($manifestPath === null) {
                throw new InvalidArgumentException("Design package must contain a 'design.json' or 'manifest.json' manifest.");
            }

            $manifestContent = file_get_contents($manifestPath);
            $manifest = json_decode($manifestContent ?: '', true);
            if (!is_array($manifest)) {
                throw new InvalidArgumentException("Invalid JSON in manifest file.");
            }

            // Validate required manifest fields
            $name = trim((string) ($manifest['name'] ?? ''));
            if ($name === '') {
                throw new InvalidArgumentException("Design manifest requires a 'name' field.");
            }

            $slug = trim((string) ($manifest['slug'] ?? ''));
            if ($slug === '') {
                $slug = strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '-', $name));
                $slug = trim(preg_replace('/-+/', '-', $slug), '-');
            }
            if ($slug === '') {
                $slug = 'custom-design-' . bin2hex(random_bytes(4));
            }

            $description = trim((string) ($manifest['description'] ?? ''));
            $version = trim((string) ($manifest['version'] ?? '1.0.0'));
            $author = trim((string) ($manifest['author'] ?? ''));
            $category = trim((string) ($manifest['category'] ?? 'Custom'));

            // Load HTML template
            $templateHtml = '';
            if (file_exists($baseDir . DIRECTORY_SEPARATOR . 'template.html')) {
                $templateHtml = (string) file_get_contents($baseDir . DIRECTORY_SEPARATOR . 'template.html');
            } elseif (file_exists($baseDir . DIRECTORY_SEPARATOR . 'template.htm')) {
                $templateHtml = (string) file_get_contents($baseDir . DIRECTORY_SEPARATOR . 'template.htm');
            } elseif (isset($manifest['template_html'])) {
                $templateHtml = (string) $manifest['template_html'];
            } elseif (isset($manifest['template_markup'])) {
                $templateHtml = (string) $manifest['template_markup'];
            } elseif (isset($manifest['template'])) {
                $templateHtml = (string) $manifest['template'];
            }
            if (trim($templateHtml) === '') {
                throw new InvalidArgumentException("Design package must provide HTML template content ('template.html').");
            }

            // Load CSS styles (styles.css or style.css)
            $cssContent = '';
            if (file_exists($baseDir . DIRECTORY_SEPARATOR . 'styles.css')) {
                $cssContent = (string) file_get_contents($baseDir . DIRECTORY_SEPARATOR . 'styles.css');
            } elseif (file_exists($baseDir . DIRECTORY_SEPARATOR . 'style.css')) {
                $cssContent = (string) file_get_contents($baseDir . DIRECTORY_SEPARATOR . 'style.css');
            } elseif (isset($manifest['css_content'])) {
                $cssContent = (string) $manifest['css_content'];
            } elseif (isset($manifest['css'])) {
                $cssContent = (string) $manifest['css'];
            }

            // Scope and validate CSS
            if (trim($cssContent) !== '') {
                $cssContent = CssScoper::validateAndScope($cssContent, $slug);
            }

            // Load JS script (optional, disabled by default)
            $jsContent = '';
            if (file_exists($baseDir . DIRECTORY_SEPARATOR . 'script.js')) {
                $jsContent = (string) file_get_contents($baseDir . DIRECTORY_SEPARATOR . 'script.js');
            } elseif (file_exists($baseDir . DIRECTORY_SEPARATOR . 'scripts.js')) {
                $jsContent = (string) file_get_contents($baseDir . DIRECTORY_SEPARATOR . 'scripts.js');
            } elseif (isset($manifest['js_content'])) {
                $jsContent = (string) $manifest['js_content'];
            } elseif (isset($manifest['js'])) {
                $jsContent = (string) $manifest['js'];
            }

            $supportedNormalizers = $manifest['supported_normalizers'] ?? ['media', 'default'];
            $settingsSchema = $manifest['settings_schema'] ?? ($manifest['schema'] ?? []);

            // Load bindings schema
            $bindingsSchema = $manifest['bindings_schema'] ?? ($manifest['bindings'] ?? []);
            if (file_exists($baseDir . DIRECTORY_SEPARATOR . 'bindings.json')) {
                $decodedBindings = json_decode((string)file_get_contents($baseDir . DIRECTORY_SEPARATOR . 'bindings.json'), true);
                if (is_array($decodedBindings)) {
                    $bindingsSchema = $decodedBindings;
                }
            }

            // Load normalizer key
            $normalizerKey = (string)($manifest['normalizer_key'] ?? ($supportedNormalizers[0] ?? 'default'));

            // Load preview data from preview.json or manifest
            $previewData = $manifest['preview_data'] ?? ($manifest['preview'] ?? []);
            if (file_exists($baseDir . DIRECTORY_SEPARATOR . 'preview.json')) {
                $decodedPreview = json_decode((string)file_get_contents($baseDir . DIRECTORY_SEPARATOR . 'preview.json'), true);
                if (is_array($decodedPreview)) {
                    $previewData = $decodedPreview;
                }
            }

            // Check existing design
            $existing = $this->repository->findBySlug($slug);
            if ($existing !== null) {
                if (!$overwrite) {
                    throw new InvalidArgumentException("A design with slug '{$slug}' already exists. Use overwrite option to replace.");
                }

                $this->repository->update($existing->getId(), [
                    'name'                  => $name,
                    'description'           => $description,
                    'version'               => $version,
                    'author'                => $author,
                    'category'              => $category,
                    'template_html'         => $templateHtml,
                    'template_markup'       => $templateHtml,
                    'css_content'           => $cssContent,
                    'js_content'            => $jsContent,
                    'js_enabled'            => 0, // Enforce disabled by default
                    'supported_normalizers' => $supportedNormalizers,
                    'settings_schema'       => $settingsSchema,
                    'bindings_schema'       => $bindingsSchema,
                    'normalizer_key'        => $normalizerKey,
                    'preview_data'          => $previewData,
                ]);

                return $this->repository->findById($existing->getId());
            }

            return $this->repository->create([
                'name'                  => $name,
                'slug'                  => $slug,
                'description'           => $description,
                'version'               => $version,
                'author'                => $author,
                'category'              => $category,
                'template_html'         => $templateHtml,
                'template_markup'       => $templateHtml,
                'css_content'           => $cssContent,
                'js_content'            => $jsContent,
                'is_builtin'            => 0,
                'is_active'             => 1,
                'js_enabled'            => 0,
                'supported_normalizers' => $supportedNormalizers,
                'settings_schema'       => $settingsSchema,
                'bindings_schema'       => $bindingsSchema,
                'normalizer_key'        => $normalizerKey,
                'preview_data'          => $previewData,
            ]);
        } finally {
            $this->removeDirectory($tempDir);
        }
    }

    /**
     * Export a FrontendDesign as a ZIP package.
     */
    public function exportToZip(FrontendDesign $design, string $destinationZip): string
    {
        $zip = new ZipArchive();
        $res = $zip->open($destinationZip, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if ($res !== true) {
            throw new RuntimeException("Cannot create ZIP file: {$destinationZip}");
        }

        $manifest = [
            'name' => $design->getName(),
            'slug' => $design->getSlug(),
            'description' => $design->getDescription(),
            'version' => $design->getVersion(),
            'author' => $design->getAuthor(),
            'category' => $design->getCategory(),
            'supported_normalizers' => $design->getSupportedNormalizers(),
            'settings_schema' => $design->getSettingsSchema(),
        ];

        $zip->addFromString('design.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $zip->addFromString('template.html', $design->getTemplateHtml());

        if ($design->getCssContent() !== '') {
            $zip->addFromString('styles.css', $design->getCssContent());
        }

        if ($design->getJsContent() !== '') {
            $zip->addFromString('script.js', $design->getJsContent());
        }

        $zip->close();
        return $destinationZip;
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir) ?: [], ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}
