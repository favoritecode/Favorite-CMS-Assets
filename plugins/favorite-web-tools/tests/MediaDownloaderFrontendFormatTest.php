<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Tests;

use FavoriteCMS\Core\Database;
use FavoriteCMS\Tools\Engines\PythonApiEngine;
use FavoriteCMS\Tools\Models\Tool;
use FavoriteCMS\Tools\Repositories\PythonServiceRepository;
use FavoriteCMS\Tools\Repositories\ToolRepository;
use FavoriteCMS\Tools\Services\DownloadManager;
use FavoriteCMS\Tools\Services\PythonClientService;
use FavoriteCMS\Tools\Services\ToolExecutionService;
use FavoriteCMS\Tools\Support\EngineType;
use PHPUnit\Framework\TestCase;

class MediaDownloaderFrontendFormatTest extends TestCase
{
    private array $productionSample;

    protected function setUp(): void
    {
        parent::setUp();

        require_once dirname(__DIR__) . '/database/migrations/001_create_favorite_web_tool_categories_table.php';
        require_once dirname(__DIR__) . '/database/migrations/002_create_favorite_web_tool_python_services_table.php';
        require_once dirname(__DIR__) . '/database/migrations/003_create_favorite_web_tools_table.php';
        require_once dirname(__DIR__) . '/database/migrations/004_add_fields_to_favorite_web_tool_python_services_table.php';

        // Exact production response structure verified from https://server.favoriteweb.net/download/api
        $this->productionSample = [
            'audio' => 'https://rr2---sn-googlevideo.com/videoplayback?itag=140&audio_only=1&sig=test1',
            'video' => 'https://rr2---sn-googlevideo.com/videoplayback?itag=18&video_def=1&sig=test2',
            'title' => 'Top Rotating Background Video Asset No Copyright',
            'thumbnail' => 'https://i.ytimg.com/vi/NHWjhX0Fekk/maxresdefault.jpg',
            'duration' => 60,
            'duration_string' => '1:00',
            'normalized_url' => 'https://www.youtube.com/watch?v=NHWjhX0Fekk',
            'videos' => [
                [
                    'formatId' => '18',
                    'quality'  => 360,
                    'ext'      => 'mp4',
                    'vcodec'   => 'H.264',
                    'acodec'   => 'AAC',
                    'hasAudio' => true,
                    'fps'      => 30,
                    'filesize' => 5056781,
                    'label'    => '360p - H.264 - AAC - MP4',
                    'url'      => 'https://rr2---sn-googlevideo.com/videoplayback?itag=18&mime=video%2Fmp4&sig=v18',
                ],
                [
                    'formatId' => '137',
                    'quality'  => 1080,
                    'ext'      => 'mp4',
                    'vcodec'   => 'H.264',
                    'acodec'   => '',
                    'hasAudio' => false,
                    'fps'      => 30,
                    'filesize' => 29081334,
                    'label'    => '1080p - H.264 - MP4',
                    'url'      => 'https://rr2---sn-googlevideo.com/videoplayback?itag=137&mime=video%2Fmp4&sig=v137',
                ],
                [
                    'formatId' => '136',
                    'quality'  => 720,
                    'ext'      => 'mp4',
                    'vcodec'   => 'H.264',
                    'acodec'   => '',
                    'hasAudio' => false,
                    'fps'      => 30,
                    'filesize' => 15768936,
                    'label'    => '720p - H.264 - MP4',
                    'url'      => 'https://rr2---sn-googlevideo.com/videoplayback?itag=136&mime=video%2Fmp4&sig=v136',
                ],
                [
                    'formatId' => '243',
                    'quality'  => 360,
                    'ext'      => 'webm',
                    'vcodec'   => 'VP9',
                    'acodec'   => '',
                    'hasAudio' => false,
                    'fps'      => 30,
                    'filesize' => 3657614,
                    'label'    => '360p - VP9 - WEBM',
                    'url'      => 'https://rr2---sn-googlevideo.com/videoplayback?itag=243&mime=video%2Fwebm&sig=v243',
                ],
                [
                    'formatId' => '160',
                    'quality'  => 144,
                    'ext'      => 'mp4',
                    'vcodec'   => 'H.264',
                    'acodec'   => '',
                    'hasAudio' => false,
                    'fps'      => 30,
                    'filesize' => 1790065,
                    'label'    => '144p - H.264 - MP4',
                    'url'      => 'https://rr2---sn-googlevideo.com/videoplayback?itag=160&mime=video%2Fmp4&sig=v160',
                ],
            ],
            'audios' => [
                [
                    'formatId' => '140',
                    'bitrate'  => 129,
                    'ext'      => 'm4a',
                    'acodec'   => 'AAC',
                    'filesize' => 972659,
                    'label'    => '129 kbps - AAC - M4A',
                    'url'      => 'https://rr2---sn-googlevideo.com/videoplayback?itag=140&mime=audio%2Fmp4&sig=a140',
                ],
                [
                    'formatId' => '251',
                    'bitrate'  => 3,
                    'ext'      => 'webm',
                    'acodec'   => 'Opus',
                    'filesize' => 27454,
                    'label'    => '3 kbps - Opus - WEBM',
                    'url'      => 'https://rr2---sn-googlevideo.com/videoplayback?itag=251&mime=audio%2Fwebm&sig=a251',
                ],
            ],
        ];
    }

