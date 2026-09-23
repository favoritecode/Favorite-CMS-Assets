<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Tests;

use FavoriteCMS\Core\Application;
use FavoriteCMS\Core\Database;
use FavoriteCMS\Core\Request;
use FavoriteCMS\Tools\Controllers\Admin\AdminToolController;
use FavoriteCMS\Tools\Controllers\Api\BulkMediaApiController;
use FavoriteCMS\Tools\Models\Tool;
use FavoriteCMS\Tools\Normalizers\MediaResultNormalizer;
use FavoriteCMS\Tools\Repositories\CategoryRepository;
use FavoriteCMS\Tools\Repositories\FrontendDesignRepository;
use FavoriteCMS\Tools\Repositories\PythonServiceRepository;
use FavoriteCMS\Tools\Repositories\ToolRepository;
use FavoriteCMS\Tools\Services\BulkMediaDownloadService;
use FavoriteCMS\Tools\Services\DesignPackageImporter;
use FavoriteCMS\Tools\Services\ToolExecutionService;
use FavoriteCMS\Tools\Support\SafeTemplateRenderer;
use PHPUnit\Framework\TestCase;
use ZipArchive;

class BulkMediaDownloaderTest extends TestCase
{
    private Database $db;
    private FrontendDesignRepository $designRepo;
    private ToolRepository $toolRepo;
    private CategoryRepository $catRepo;
    private PythonServiceRepository $pythonRepo;
    private BulkMediaDownloadService $bulkService;

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
        $this->bulkService = new BulkMediaDownloadService($this->pythonRepo);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['_test_raw_input']);
        parent::tearDown();
    }

    public function testParseBulkUrlsSingleAndMultiple(): void
    {
        // 1. Single URL
        $single = $this->bulkService->parseBulkUrls('https://www.youtube.com/watch?v=dQw4w9WgXcQ');
        $this->assertEquals(1, $single['total_input_count']);
        $this->assertEquals(1, $single['valid_count']);
        $this->assertEquals(0, $single['invalid_count']);
        $this->assertEquals(0, $single['duplicate_count']);
        $this->assertEquals(['https://www.youtube.com/watch?v=dQw4w9WgXcQ'], $single['valid_urls']);

        // 2. Multiline textarea input with various whitespace
        $multiline = "  https://www.youtube.com/watch?v=dQw4w9WgXcQ  \r\n\r\n" .
                     "https://vimeo.com/76979871\n" .
                     "https://instagram.com/reel/C12345/   https://tiktok.com/@user/video/123";

        $parsed = $this->bulkService->parseBulkUrls($multiline);
        $this->assertEquals(4, $parsed['total_input_count']);
        $this->assertEquals(4, $parsed['valid_count']);
        $this->assertCount(4, $parsed['valid_urls']);
        $this->assertContains('https://www.youtube.com/watch?v=dQw4w9WgXcQ', $parsed['valid_urls']);
        $this->assertContains('https://vimeo.com/76979871', $parsed['valid_urls']);

        // 3. Array input
        $arrayInput = [
            'https://youtube.com/watch?v=1',
            'https://youtube.com/watch?v=2',
        ];
        $parsedArray = $this->bulkService->parseBulkUrls($arrayInput);
        $this->assertEquals(2, $parsedArray['valid_count']);
    }

    public function testParseBulkUrlsDeduplicatesIdenticalUrls(): void
    {
        $input = "https://www.youtube.com/watch?v=dQw4w9WgXcQ\n" .
                 "https://www.youtube.com/watch?v=dQw4w9WgXcQ\n" .
                 "https://vimeo.com/76979871\n" .
                 "HTTPS://WWW.YOUTUBE.COM/WATCH?V=DQW4W9WGXCQ";

        $result = $this->bulkService->parseBulkUrls($input);
        $this->assertEquals(4, $result['total_input_count']);
        $this->assertEquals(2, $result['valid_count']);
        $this->assertEquals(2, $result['duplicate_count']);
        $this->assertCount(2, $result['valid_urls']);
    }

    public function testParseBulkUrlsEnforcesMaxUrlsLimit(): void
    {
        $input = "https://example.com/1\nhttps://example.com/2\nhttps://example.com/3\nhttps://example.com/4\nhttps://example.com/5";
        $result = $this->bulkService->parseBulkUrls($input, 3);

        $this->assertEquals(5, $result['total_input_count']);
        $this->assertEquals(3, $result['valid_count']);
        $this->assertEquals(2, $result['invalid_count']);
        $this->assertStringContainsString('Maximum URL limit (3) reached', $result['invalid_urls'][0]['reason']);
    }

    public function testValidateMediaUrlBlocksSsrfAndInvalidUrls(): void
    {
        // Loopback and localhost
        $this->assertNotNull($this->bulkService->validateMediaUrl('http://localhost/video.mp4'));
        $this->assertNotNull($this->bulkService->validateMediaUrl('http://127.0.0.1/video.mp4'));
        $this->assertNotNull($this->bulkService->validateMediaUrl('http://0.0.0.0/video.mp4'));

        // Private IP ranges (RFC 1918)
        $this->assertNotNull($this->bulkService->validateMediaUrl('http://10.0.0.1/video.mp4'));
        $this->assertNotNull($this->bulkService->validateMediaUrl('http://192.168.1.1/video.mp4'));
        $this->assertNotNull($this->bulkService->validateMediaUrl('http://172.16.0.1/video.mp4'));

        // Prohibited schemes
        $this->assertNotNull($this->bulkService->validateMediaUrl('file:///etc/passwd'));
        $this->assertNotNull($this->bulkService->validateMediaUrl('ftp://example.com/video.mp4'));
        $this->assertNotNull($this->bulkService->validateMediaUrl('javascript:alert(1)'));

        // Invalid formats
        $this->assertNotNull($this->bulkService->validateMediaUrl(''));
        $this->assertNotNull($this->bulkService->validateMediaUrl('not-a-valid-url'));

        // Legitimate public URLs should be null (valid)
        $this->assertNull($this->bulkService->validateMediaUrl('https://www.youtube.com/watch?v=dQw4w9WgXcQ'));
        $this->assertNull($this->bulkService->validateMediaUrl('https://vimeo.com/76979871'));
        $this->assertNull($this->bulkService->validateMediaUrl('https://instagram.com/reel/C12345/'));
    }

    public function testNormalizeUrlStandardizesInstagramAndSchemes(): void
    {
        // Missing scheme defaults to https
        $this->assertEquals('https://example.com/video.mp4', $this->bulkService->normalizeUrl('example.com/video.mp4'));

        // Instagram unwraps /reels/ to /reel/
        $this->assertEquals(
            'https://www.instagram.com/reel/C_12345/',
            $this->bulkService->normalizeUrl('https://www.instagram.com/reels/C_12345/?utm_source=ig_web_copy_link')
        );

        // Instagram redirect wrapper unwrapping
        $wrapped = 'https://l.instagram.com/?u=https%3A%2F%2Fwww.youtube.com%2Fwatch%3Fv%3Dtest123';
        $this->assertEquals(
            'https://www.youtube.com/watch?v=test123',
            $this->bulkService->normalizeUrl($wrapped)
        );
    }

    public function testMediaResultNormalizerBulkPayloadNormalization(): void
    {
        $normalizer = new MediaResultNormalizer();

        $bulkPayload = [
            'mode'    => 'bulk_media',
            'is_bulk' => true,
            'items'   => [
                [
                    'url'             => 'https://www.youtube.com/watch?v=sample1',
                    'title'           => 'Scenic 4K Nature Walk',
                    'thumbnail'       => 'https://i.ytimg.com/vi/sample1/maxresdefault.jpg',
                    'duration'        => 180,
                    'duration_string' => '3:00',
                    'videos'          => [
                        [
                            'formatId' => '137',
                            'quality'  => 1080,
                            'ext'      => 'mp4',
                            'filesize' => 45000000,
                            'hasAudio' => false,
                            'url'      => 'https://video.googlevideo.com/137',
                        ],
                        [
                            'formatId' => '18',
                            'quality'  => 360,
                            'ext'      => 'mp4',
                            'filesize' => 12000000,
                            'hasAudio' => true,
                            'url'      => 'https://video.googlevideo.com/18',
                        ],
                    ],
                ],
                [
                    'url'             => 'https://www.youtube.com/watch?v=sample2',
                    'title'           => 'City Lights at Night',
                    'thumbnail'       => 'https://i.ytimg.com/vi/sample2/maxresdefault.jpg',
                    'duration'        => 90,
                    'duration_string' => '1:30',
                    'videos'          => [
                        [
                            'formatId' => '22',
                            'quality'  => 720,
                            'ext'      => 'mp4',
                            'filesize' => 20000000,
                            'hasAudio' => true,
                            'url'      => 'https://video.googlevideo.com/22',
                        ],
                    ],
                ],
            ],
        ];

        $this->assertTrue($normalizer->supports('bulk_media', $bulkPayload));

        $normalized = $normalizer->normalize($bulkPayload);
        $this->assertTrue($normalized['success']);
        $this->assertTrue($normalized['is_bulk']);
        $this->assertEquals(2, $normalized['total_items']);
        $this->assertCount(2, $normalized['items']);

        // Verify item 0
        $item0 = $normalized['items'][0];
        $this->assertEquals('Scenic 4K Nature Walk', $item0['title']);
        $this->assertEquals('3:00', $item0['duration']);
        $this->assertTrue($item0['has_thumbnail']);
        $this->assertCount(2, $item0['formats']);
        $this->assertEquals('1080p', $item0['formats'][0]['quality']);
        $this->assertEquals('https://video.googlevideo.com/137', $item0['formats'][0]['download_url']);

        // Verify global qualities
        $this->assertArrayHasKey('global_qualities', $normalized);
        $this->assertContains('1080p', $normalized['global_qualities']);
        $this->assertContains('720p', $normalized['global_qualities']);
        $this->assertContains('360p', $normalized['global_qualities']);
    }

    public function testMediaResultNormalizerSingleVideoBackwardCompatibility(): void
    {
        $normalizer = new MediaResultNormalizer();

        $singlePayload = [
            'title'           => 'Single Video Asset',
            'thumbnail'       => 'https://example.com/thumb.jpg',
            'duration_string' => '1:05',
            'videos'          => [
                [
                    'formatId' => '18',
                    'quality'  => 360,
                    'ext'      => 'mp4',
                    'filesize' => 5000000,
                    'hasAudio' => true,
                    'url'      => 'https://example.com/video18.mp4',
                ],
            ],
        ];

        $this->assertTrue($normalizer->supports('media', $singlePayload));
        $normalized = $normalizer->normalize($singlePayload);

        $this->assertTrue($normalized['success']);
        $this->assertEquals('Single Video Asset', $normalized['title']);
        $this->assertEquals('https://example.com/thumb.jpg', $normalized['thumbnail']);
        $this->assertEquals('1:05', $normalized['duration']);
        $this->assertFalse($normalized['is_bulk']);
        $this->assertCount(1, $normalized['items']);
        $this->assertEquals($normalized['title'], $normalized['items'][0]['title']);
    }

    public function testMediaResultNormalizerRejectsGenericArray(): void
    {
        $normalizer = new MediaResultNormalizer();

        // Generic non-media array
        $genericData = ['items' => ['apple', 'banana', 'cherry']];
        $this->assertFalse($normalizer->supports('default', $genericData));
    }

    public function testBulkMediaApiControllerParseUrlsEndpoint(): void
    {
        $app = new Application();
        $controller = new BulkMediaApiController($app, $this->bulkService);

        // 1. Missing / Empty input
        $reqEmpty = new Request([], [], []);
        $resEmpty = $controller->parseUrls($reqEmpty);
        $this->assertEquals(422, $resEmpty->getStatusCode());

        // 2. Valid JSON payload
        $GLOBALS['_test_raw_input'] = json_encode([
            'urls' => "https://www.youtube.com/watch?v=111\nhttps://www.youtube.com/watch?v=222\nhttps://vimeo.com/333",
        ]);
        $reqValid = new Request([], [], []);
        $resValid = $controller->parseUrls($reqValid);
        $this->assertEquals(200, $resValid->getStatusCode());

        $body = json_decode($resValid->getContent(), true);
        $this->assertTrue($body['success']);
        $this->assertEquals(3, $body['valid_count']);
        $this->assertCount(3, $body['valid_urls']);
    }

    public function testBulkMediaApiControllerEndpointsWithMockService(): void
    {
        // Create an anonymous subclass to mock remote HTTP calls without network
        $mockService = new class($this->pythonRepo) extends BulkMediaDownloadService {
            public function discoverFormats(string $mediaUrl): array
            {
                return [
                    'title'           => 'Mocked Discovery Video',
                    'thumbnail'       => 'https://example.com/mock.jpg',
                    'duration_string' => '2:15',
                    'videos'          => [
                        [
                            'formatId' => '1080',
                            'quality'  => 1080,
                            'ext'      => 'mp4',
                            'filesize' => 10000000,
                            'url'      => 'https://example.com/mock-1080.mp4',
                        ],
                    ],
                ];
            }

            public function startDownloadJob(
                string $mediaUrl,
                ?string $formatId = null,
                string $type = 'video',
                bool $hasAudio = true,
                bool $convert = false
            ): array {
                return [
                    'success' => true,
                    'jobId'   => 'job_test_123456',
                ];
            }

            public function getJobStatus(string $jobId): array
            {
                return [
                    'status'   => 'completed',
                    'progress' => 100,
                    'file'     => 'job_test_123456.mp4',
                ];
            }

            public function getJobFileUrl(string $jobId): string
            {
                return 'https://server.favoriteweb.net/download/job-file/' . $jobId;
            }
        };

        $app = new Application();
        $controller = new BulkMediaApiController($app, $mockService);

        // 1. Get Formats
        $GLOBALS['_test_raw_input'] = json_encode(['url' => 'https://www.youtube.com/watch?v=testvid']);
        $resFormats = $controller->getFormats(new Request([], [], []));
        $this->assertEquals(200, $resFormats->getStatusCode());
        $formatsData = json_decode($resFormats->getContent(), true);
        $this->assertTrue($formatsData['success']);
        $this->assertEquals('Mocked Discovery Video', $formatsData['item']['title']);

        // 2. Start Job
        $GLOBALS['_test_raw_input'] = json_encode([
            'url'      => 'https://www.youtube.com/watch?v=testvid',
            'formatId' => '1080',
            'compat'   => 1,
        ]);
        $resJob = $controller->startJob(new Request([], [], []));
        $this->assertEquals(200, $resJob->getStatusCode());
        $jobData = json_decode($resJob->getContent(), true);
        $this->assertTrue($jobData['success']);
        $this->assertEquals('job_test_123456', $jobData['jobId']);

        // 3. Job Status
        $resStatus = $controller->getJobStatus(new Request([], [], []), 'job_test_123456');
        $this->assertEquals(200, $resStatus->getStatusCode());
        $statusData = json_decode($resStatus->getContent(), true);
        $this->assertEquals('completed', $statusData['status']);
        $this->assertEquals(100, $statusData['progress']);

        // 4. Job File
        $resFile = $controller->getJobFile(new Request([], [], []), 'job_test_123456');
        $this->assertEquals(302, $resFile->getStatusCode());
        $this->assertEquals('https://server.favoriteweb.net/download/job-file/job_test_123456', $resFile->getHeader('Location'));
    }

    public function testZipOnlyUiUpdateability(): void
    {
        // 1. Verify media-downloader-grid.zip exists in release/designs/
        $zipPath = dirname(__DIR__) . '/release/designs/media-downloader-grid.zip';
        $this->assertFileExists($zipPath, 'release/designs/media-downloader-grid.zip must exist');

        // 2. Import media-downloader-grid.zip into repository
        $importer = new DesignPackageImporter($this->designRepo);
        $imported = $importer->importFromZip($zipPath, true);

        $this->assertNotNull($imported);
        $this->assertEquals('media-downloader-grid', $imported->getSlug());
        $this->assertEquals('Media Downloader Grid', $imported->getName());

        // 3. Verify it is queryable from design repo
        $found = $this->designRepo->findBySlug('media-downloader-grid');
        $this->assertNotNull($found);

        // 4. Render template with sample bulk media context
        $sampleBulkData = [
            'total_items' => 1,
            'is_bulk'     => true,
            'items'       => [
                [
                    'id'            => '0',
                    'url'           => 'https://example.com/ocean.mp4',
                    'title'         => 'Ocean Waves 4K',
                    'has_thumbnail' => true,
                    'thumbnail'     => 'https://example.com/ocean.jpg',
                    'duration'      => '2:15',
                    'formats'       => [
                        [
                            'format_id'          => '1080',
                            'quality'            => '1080p',
                            'ext'                => 'MP4',
                            'filesize_formatted' => '35 MB',
                            'download_url'       => 'https://example.com/ocean-1080.mp4',
                            'stream_type'        => 'video',
                        ],
                    ],
                ],
            ],
        ];

        $rendered = SafeTemplateRenderer::render($found->getTemplateHtml(), $sampleBulkData);

        // Verify key structural contracts of the Grid layout
        $this->assertStringContainsString('fwt-grid-downloader', $rendered);
        $this->assertStringContainsString('fwt-grid-cards-layout', $rendered);
        $this->assertStringContainsString('data-fwt-input="urls"', $rendered);
        $this->assertStringContainsString('data-fwt-action="fast-download-all"', $rendered);
        $this->assertStringContainsString('data-fwt-action="fast-download"', $rendered);
        $this->assertStringContainsString('Ocean Waves 4K', $rendered);

        // 5. PROVE ZIP-ONLY UPDATEABILITY:
        // Create an updated version of the ZIP with a distinct new class & CSS rule
        // (without modifying any PHP or JS in the plugin codebase)
        $tempUpdateZip = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'media-downloader-grid-v2.zip';
        copy($zipPath, $tempUpdateZip);

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($tempUpdateZip));
        $manifest = json_decode($zip->getFromName('manifest.json'), true);
        $manifest['version'] = '2.0.0';
        $manifest['description'] = 'Updated Grid with neon futuristic styling';

        $template = $zip->getFromName('template.html');
        $updatedTemplate = str_replace(
            'class="fwt-grid-downloader"',
            'class="fwt-grid-downloader fwt-futuristic-grid-v2"',
            $template
        );

        $styles = $zip->getFromName('styles.css');
        $updatedStyles = $styles . "\n.fwt-futuristic-grid-v2 { border: 3px solid #a855f7 !important; }\n";

        $zip->addFromString('manifest.json', json_encode($manifest));
        $zip->addFromString('template.html', $updatedTemplate);
        $zip->addFromString('styles.css', $updatedStyles);
        $zip->close();

        try {
            // Re-import with overwrite = true
            $updatedImport = $importer->importFromZip($tempUpdateZip, true);
            $this->assertEquals('2.0.0', $updatedImport->getVersion());

            // Re-render and assert updated structure is immediately live
            $reRendered = SafeTemplateRenderer::render($updatedImport->getTemplateHtml(), $sampleBulkData);
            $this->assertStringContainsString('fwt-futuristic-grid-v2', $reRendered);
            $this->assertStringContainsString('.fwt-futuristic-grid-v2', $updatedImport->getCssContent());
        } finally {
            @unlink($tempUpdateZip);
        }
    }

    public function testPluginVersionMetadataConsistency(): void
    {
        $pluginDir = dirname(__DIR__);

        // 1. Check FavoriteWebToolsPlugin::VERSION constant
        $this->assertEquals('1.2.1', \FavoriteCMS\Tools\FavoriteWebToolsPlugin::VERSION);

        // 2. Check plugin.json
        $pluginJsonPath = $pluginDir . '/plugin.json';
        $this->assertFileExists($pluginJsonPath);
        $pluginJson = json_decode((string)file_get_contents($pluginJsonPath), true);
        $this->assertIsArray($pluginJson);
        $this->assertEquals('1.2.1', $pluginJson['version']);

        // 3. Check plugin.php header
        $pluginPhpPath = $pluginDir . '/plugin.php';
        $this->assertFileExists($pluginPhpPath);
        $pluginPhpContent = (string)file_get_contents($pluginPhpPath);
        $this->assertMatchesRegularExpression('/Version:\s*1\.2\.1/', $pluginPhpContent);
    }

    /**
     * Test A, B, C, E, F, G, H: Single URL discovery, format normalization, and card template rendering.
     */
    public function testSingleUrlDiscoveryFormatNormalizationAndCardRendering(): void
    {
        $testUrl = 'https://www.youtube.com/watch?v=NHWjhX0Fekk';

        // 1. Test A: Single URL parsing
        $parsed = $this->bulkService->parseBulkUrls($testUrl);
        $this->assertEquals(1, $parsed['valid_count']);
        $this->assertEquals([$testUrl], $parsed['valid_urls']);

        // 2. Simulated upstream response from GET https://server.favoriteweb.net/download/api?url=...
        $upstreamPayload = [
            'normalized_url' => $testUrl,
            'title'          => 'Sample Video Title',
            'thumbnail'      => 'https://i.ytimg.com/vi/NHWjhX0Fekk/hqdefault.jpg',
            'duration'       => 215,
            'channel'        => 'Test Channel',
            'videos'         => [
                [
                    'formatId' => '137',
                    'quality'  => '1080',
                    'ext'      => 'mp4',
                    'filesize' => 52428800,
                    'hasAudio' => '0',
                    'url'      => 'https://server.favoriteweb.net/download/stream?f=137',
                ],
                [
                    'formatId' => '18',
                    'quality'  => '360',
                    'ext'      => 'mp4',
                    'filesize' => 15728640,
                    'hasAudio' => '1',
                    'url'      => 'https://server.favoriteweb.net/download/stream?f=18',
                ],
            ],
            'audios'         => [
                [
                    'formatId' => '140',
                    'bitrate'  => 128,
                    'ext'      => 'm4a',
                    'filesize' => 3500000,
                    'url'      => 'https://server.favoriteweb.net/download/stream?f=140',
                ],
            ],
        ];

        // 3. Test B: Normalization on single item preserves normalized_url, url, and source_url
        $normalizer = new MediaResultNormalizer();
        $this->assertTrue($normalizer->supports('media-downloader', $upstreamPayload));

        $normalized = $normalizer->normalize($upstreamPayload);
        $this->assertEquals($testUrl, $normalized['url']);
        $this->assertEquals($testUrl, $normalized['source_url']);
        $this->assertEquals($testUrl, $normalized['normalized_url']);
        $this->assertCount(1, $normalized['items']);

        $item = $normalized['items'][0];
        $this->assertEquals($testUrl, $item['url']);
        $this->assertEquals($testUrl, $item['source_url']);
        $this->assertEquals($testUrl, $item['normalized_url']);
        $this->assertEquals('Sample Video Title', $item['title']);
        $this->assertTrue($item['has_thumbnail']);

        // 4. Test E, F, G, H: Format properties
        $this->assertCount(3, $item['formats']);

        // Video format 1 (1080p, no audio)
        $fmt1080 = $item['formats'][0];
        $this->assertEquals('137', $fmt1080['format_id']);
        $this->assertEquals('video', $fmt1080['stream_type']);
        $this->assertEquals('video', $fmt1080['type']);
        $this->assertEquals('video', $fmt1080['badge_class']);
        $this->assertEquals('1080p', $fmt1080['quality']);
        $this->assertFalse($fmt1080['has_audio']);
        $this->assertEquals('0', $fmt1080['has_audio_str']);

        // Video format 2 (360p, has audio)
        $fmt360 = $item['formats'][1];
        $this->assertEquals('18', $fmt360['format_id']);
        $this->assertEquals('video', $fmt360['stream_type']);
        $this->assertEquals('video', $fmt360['type']);
        $this->assertEquals('360p', $fmt360['quality']);
        $this->assertTrue($fmt360['has_audio']);
        $this->assertEquals('1', $fmt360['has_audio_str']);

        // Audio format (128 kbps)
        $fmtAudio = $item['formats'][2];
        $this->assertEquals('140', $fmtAudio['format_id']);
        $this->assertEquals('audio', $fmtAudio['stream_type']);
        $this->assertEquals('audio', $fmtAudio['type']);
        $this->assertEquals('audio', $fmtAudio['badge_class']);
        $this->assertTrue($fmtAudio['has_audio']);
        $this->assertEquals('1', $fmtAudio['has_audio_str']);

        // 5. Test C: Card template rendering with SafeTemplateRenderer
        $cardTemplate = '<div class="fwt-media-item-card" data-fwt-item-id="{{@index}}" data-fwt-url="{{url}}" data-fwt-status="ready"><select data-fwt-select="format">{{#formats}}<option value="{{format_id}}" data-stream="{{stream_type}}" data-has-audio="{{has_audio_str}}">{{label}}</option>{{/formats}}</select><button type="button" data-fwt-action="fast-download">Download</button></div>';

        $renderedCard = SafeTemplateRenderer::render($cardTemplate, $item);
        $this->assertStringContainsString('data-fwt-url="' . $testUrl . '"', $renderedCard);
        $this->assertStringNotContainsString('data-fwt-url=""', $renderedCard);
        $this->assertStringContainsString('data-fwt-action="fast-download"', $renderedCard);
        $this->assertStringContainsString('value="137"', $renderedCard);
    }

    /**
     * Test D: Multiple URLs discovery and distinct cards rendering.
     */
    public function testMultipleUrlsDiscoveryAndDistinctCardsRendering(): void
    {
        $url1 = 'https://www.youtube.com/watch?v=video_one_111';
        $url2 = 'https://www.youtube.com/watch?v=video_two_222';

        $normalizer = new MediaResultNormalizer();

        $item1 = $normalizer->normalizeItem([
            'normalized_url' => $url1,
            'title'          => 'First Video',
            'videos'         => [['formatId' => '22', 'quality' => '720', 'ext' => 'mp4']],
        ], 0);

        $item2 = $normalizer->normalizeItem([
            'normalized_url' => $url2,
            'title'          => 'Second Video',
            'videos'         => [['formatId' => '18', 'quality' => '360', 'ext' => 'mp4']],
        ], 1);

        $this->assertEquals($url1, $item1['url']);
        $this->assertEquals($url2, $item2['url']);
        $this->assertNotEquals($item1['url'], $item2['url']);

        $bulkPayload = [
            'is_bulk' => true,
            'items'   => [
                [
                    'normalized_url' => $url1,
                    'title'          => 'First Video',
                    'videos'         => [['formatId' => '22', 'quality' => '720', 'ext' => 'mp4']],
                ],
                [
                    'normalized_url' => $url2,
                    'title'          => 'Second Video',
                    'videos'         => [['formatId' => '18', 'quality' => '360', 'ext' => 'mp4']],
                ],
            ],
        ];

        $normalizedBulk = $normalizer->normalize($bulkPayload);
        $this->assertTrue($normalizedBulk['is_bulk']);
        $this->assertCount(2, $normalizedBulk['items']);

        $template = '{{#items}}<div class="fwt-card" data-fwt-item-id="{{@index}}" data-fwt-url="{{url}}"><h3>{{title}}</h3></div>{{/items}}';
        $rendered = SafeTemplateRenderer::render($template, $normalizedBulk);

        $this->assertStringContainsString('data-fwt-url="' . $url1 . '"', $rendered);
        $this->assertStringContainsString('data-fwt-url="' . $url2 . '"', $rendered);
        $this->assertStringContainsString('First Video', $rendered);
        $this->assertStringContainsString('Second Video', $rendered);
    }

    /**
     * Test I, J: AdminToolController provides active designs to create & edit forms.
     */
    public function testAdminToolControllerProvidesActiveDesignsToEditForm(): void
    {
        $app = new Application();
        $executionService = $this->createMock(ToolExecutionService::class);

        $adminToolController = new AdminToolController(
            $app,
            $this->toolRepo,
            $this->catRepo,
            $this->pythonRepo,
            $executionService,
            $this->designRepo
        );

        // Ensure active designs exist in repo
        $activeDesigns = $this->designRepo->allActive();
        $this->assertNotEmpty($activeDesigns);

        // Mock authorized current user
        $GLOBALS['_test_current_user'] = new class {
            public function can($perm): bool {
                return true;
            }
        };

        // Create tool for edit screen
        $tool = $this->toolRepo->create([
            'name'                  => 'Media Downloader Pro',
            'slug'                  => 'media-downloader-pro',
            'category_id'           => 1,
            'engine'                => 'PHP',
            'access_mode'           => 'FREE',
            'status'                => 'ACTIVE',
            'frontend_design_slug'  => 'media-downloader-cards',
        ]);

        $req = new Request(['action' => 'edit', 'id' => (string)$tool->id]);
        $response = $adminToolController->handle($req);
        $html = is_string($response) ? $response : $response->getContent();

        // Must render frontend_design_slug select element
        $this->assertStringContainsString('name="frontend_design_slug"', $html);
        $this->assertStringContainsString('id="fwt-frontend-design-select"', $html);

        // Must contain active designs as options
        $this->assertStringContainsString('Default Tool UI', $html);
        $this->assertStringContainsString('Media Downloader Cards', $html);
        $this->assertStringContainsString('Card Grid', $html);
        $this->assertStringContainsString('value="media-downloader-cards"', $html);
    }

    /**
     * Test K, L: BulkMediaApiController startJob validation and request contract.
     */
    public function testBulkMediaApiControllerStartJobValidationAndContract(): void
    {
        $app = new Application();
        $controller = new BulkMediaApiController($app, $this->bulkService);

        // 1. Missing URL returns 422
        $emptyReq = new Request([], []);
        $resp = $controller->startJob($emptyReq);
        $this->assertEquals(422, $resp->getStatusCode());
        $body = json_decode($resp->getContent(), true);
        $this->assertFalse($body['success']);
        $this->assertStringContainsString('Missing required media "url"', $body['error']);

        // 2. Fast download contract (type='video', hasAudio=true, compat=false)
        $fastReq = new Request([], [
            'url'      => 'https://www.youtube.com/watch?v=NHWjhX0Fekk',
            'format'   => '137',
            'type'     => 'video',
            'hasAudio' => '1',
            'compat'   => '0',
        ]);
        $fastResp = $controller->startJob($fastReq);
        $this->assertContains($fastResp->getStatusCode(), [200, 400, 502]);

        // 3. Convert & download contract (type='video', hasAudio=true, compat=true)
        $convertReq = new Request([], [
            'url'      => 'https://www.youtube.com/watch?v=NHWjhX0Fekk',
            'format'   => '137',
            'type'     => 'video',
            'hasAudio' => '1',
            'compat'   => '1',
        ]);
        $convertResp = $controller->startJob($convertReq);
        $this->assertContains($convertResp->getStatusCode(), [200, 400, 502]);
    }

    /**
     * Test M: BulkMediaApiController getFormats passes through normalized_url.
     */
    public function testBulkMediaApiControllerGetFormatsNormalizedUrlPassThrough(): void
    {
        $app = new Application();

        $mockService = $this->createMock(BulkMediaDownloadService::class);
        $mockService->method('validateMediaUrl')->willReturn(null);
        $mockService->method('discoverFormats')->willReturn([
            'title'     => 'Mock Video',
            'thumbnail' => 'https://example.com/thumb.jpg',
            'videos'    => [
                ['formatId' => '22', 'quality' => '720', 'ext' => 'mp4', 'hasAudio' => '1']
            ],
            'audios'    => [],
        ]);

        $controller = new BulkMediaApiController($app, $mockService, new MediaResultNormalizer());

        $req = new Request(['url' => 'https://www.youtube.com/watch?v=NHWjhX0Fekk']);
        $resp = $controller->getFormats($req);

        $this->assertEquals(200, $resp->getStatusCode());
        $data = json_decode($resp->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertEquals('https://www.youtube.com/watch?v=NHWjhX0Fekk', $data['data']['url']);
        $this->assertEquals('https://www.youtube.com/watch?v=NHWjhX0Fekk', $data['data']['normalized_url']);
        $this->assertEquals('https://www.youtube.com/watch?v=NHWjhX0Fekk', $data['data']['source_url']);
    }
}

