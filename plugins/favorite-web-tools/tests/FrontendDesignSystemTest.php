<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Tests;

use FavoriteCMS\Core\Application;
use FavoriteCMS\Core\Database;
use FavoriteCMS\Core\Request;
use FavoriteCMS\Tools\Controllers\Admin\AdminFrontendDesignController;
use FavoriteCMS\Tools\Controllers\Api\ToolExecutionApiController;
use FavoriteCMS\Tools\Models\FrontendDesign;
use FavoriteCMS\Tools\Models\Tool;
use FavoriteCMS\Tools\Normalizers\DefaultResultNormalizer;
use FavoriteCMS\Tools\Normalizers\MediaResultNormalizer;
use FavoriteCMS\Tools\Normalizers\NormalizerResolver;
use FavoriteCMS\Tools\Repositories\CategoryRepository;
use FavoriteCMS\Tools\Repositories\FrontendDesignRepository;
use FavoriteCMS\Tools\Repositories\PythonServiceRepository;
use FavoriteCMS\Tools\Repositories\ToolRepository;
use FavoriteCMS\Tools\Services\AccessControlService;
use FavoriteCMS\Tools\Services\DesignPackageImporter;
use FavoriteCMS\Tools\Services\DownloadManagerService;
use FavoriteCMS\Tools\Services\ToolExecutionService;
use FavoriteCMS\Tools\Support\CsrfGuard;
use FavoriteCMS\Tools\Support\CssScoper;
use FavoriteCMS\Tools\Support\SafeTemplateRenderer;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ZipArchive;