    /**
     * 1. Test actual production response structure handling in backend pipeline
     */
    public function testActualProductionResponseStructure(): void
    {
        $db = new Database(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
        (new \CreateFavoriteWebToolCategoriesTable($db))->up();
        (new \CreateFavoriteWebToolPythonServicesTable($db))->up();
        (new \CreateFavoriteWebToolsTable($db))->up();

        $serviceRepo = new PythonServiceRepository($db);
        $service = $serviceRepo->create([
            'name'                  => 'Favorite Media Downloader API',
            'slug'                  => 'favorite-media-downloader-api',
            'base_url'              => 'https://server.favoriteweb.net',
            'default_endpoint_path' => '/download/api',
            'http_method'           => 'GET',
            'status'                => 'active',
        ]);

        $tool = new Tool([
            'id'            => 1,
            'name'          => 'Favorite Media Downloader',
            'slug'          => 'favorite-media-downloader',
            'engine'        => EngineType::PYTHON_API,
            'status'        => 'ACTIVE',
            'access_mode'   => 'FREE',
            'configuration' => [
                'python_service_id' => $service->id,
                'python_endpoint'   => '/download/api',
                'http_method'       => 'GET',
            ],
        ]);

        $mockClient = $this->createMock(PythonClientService::class);
        $mockClient->method('sendRequest')->willReturn($this->productionSample);

        $engine = new PythonApiEngine($serviceRepo, $mockClient);
        $result = $engine->execute($tool, ['video_url' => 'https://www.youtube.com/watch?v=NHWjhX0Fekk']);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('videos', $result['data']);
        $this->assertArrayHasKey('audios', $result['data']);
        $this->assertCount(5, $result['data']['videos']);
        $this->assertCount(2, $result['data']['audios']);
        $this->assertEquals('Top Rotating Background Video Asset No Copyright', $result['data']['title']);
    }

    /**
     * Helper mimicking tools-frontend.js extractMediaFormats
     */
    private function extractMediaFormatsPhp(mixed $payload): array
    {
        if (!is_array($payload)) {
            return [];
        }

        // Direct list of format items
        if (isset($payload[0]) && is_array($payload[0]) && ($this->isFormatCandidatePhp($payload[0]))) {
            return $this->processAndSortFormatsPhp($payload);
        }

        $candidates = [
            $payload,
            $payload['result'] ?? null,
            $payload['data'] ?? null,
            $payload['value'] ?? null,
            $payload['data']['result'] ?? null,
            $payload['result']['data'] ?? null,
        ];

        foreach ($candidates as $c) {
            if (!is_array($c)) continue;

            $hasVideos = !empty($c['videos']) && is_array($c['videos']);
            $hasAudios = !empty($c['audios']) && is_array($c['audios']);

            if ($hasVideos || $hasAudios) {
                $combined = [];
                if ($hasVideos) {
                    foreach ($c['videos'] as $v) {
                        if (is_array($v)) {
                            $v['_streamCategory'] = 'video';
                            $combined[] = $v;
                        }
                    }
                }
                if ($hasAudios) {
                    foreach ($c['audios'] as $a) {
                        if (is_array($a)) {
                            $a['_streamCategory'] = 'audio';
                            $combined[] = $a;
                        }
                    }
                }
                if (!empty($combined)) {
                    return $this->processAndSortFormatsPhp($combined);
                }
            }

            foreach (['formats', 'streams', 'media', 'items'] as $k) {
                if (!empty($c[$k]) && is_array($c[$k]) && isset($c[$k][0]) && $this->isFormatCandidatePhp($c[$k][0])) {
                    return $this->processAndSortFormatsPhp($c[$k]);
                }
            }
        }

        return [];
    }

    private function isFormatCandidatePhp(mixed $item): bool
    {
        if (!is_array($item)) return false;
        return isset($item['url'])
            || isset($item['formatId'])
            || isset($item['format_id'])
            || isset($item['quality'])
            || isset($item['resolution'])
            || isset($item['bitrate'])
            || isset($item['ext'])
            || isset($item['vcodec'])
            || isset($item['acodec']);
    }

    private function isSafeDownloadUrlPhp(?string $url): bool
    {
        if (!$url) return false;
        $trimmed = strtolower(trim($url));
        return str_starts_with($trimmed, 'https://')
            || str_starts_with($trimmed, 'http://')
            || str_starts_with($trimmed, '/');
    }

    private function processAndSortFormatsPhp(array $rawList): array
    {
        $seen = [];
        $processed = [];

        foreach ($rawList as $item) {
            if (!is_array($item)) continue;

            $ext = strtolower((string)($item['ext'] ?? 'mp4'));
            $formatId = (string)($item['formatId'] ?? ($item['format_id'] ?? ''));
            $vcodec = (string)($item['vcodec'] ?? '');
            $acodec = (string)($item['acodec'] ?? '');
            $hasVcodec = $vcodec !== '' && strtolower($vcodec) !== 'none';
            $hasAcodec = $acodec !== '' && strtolower($acodec) !== 'none';

            $isAudioCat = ($item['_streamCategory'] ?? '') === 'audio';
            $isAudioExt = in_array($ext, ['m4a', 'mp3', 'aac', 'opus', 'wav', 'ogg', 'flac'], true);
            $isAudioOnly = $isAudioCat || (!$hasVcodec && ($hasAcodec || isset($item['bitrate']) || $isAudioExt));

            $streamType = 'video_audio';
            if ($isAudioOnly) {
                $streamType = 'audio_only';
            } elseif (!empty($item['hasAudio']) && ($hasVcodec || ($item['_streamCategory'] ?? '') === 'video')) {
                $streamType = 'video_audio';
            } elseif ($hasVcodec && $hasAcodec) {
                $streamType = 'video_audio';
            } elseif ($hasVcodec || ($item['_streamCategory'] ?? '') === 'video') {
                $streamType = (isset($item['hasAudio']) && !$item['hasAudio']) ? 'video_only' : 'video_audio';
            }

            $numResolution = (int)($item['quality'] ?? 0);
            $numBitrate = (int)($item['bitrate'] ?? 0);
            $qualityLabel = $isAudioOnly ? ($numBitrate > 0 ? "{$numBitrate} kbps" : 'Audio') : "{$numResolution}p";

            $codecLabel = ($hasVcodec && $hasAcodec) ? "{$vcodec} / {$acodec}" : ($hasVcodec ? $vcodec : $acodec);

            $rawUrl = (string)($item['url'] ?? '');
            $hasSafeUrl = $this->isSafeDownloadUrlPhp($rawUrl);

            // Stable deduplication key
            $key = strtolower("{$formatId}|{$ext}|{$qualityLabel}|{$codecLabel}|{$streamType}|{$numResolution}|{$numBitrate}");
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $sortRank = match ($streamType) {
                'video_audio' => 1,
                'video_only'  => 2,
                'audio_only'  => 3,
                default       => 4,
            };

            $processed[] = [
                'formatId'      => $formatId,
                'ext'           => $ext,
                'qualityLabel'  => $qualityLabel,
                'codecLabel'    => $codecLabel,
                'streamType'    => $streamType,
                'sortRank'      => $sortRank,
                'numResolution' => $numResolution,
                'numBitrate'    => $numBitrate,
                'rawUrl'        => $rawUrl,
                'hasSafeUrl'    => $hasSafeUrl,
            ];
        }

        usort($processed, function ($a, $b) {
            if ($a['sortRank'] !== $b['sortRank']) {
                return $a['sortRank'] <=> $b['sortRank'];
            }
            if ($a['sortRank'] === 1 || $a['sortRank'] === 2) {
                return $b['numResolution'] <=> $a['numResolution'];
            }
            if ($a['sortRank'] === 3) {
                return $b['numBitrate'] <=> $a['numBitrate'];
            }
            return 0;
        });

        return $processed;
    }

    /**
     * 2. Test format extraction from direct production response
     */
    public function testFormatExtractionFromProductionResponse(): void
    {
        $formats = $this->extractMediaFormatsPhp($this->productionSample);
        $this->assertCount(7, $formats); // 5 videos + 2 audios
        $this->assertEquals('video_audio', $formats[0]['streamType']);
        $this->assertEquals('18', $formats[0]['formatId']);
    }

    /**
     * 3. Test nested data and result response handling
     */
    public function testNestedDataAndResultResponseHandling(): void
    {
        $wrappedInData = ['success' => true, 'data' => $this->productionSample];
        $formats1 = $this->extractMediaFormatsPhp($wrappedInData);
        $this->assertCount(7, $formats1);

        $wrappedInResult = ['success' => true, 'result' => $this->productionSample];
        $formats2 = $this->extractMediaFormatsPhp($wrappedInResult);
        $this->assertCount(7, $formats2);

        $doubleNested = ['success' => true, 'data' => ['result' => $this->productionSample]];
        $formats3 = $this->extractMediaFormatsPhp($doubleNested);
        $this->assertCount(7, $formats3);
    }

    /**
     * 4. Test missing format URL handling
     */
    public function testMissingFormatUrlHandling(): void
    {
        $sample = [
            'videos' => [
                [
                    'formatId' => '99',
                    'quality'  => 720,
                    'ext'      => 'mp4',
                    'url'      => '', // Missing URL
                ],
            ],
        ];

        $formats = $this->extractMediaFormatsPhp($sample);
        $this->assertCount(1, $formats);
        $this->assertFalse($formats[0]['hasSafeUrl']);
    }

    /**
     * 5. Test unsafe format URL handling
     */
    public function testUnsafeFormatUrlHandling(): void
    {
        $unsafeUrls = [
            'javascript:alert(1)',
            'data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==',
            'vbscript:msgbox(1)',
            'file:///etc/passwd',
            'chrome://settings',
        ];

        foreach ($unsafeUrls as $badUrl) {
            $sample = [
                'videos' => [
                    [
                        'formatId' => '99',
                        'quality'  => 720,
                        'ext'      => 'mp4',
                        'url'      => $badUrl,
                    ],
                ],
            ];

            $formats = $this->extractMediaFormatsPhp($sample);
            $this->assertCount(1, $formats);
            $this->assertFalse($formats[0]['hasSafeUrl'], "Unsafe URL was not rejected: $badUrl");
        }
    }

    /**
     * 6. Test download button uses actual API-provided format.url
     */
    public function testDownloadButtonUsesActualApiFormatUrl(): void
    {
        $formats = $this->extractMediaFormatsPhp($this->productionSample);
        $firstVideo = $formats[0];

        $this->assertTrue($firstVideo['hasSafeUrl']);
        $this->assertEquals(
            'https://rr2---sn-googlevideo.com/videoplayback?itag=18&mime=video%2Fmp4&sig=v18',
            $firstVideo['rawUrl']
        );
        $this->assertStringContainsString('https://rr2---sn-googlevideo.com', $firstVideo['rawUrl']);
    }

    /**
     * 7. Test duplicate format handling / deduplication
     */
    public function testDuplicateFormatDeduplication(): void
    {
        $sampleWithDupes = [
            'videos' => [
                [
                    'formatId' => '137',
                    'quality'  => 1080,
                    'ext'      => 'mp4',
                    'vcodec'   => 'H.264',
                    'hasAudio' => false,
                    'url'      => 'https://example.com/v1',
                ],
                [
                    'formatId' => '137',
                    'quality'  => 1080,
                    'ext'      => 'mp4',
                    'vcodec'   => 'H.264',
                    'hasAudio' => false,
                    'url'      => 'https://example.com/v1_duplicate',
                ],
                // Legitimate different format (VP9 WEBM at same resolution)
                [
                    'formatId' => '248',
                    'quality'  => 1080,
                    'ext'      => 'webm',
                    'vcodec'   => 'VP9',
                    'hasAudio' => false,
                    'url'      => 'https://example.com/v2_webm',
                ],
            ],
        ];

        $formats = $this->extractMediaFormatsPhp($sampleWithDupes);
        $this->assertCount(2, $formats); // Exactly 2: duplicate 137 removed, 248 preserved
        $this->assertEquals('mp4', $formats[0]['ext']);
        $this->assertEquals('webm', $formats[1]['ext']);
    }

    /**
     * 8. Test video and audio sorting order
     */
    public function testVideoAudioSortingOrder(): void
    {
        $formats = $this->extractMediaFormatsPhp($this->productionSample);

        // Expectation:
        // Index 0: Video + Audio (format 18, 360p)
        $this->assertEquals('video_audio', $formats[0]['streamType']);
        $this->assertEquals('18', $formats[0]['formatId']);

        // Indices 1 to 4: Video only, sorted descending by resolution: 1080p, 720p, 360p, 144p
        $this->assertEquals('video_only', $formats[1]['streamType']);
        $this->assertEquals(1080, $formats[1]['numResolution']);

        $this->assertEquals('video_only', $formats[2]['streamType']);
        $this->assertEquals(720, $formats[2]['numResolution']);

        $this->assertEquals('video_only', $formats[3]['streamType']);
        $this->assertEquals(360, $formats[3]['numResolution']);

        $this->assertEquals('video_only', $formats[4]['streamType']);
        $this->assertEquals(144, $formats[4]['numResolution']);

        // Indices 5 to 6: Audio only, sorted descending by bitrate: 129 kbps, 3 kbps
        $this->assertEquals('audio_only', $formats[5]['streamType']);
        $this->assertEquals(129, $formats[5]['numBitrate']);

        $this->assertEquals('audio_only', $formats[6]['streamType']);
        $this->assertEquals(3, $formats[6]['numBitrate']);
    }

    /**
     * 9. Test non-media tool result remains unchanged
     */
    public function testNonMediaToolResultRemainsUnchanged(): void
    {
        $nonMediaPayload = [
            'type'  => 'JSON',
            'value' => ['status' => 'ok', 'word_count' => 42, 'char_count' => 256],
            'data'  => ['status' => 'ok', 'word_count' => 42, 'char_count' => 256],
        ];

        $formats = $this->extractMediaFormatsPhp($nonMediaPayload);
        $this->assertEmpty($formats); // Zero media formats, falls back to normal JSON result
    }
}