class FrontendDesignSystemTest extends TestCase
{
    private Database $db;
    private FrontendDesignRepository $designRepo;
    private ToolRepository $toolRepo;
    private CategoryRepository $catRepo;
    private PythonServiceRepository $pythonRepo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db = new Database([
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        // Run migrations 001 to 005
        require_once dirname(__DIR__) . '/database/migrations/001_create_favorite_web_tool_categories_table.php';
        require_once dirname(__DIR__) . '/database/migrations/002_create_favorite_web_tool_python_services_table.php';
        require_once dirname(__DIR__) . '/database/migrations/003_create_favorite_web_tools_table.php';
        require_once dirname(__DIR__) . '/database/migrations/004_add_fields_to_favorite_web_tool_python_services_table.php';
        require_once dirname(__DIR__) . '/database/migrations/005_create_favorite_web_tool_frontend_designs_table.php';

        (new \CreateFavoriteWebToolCategoriesTable($this->db))->up();
        (new \CreateFavoriteWebToolPythonServicesTable($this->db))->up();
        (new \CreateFavoriteWebToolsTable($this->db))->up();
        (new \AddFieldsToFavoriteWebToolPythonServicesTable($this->db))->up();
        (new \CreateFavoriteWebToolFrontendDesignsTable($this->db))->up();

        $this->catRepo = new CategoryRepository($this->db);
        $this->toolRepo = new ToolRepository($this->db);
        $this->pythonRepo = new PythonServiceRepository($this->db);
        $this->designRepo = new FrontendDesignRepository($this->db);

        $this->designRepo->seedBuiltins();
    }

    public function testBuiltinDesignsSeededSuccessfully(): void
    {
        $all = $this->designRepo->all();
        $this->assertGreaterThanOrEqual(5, count($all));

        $mediaCards = $this->designRepo->findBySlug('media-downloader-cards');
        $this->assertNotNull($mediaCards);
        $this->assertEquals('Media Downloader Cards', $mediaCards->getName());
        $this->assertTrue($mediaCards->isBuiltin());
        $this->assertTrue($mediaCards->isActive());
        $this->assertFalse($mediaCards->isJsEnabled());
        $this->assertStringContainsString('{{download_url}}', $mediaCards->getTemplateHtml());

        $defaultUi = $this->designRepo->findBySlug('default');
        $this->assertNotNull($defaultUi);
        $this->assertTrue($defaultUi->isBuiltin());

        $cardGrid = $this->designRepo->findBySlug('card-grid');
        $this->assertNotNull($cardGrid);

        $tableView = $this->designRepo->findBySlug('table-view');
        $this->assertNotNull($tableView);

        $apiResult = $this->designRepo->findBySlug('api-result-view');
        $this->assertNotNull($apiResult);
    }

    public function testFrontendDesignCrudOperations(): void
    {
        $custom = $this->designRepo->create([
            'name'          => 'Custom Hero Design',
            'slug'          => 'custom-hero-design',
            'description'   => 'A bold hero result layout.',
            'category'      => 'Custom',
            'version'       => '1.0.0',
            'author'        => 'Test Author',
            'template_html' => '<div class="hero"><h1>{{title}}</h1><a href="{{download_url}}">Download</a></div>',
            'css_content'   => '.hero { background: #000; color: #fff; }',
            'is_builtin'    => 0,
            'is_active'     => 1,
            'js_enabled'    => 0,
        ]);

        $this->assertNotNull($custom->getId());
        $this->assertEquals('custom-hero-design', $custom->getSlug());
        $this->assertFalse($custom->isBuiltin());

        // Update
        $this->designRepo->update($custom->getId(), [
            'name' => 'Custom Hero Design Updated',
        ]);
        $updated = $this->designRepo->find($custom->getId());
        $this->assertEquals('Custom Hero Design Updated', $updated->getName());

        // Uniqueness check
        $this->expectException(InvalidArgumentException::class);
        $this->designRepo->create([
            'name'          => 'Duplicate Slug Test',
            'slug'          => 'custom-hero-design',
            'template_html' => '<div>Test</div>',
        ]);
    }

    public function testBuiltinDesignCannotBeDeleted(): void
    {
        $builtin = $this->designRepo->findBySlug('media-downloader-cards');
        $this->assertNotNull($builtin);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Built-in system designs cannot be deleted');
        $this->designRepo->delete($builtin->getId());
    }

    public function testDesignCannotBeDeletedIfInUseByTools(): void
    {
        $custom = $this->designRepo->create([
            'name'          => 'In Use Design',
            'slug'          => 'in-use-design',
            'template_html' => '<div>{{output}}</div>',
            'is_builtin'    => 0,
        ]);

        // Create tool that uses this design
        $tool = $this->toolRepo->create([
            'name'                 => 'Tool Using Design',
            'slug'                 => 'tool-using-design',
            'engine'               => 'PHP',
            'frontend_design_slug' => 'in-use-design',
        ]);

        $this->assertEquals(1, $this->designRepo->getUsageCount('in-use-design'));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot delete design');
        $this->designRepo->delete($custom->getId());
    }

    public function testDesignDuplication(): void
    {
        $builtin = $this->designRepo->findBySlug('media-downloader-cards');
        $this->assertNotNull($builtin);

        $copy = $this->designRepo->duplicate($builtin->getId());
        $this->assertNotNull($copy);
        $this->assertNotEquals($builtin->getSlug(), $copy->getSlug());
        $this->assertStringContainsString('copy', $copy->getSlug());
        $this->assertFalse($copy->isBuiltin());
        $this->assertEquals($builtin->getTemplateHtml(), $copy->getTemplateHtml());
    }

    public function testCssScoperScopesCorrectly(): void
    {
        $css = "
            .card { background: red; }
            .card .btn { color: white; }
            @media (max-width: 768px) {
                .card { padding: 10px; }
            }
        ";

        $scoped = CssScoper::validateAndScope($css, 'my-design');
        $this->assertStringContainsString('.fwt-design-container[data-design="my-design"] .card', $scoped);
        $this->assertStringContainsString('@media (max-width: 768px)', $scoped);
    }

    public function testCssScoperRejectsDangerousConstructs(): void
    {
        $dangerousCases = [
            '@import url("https://evil.com/style.css");',
            '.box { behavior: url(x.htc); }',
            '.box { -moz-binding: url(x.xml#test); }',
            '.box { background: expression(alert(1)); }',
        ];

        foreach ($dangerousCases as $css) {
            try {
                CssScoper::validateAndScope($css, 'test');
                $this->fail("Expected InvalidArgumentException for dangerous CSS: {$css}");
            } catch (InvalidArgumentException $e) {
                $this->assertStringContainsString('prohibited construct', $e->getMessage());
            }
        }
    }

    public function testCssScoperRejectsGlobalEscapeSelectors(): void
    {
        $forbiddenSelectors = ['body', 'html', ':root', '*', 'header', 'footer', 'nav'];

        foreach ($forbiddenSelectors as $sel) {
            $css = "{$sel} { background: black !important; }";
            try {
                CssScoper::validateAndScope($css, 'test');
                $this->fail("Expected InvalidArgumentException for global selector: {$sel}");
            } catch (InvalidArgumentException $e) {
                $this->assertStringContainsString('is prohibited in frontend designs', $e->getMessage());
            }
        }
    }

    public function testSafeTemplateRendererEscapesHtmlAndPreventsBypass(): void
    {
        $template = '<h1>{{title}}</h1><p>{{content}}</p>';
        $context = [
            'title'   => '<script>alert(1)</script>',
            'content' => 'Quotes " & \' < > chars',
        ];

        $rendered = SafeTemplateRenderer::render($template, $context);
        $this->assertStringNotContainsString('<script>', $rendered);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $rendered);
        $this->assertStringContainsString('&quot; &amp; &#039; &lt; &gt;', $rendered);

        // Verify raw HTML bypass {{{ }}} or {{& }} is disarmed
        $bypassTmpl = '<div>{{{payload}}}</div><span>{{&payload}}</span>';
        $bypassCtx = ['payload' => '<img src=x onerror=alert(1)>'];
        $renderedBypass = SafeTemplateRenderer::render($bypassTmpl, $bypassCtx);
        $this->assertStringNotContainsString('<img', $renderedBypass);
        $this->assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $renderedBypass);
    }

    public function testSafeTemplateRendererEnforcesSafeUrlsOnDownloadBindings(): void
    {
        $template = '<a href="{{download_url}}">Download</a><img src="{{thumbnail}}">';

        // Safe URLs
        $safeCtx = [
            'download_url' => 'https://server.favoriteweb.net/files/video.mp4',
            'thumbnail'    => 'https://images.example.com/thumb.jpg',
        ];
        $safeRendered = SafeTemplateRenderer::render($template, $safeCtx);
        $this->assertStringContainsString('href="https://server.favoriteweb.net/files/video.mp4"', $safeRendered);
        $this->assertStringContainsString('src="https://images.example.com/thumb.jpg"', $safeRendered);

        // Unsafe URLs
        $unsafeCtx = [
            'download_url' => 'javascript:alert(document.cookie)',
            'thumbnail'    => 'data:text/html,<script>alert(1)</script>',
        ];
        $unsafeRendered = SafeTemplateRenderer::render($template, $unsafeCtx);
        $this->assertStringContainsString('href=""', $unsafeRendered);
        $this->assertStringContainsString('src=""', $unsafeRendered);
    }

    public function testSafeTemplateRendererConditionalsAndLoops(): void
    {
        $template = '
            {{#if has_items}}
                <ul>
                    {{#items}}
                        <li>{{@number}}: {{name}} ({{quality}})</li>
                    {{/items}}
                </ul>
            {{else}}
                <p>No items found.</p>
            {{/if}}
        ';

        $withItems = [
            'has_items' => true,
            'items'     => [
                ['name' => 'Stream 1', 'quality' => '1080p'],
                ['name' => 'Stream 2', 'quality' => '720p'],
            ],
        ];

        $renderedWith = SafeTemplateRenderer::render($template, $withItems);
        $this->assertStringContainsString('1: Stream 1 (1080p)', $renderedWith);
        $this->assertStringContainsString('2: Stream 2 (720p)', $renderedWith);

        $withoutItems = [
            'has_items' => false,
            'items'     => [],
        ];
        $renderedWithout = SafeTemplateRenderer::render($template, $withoutItems);
        $this->assertStringContainsString('No items found.', $renderedWithout);
    }

    public function testMediaResultNormalizerProcessesProductionPayload(): void
    {
        $normalizer = new MediaResultNormalizer();

        $prodPayload = [
            'title'           => 'Test Video Sample',
            'thumbnail'       => 'https://i.ytimg.com/vi/123/maxresdefault.jpg',
            'duration_string' => '1:00',
            'videos'          => [
                [
                    'formatId' => '18',
                    'quality'  => 360,
                    'ext'      => 'mp4',
                    'vcodec'   => 'H.264',
                    'filesize' => 5056781,
                    'hasAudio' => true,
                    'url'      => 'https://video.google.com/playback?itag=18',
                ],
                [
                    'formatId' => '137',
                    'quality'  => 1080,
                    'ext'      => 'mp4',
                    'vcodec'   => 'H.264',
                    'filesize' => 29081334,
                    'hasAudio' => false,
                    'url'      => 'https://video.google.com/playback?itag=137',
                ],
            ],
            'audios'          => [
                [
                    'formatId' => '140',
                    'bitrate'  => 129,
                    'ext'      => 'm4a',
                    'acodec'   => 'AAC',
                    'filesize' => 972659,
                    'url'      => 'https://video.google.com/playback?itag=140',
                ],
            ],
        ];

        $this->assertTrue($normalizer->supports('media', $prodPayload));
        $normalized = $normalizer->normalize($prodPayload);

        $this->assertTrue($normalized['success']);
        $this->assertEquals('Test Video Sample', $normalized['title']);
        $this->assertEquals('https://i.ytimg.com/vi/123/maxresdefault.jpg', $normalized['thumbnail']);
        $this->assertEquals('1:00', $normalized['duration']);
        $this->assertCount(3, $normalized['formats']);

        // Check sorted order: 1080p video should come before 360p video, audios last
        $this->assertEquals('1080p', $normalized['formats'][0]['quality']);
        $this->assertEquals('video', $normalized['formats'][0]['stream_type']);
        $this->assertTrue($normalized['formats'][0]['has_download_url']);
        $this->assertEquals('https://video.google.com/playback?itag=137', $normalized['formats'][0]['download_url']);

        $this->assertEquals('360p', $normalized['formats'][1]['quality']);
        $this->assertEquals('audio', $normalized['formats'][2]['stream_type']);
        $this->assertEquals('129 kbps', $normalized['formats'][2]['quality']);
    }

    public function testNormalizerResolverRouting(): void
    {
        $resolver = new NormalizerResolver();

        // Media payload routes to MediaResultNormalizer
        $mediaData = ['videos' => [['url' => 'https://example.com/v.mp4']]];
        $mediaResult = $resolver->normalize($mediaData);
        $this->assertEquals('media', $mediaResult['normalized_type']);

        // Text / generic payload routes to DefaultResultNormalizer
        $genericData = ['message' => 'Processed text successfully', 'items' => ['a', 'b', 'c']];
        $genericResult = $resolver->normalize($genericData);
        $this->assertEquals('default', $genericResult['normalized_type']);
        $this->assertEquals(3, $genericResult['item_count']);
    }

    public function testDesignPackageImporterWithZip(): void
    {
        $importer = new DesignPackageImporter($this->designRepo);

        // Create a temporary valid ZIP package
        $zipPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_design_' . bin2hex(random_bytes(4)) . '.zip';
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE));

        $manifest = [
            'name'        => 'Zip Imported Design',
            'slug'        => 'zip-imported-design',
            'version'     => '1.2.0',
            'category'    => 'Imported',
            'description' => 'A package tested via ZIP archive.',
        ];
        $zip->addFromString('design.json', json_encode($manifest));
        $zip->addFromString('template.html', '<div class="zip-card">{{title}} - {{download_url}}</div>');
        $zip->addFromString('styles.css', '.zip-card { border: 1px solid #ccc; padding: 10px; }');
        $zip->close();

        try {
            $imported = $importer->importFromZip($zipPath);
            $this->assertEquals('Zip Imported Design', $imported->getName());
            $this->assertEquals('zip-imported-design', $imported->getSlug());
            $this->assertFalse($imported->isBuiltin());
            $this->assertFalse($imported->isJsEnabled());
            $this->assertStringContainsString('.fwt-design-container[data-design="zip-imported-design"] .zip-card', $imported->getCssContent());
        } finally {
            @unlink($zipPath);
        }
    }

    public function testDesignPackageImporterRejectsExecutableFiles(): void
    {
        $importer = new DesignPackageImporter($this->designRepo);

        $zipPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_malicious_' . bin2hex(random_bytes(4)) . '.zip';
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE));

        $zip->addFromString('design.json', json_encode(['name' => 'Bad Package']));
        $zip->addFromString('template.html', '<div>bad</div>');
        $zip->addFromString('shell.php', '<?php phpinfo(); ?>');
        $zip->close();

        try {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage("Prohibited file type '.php'");
            $importer->importFromZip($zipPath);
        } finally {
            @unlink($zipPath);
        }
    }

    public function testEndToEndExecutionWithAssignedDesign(): void
    {
        $app = new Application();
        $app->instance(Database::class, $this->db);
        $app->instance(FrontendDesignRepository::class, $this->designRepo);
        $app->instance(ToolRepository::class, $this->toolRepo);
        $app->instance(NormalizerResolver::class, new NormalizerResolver());

        // Create tool with media-downloader-cards design
        $tool = $this->toolRepo->create([
            'name'                 => 'Mock Media Downloader',
            'slug'                 => 'mock-media-downloader',
            'engine'               => 'PHP',
            'frontend_design_slug' => 'media-downloader-cards',
            'access_mode'          => 'FREE',
            'status'               => 'ACTIVE',
        ]);

        // Mock execution service that returns media streams
        $mockExecutionService = $this->createMock(ToolExecutionService::class);
        $mockExecutionService->method('execute')->willReturn([
            'success' => true,
            'tool'    => ['name' => 'Mock Media Downloader', 'slug' => 'mock-media-downloader'],
            'data'    => [
                'type'  => 'JSON',
                'value' => [
                    'title'           => 'Amazing Nature 4K',
                    'thumbnail'       => 'https://images.example.com/thumb.jpg',
                    'duration_string' => '2:30',
                    'videos'          => [
                        [
                            'formatId' => '137',
                            'label'    => '1080p - MP4',
                            'quality'  => 1080,
                            'ext'      => 'mp4',
                            'filesize' => 15000000,
                            'url'      => 'https://server.favoriteweb.net/stream_1080.mp4',
                        ],
                    ],
                ],
            ],
        ]);

        $controller = new ToolExecutionApiController($app, $mockExecutionService);
        $request = new Request([], [], [], [], ['REQUEST_METHOD' => 'POST', 'CONTENT_TYPE' => 'application/json']);

        $response = $controller->execute($request, 'mock-media-downloader');
        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getContent(), true);
        $this->assertTrue($body['success']);
        $this->assertArrayHasKey('design', $body);
        $this->assertEquals('media-downloader-cards', $body['design']['slug']);
        $this->assertArrayHasKey('rendered_html', $body);
        $this->assertStringContainsString('Amazing Nature 4K', $body['rendered_html']);
        $this->assertStringContainsString('https://server.favoriteweb.net/stream_1080.mp4', $body['rendered_html']);
        $this->assertStringContainsString('1080p', $body['rendered_html']);
    }
}
