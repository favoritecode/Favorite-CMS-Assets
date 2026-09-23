<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Normalizers;

class MediaResultNormalizer implements ResultNormalizerInterface
{
    public function supports(string $type, array $data): bool
    {
        if (in_array(strtolower($type), ['media', 'video', 'audio', 'media-downloader', 'media-downloader-cards', 'bulk_media', 'media-downloader-bulk', 'media-downloader-grid'], true)) {
            return true;
        }

        // Auto-detect based on payload keys
        if (!empty($data['videos']) || !empty($data['audios']) || !empty($data['formats']) || !empty($data['is_bulk'])) {
            return true;
        }

        if (isset($data['video']) || isset($data['audio']) || isset($data['download_url']) || isset($data['video_url']) || isset($data['normalized_url']) || isset($data['webpage_url']) || isset($data['original_url'])) {
            return true;
        }

        // Check if items array contains media stream dictionaries
        if (!empty($data['items']) && is_array($data['items'])) {
            $first = reset($data['items']);
            if (is_array($first) && (
                !empty($first['videos']) ||
                !empty($first['audios']) ||
                !empty($first['formats']) ||
                !empty($first['video_url']) ||
                !empty($first['download_url']) ||
                isset($first['formatId']) ||
                isset($first['hasAudio'])
            )) {
                return true;
            }
        }

        return false;
    }

    public function normalize(array $data): array
    {
        // Bulk items normalization
        if (!empty($data['is_bulk']) || (isset($data['items']) && is_array($data['items']))) {
            $rawItems = $data['items'] ?? [];
            $normalizedItems = [];
            foreach ($rawItems as $idx => $item) {
                if (is_array($item)) {
                    $normItem = $this->normalizeItem($item, (int)$idx);
                    $normItem['@first'] = ($idx === 0);
                    $normItem['@last'] = ($idx === count($rawItems) - 1);
                    $normalizedItems[] = $normItem;
                }
            }

            $totalItems = count($normalizedItems);
            $first = $normalizedItems[0] ?? null;

            return [
                'success'           => true,
                'mode'              => 'bulk_media',
                'is_bulk'           => true,
                'total_items'       => $totalItems,
                'has_items'         => $totalItems > 0,
                'items'             => $normalizedItems,
                'global_qualities'  => ['1080p', '720p', '480p', '360p', 'audio'],
                'url'               => $first ? $first['url'] : '',
                'source_url'        => $first ? $first['source_url'] : '',
                'normalized_url'    => $first ? $first['normalized_url'] : '',
                'title'             => $first ? $first['title'] : 'Bulk Media Download',
                'thumbnail'         => $first ? $first['thumbnail'] : '',
                'has_thumbnail'     => $first ? $first['has_thumbnail'] : false,
                'duration'          => $first ? $first['duration'] : '',
                'has_duration'      => $first ? $first['has_duration'] : false,
                'channel'           => $first ? $first['channel'] : '',
                'has_channel'       => $first ? $first['has_channel'] : false,
                'formats'           => $first ? $first['formats'] : [],
                'total_formats'     => $first ? $first['total_formats'] : 0,
                'has_formats'       => $first ? $first['has_formats'] : false,
                'normalized_type'   => 'bulk_media',
                'raw_json'          => json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            ];
        }

        // Single media payload
        $single = $this->normalizeItem($data, 0);
        $single['@first'] = true;
        $single['@last'] = true;

        return [
            'success'         => true,
            'mode'            => 'single_media',
            'is_bulk'         => false,
            'total_items'     => 1,
            'has_items'       => true,
            'items'           => [$single],
            'url'             => $single['url'],
            'source_url'      => $single['source_url'],
            'normalized_url'  => $single['normalized_url'],
            'title'           => $single['title'],
            'thumbnail'       => $single['thumbnail'],
            'has_thumbnail'   => $single['has_thumbnail'],
            'duration'        => $single['duration'],
            'has_duration'    => $single['has_duration'],
            'channel'         => $single['channel'],
            'has_channel'     => $single['has_channel'],
            'formats'         => $single['formats'],
            'total_formats'   => $single['total_formats'],
            'has_formats'     => $single['has_formats'],
            'normalized_type' => 'media',
            'raw_json'        => json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        ];
    }

    /**
     * Normalize a single video/audio item into standard item dictionary.
     */
    public function normalizeItem(array $data, int $index = 0): array
    {
        $title = (string) ($data['title'] ?? $data['name'] ?? 'Media Download');
        $thumbnail = $this->sanitizeSafeUrl((string) ($data['thumbnail'] ?? $data['thumb'] ?? $data['image'] ?? ''));
        $durationFormatted = $this->resolveDuration($data);
        $channel = (string) ($data['channel'] ?? $data['uploader'] ?? $data['author'] ?? '');
        $sourceUrl = (string) (
            $data['url'] ??
            $data['video_url'] ??
            $data['source_url'] ??
            $data['normalized_url'] ??
            $data['webpage_url'] ??
            $data['original_url'] ??
            ''
        );

        $formats = [];

        // 1. Process explicit 'videos'
        if (isset($data['videos']) && is_array($data['videos'])) {
            foreach ($data['videos'] as $fIdx => $v) {
                if (!is_array($v)) {
                    continue;
                }
                $fmt = $this->buildFormatItem($v, 'video', $fIdx);
                if ($fmt !== null) {
                    $formats[] = $fmt;
                }
            }
        }

        // 2. Process explicit 'audios'
        if (isset($data['audios']) && is_array($data['audios'])) {
            foreach ($data['audios'] as $fIdx => $a) {
                if (!is_array($a)) {
                    continue;
                }
                $fmt = $this->buildFormatItem($a, 'audio', $fIdx);
                if ($fmt !== null) {
                    $formats[] = $fmt;
                }
            }
        }

        // 3. Process generic 'formats' if present
        if (empty($formats) && isset($data['formats']) && is_array($data['formats'])) {
            foreach ($data['formats'] as $fIdx => $f) {
                if (!is_array($f)) {
                    continue;
                }
                $streamType = !empty($f['is_audio']) || (isset($f['vcodec']) && $f['vcodec'] === 'none') ? 'audio' : 'video';
                $fmt = $this->buildFormatItem($f, $streamType, $fIdx);
                if ($fmt !== null) {
                    $formats[] = $fmt;
                }
            }
        }

        // 4. Fallback for single top-level video/audio/download_url
        if (empty($formats)) {
            $singleUrl = $data['video'] ?? $data['download_url'] ?? $data['url'] ?? $data['video_url'] ?? null;
            if (is_string($singleUrl) && !empty($singleUrl)) {
                $fmt = $this->buildFormatItem([
                    'url'      => $singleUrl,
                    'label'    => 'Standard Quality',
                    'quality'  => 'Standard',
                    'ext'      => pathinfo(parse_url($singleUrl, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION) ?: 'mp4',
                    'hasAudio' => true,
                ], 'video', 0);
                if ($fmt !== null) {
                    $formats[] = $fmt;
                }
            }

            $singleAudio = $data['audio'] ?? $data['audio_url'] ?? null;
            if (is_string($singleAudio) && !empty($singleAudio)) {
                $fmt = $this->buildFormatItem([
                    'url'     => $singleAudio,
                    'label'   => 'Audio Track',
                    'quality' => 'Audio',
                    'ext'     => pathinfo(parse_url($singleAudio, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION) ?: 'mp3',
                ], 'audio', count($formats));
                if ($fmt !== null) {
                    $formats[] = $fmt;
                }
            }
        }

        // 5. Deduplicate formats
        $deduped = [];
        $seenKeys = [];
        foreach ($formats as $fmt) {
            $key = $fmt['stream_type'] . '|' . $fmt['quality'] . '|' . $fmt['ext'] . '|' . $fmt['download_url'];
            if (!isset($seenKeys[$key])) {
                $seenKeys[$key] = true;
                $deduped[] = $fmt;
            }
        }

        // 6. Sort: videos first (high quality to low), then audios
        usort($deduped, function (array $a, array $b) {
            if ($a['stream_type'] !== $b['stream_type']) {
                return $a['stream_type'] === 'video' ? -1 : 1;
            }
            if ($a['stream_type'] === 'video') {
                $qa = (int) preg_replace('/\D/', '', $a['quality']);
                $qb = (int) preg_replace('/\D/', '', $b['quality']);
                if ($qa !== $qb) {
                    return $qb <=> $qa;
                }
            }
            $fa = $a['filesize'] ?? 0;
            $fb = $b['filesize'] ?? 0;
            return $fb <=> $fa;
        });

        // Add 1-based index and loop flags
        foreach ($deduped as $i => &$item) {
            $item['@index'] = $i;
            $item['@number'] = $i + 1;
            $item['@first'] = ($i === 0);
            $item['@last'] = ($i === count($deduped) - 1);
        }
        unset($item);

        return [
            'id'            => (string)$index,
            'item_id'       => (string)$index,
            'url'           => $sourceUrl,
            'source_url'    => $sourceUrl,
            'normalized_url'=> $sourceUrl,
            'title'         => $title,
            'thumbnail'     => $thumbnail,
            'has_thumbnail' => !empty($thumbnail),
            'duration'      => $durationFormatted,
            'has_duration'  => !empty($durationFormatted),
            'channel'       => $channel,
            'has_channel'   => !empty($channel),
            'formats'       => $deduped,
            'total_formats' => count($deduped),
            'has_formats'   => count($deduped) > 0,
            'status'        => 'ready',
            'status_label'  => 'Ready',
            'progress'      => 0,
            'error'         => '',
            'has_error'     => false,
            '@index'        => $index,
            '@number'       => $index + 1,
        ];
    }

    private function buildFormatItem(array $raw, string $streamType, int $index): ?array
    {
        $rawUrl = (string) ($raw['url'] ?? $raw['download_url'] ?? $raw['src'] ?? '');
        $downloadUrl = $this->sanitizeSafeUrl($rawUrl);
        $hasDownloadUrl = !empty($downloadUrl);

        $formatId = (string) ($raw['formatId'] ?? $raw['itag'] ?? $raw['id'] ?? (string) $index);
        $ext = strtoupper((string) ($raw['ext'] ?? ($streamType === 'audio' ? 'MP3' : 'MP4')));

        $quality = '';
        if ($streamType === 'video') {
            // Some upstream responses include a numeric quality/height value of 0
            // while the real resolution is available in qualityLabel, height,
            // resolution, or the human-readable label. Never expose 0/0p as a
            // selectable quality when a better resolution signal exists.
            $qualityCandidates = [
                $raw['qualityLabel'] ?? null,
                $raw['quality_label'] ?? null,
                $raw['quality'] ?? null,
                $raw['height'] ?? null,
                $raw['resolution'] ?? null,
            ];

            foreach ($qualityCandidates as $candidate) {
                if ($candidate === null || $candidate === '') {
                    continue;
                }

                $candidateString = trim((string) $candidate);
                if ($candidateString === '' || preg_match('/^(?:0|0p|0x0)$/i', $candidateString)) {
                    continue;
                }

                if (is_numeric($candidate) && (int) $candidate > 0) {
                    $quality = ((int) $candidate) . 'p';
                    break;
                }

                if (preg_match('/(\d{3,4})\s*p\b/i', $candidateString, $m)) {
                    $quality = $m[1] . 'p';
                    break;
                }

                if (preg_match('/(?:^|x)(\d{3,4})(?:$|p?)/i', $candidateString, $m)) {
                    $height = (int) $m[1];
                    if ($height > 0) {
                        $quality = $height . 'p';
                        break;
                    }
                }
            }

            if ($quality === '' && preg_match('/(\d{3,4})\s*p\b/i', (string) ($raw['label'] ?? ''), $m)) {
                $quality = $m[1] . 'p';
            }

            if ($quality === '') {
                $quality = 'HD';
            }
        } else {
            if (isset($raw['bitrate']) && is_numeric($raw['bitrate']) && (int) $raw['bitrate'] > 0) {
                $quality = ((int) $raw['bitrate']) . ' kbps';
            } elseif (preg_match('/(\d+\s*kbps)/i', (string) ($raw['label'] ?? ''), $m)) {
                $quality = $m[1];
            } else {
                $quality = 'Audio';
            }
        }

        $codec = (string) ($raw['vcodec'] ?? $raw['acodec'] ?? $raw['codec'] ?? '');
        $filesize = (int) ($raw['filesize'] ?? 0);
        $filesizeFormatted = $filesize > 0 ? $this->formatFilesize($filesize) : '';

        $label = (string) ($raw['label'] ?? "{$quality} - {$ext}");

        $hasAudio = false;
        if (isset($raw['hasAudio'])) {
            $hasAudio = filter_var($raw['hasAudio'], FILTER_VALIDATE_BOOLEAN);
        } elseif ($streamType === 'audio') {
            $hasAudio = true;
        } elseif (!empty($raw['acodec']) && $raw['acodec'] !== 'none') {
            $hasAudio = true;
        }

        // Expose a stable alias set so imported frontend designs can use either
        // the canonical fields or simple id/label/quality names. This is especially
        // important for single-item rendering, where older designs may expect
        // format.id instead of format.format_id.
        $qualityHeight = 0;
        if (preg_match('/(\d{3,4})\s*p\b/i', $quality, $qm)) {
            $qualityHeight = (int) $qm[1];
        }

        return [
            'id' => $formatId,
            'format_id' => $formatId,
            'formatId' => $formatId,
            'format' => $formatId,
            'label' => $label,
            'quality_label' => $quality,
            'qualityLabel' => $quality,
            'stream_type' => $streamType,
            'type' => $streamType,
            'badge_class' => $streamType === 'audio' ? 'audio' : 'video',
            'quality' => $quality,
            'resolution' => $quality,
            'height' => $qualityHeight,
            'ext' => $ext,
            'codec' => $codec,
            'filesize' => $filesize,
            'filesize_formatted' => $filesizeFormatted,
            'has_filesize' => !empty($filesizeFormatted),
            'download_url' => $downloadUrl,
            'url' => $downloadUrl,
            'has_download_url' => $hasDownloadUrl,
            'has_audio' => $hasAudio,
            'has_audio_str' => $hasAudio ? '1' : '0',
            'hasAudio' => $hasAudio ? '1' : '0',
            'fps' => (int) ($raw['fps'] ?? 0),
        ];
    }

    private function resolveDuration(array $data): string
    {
        if (!empty($data['duration_string']) && is_string($data['duration_string'])) {
            return trim($data['duration_string']);
        }

        if (isset($data['duration']) && is_numeric($data['duration'])) {
            $seconds = (int) round((float) $data['duration']);
            $hours = floor($seconds / 3600);
            $mins = floor(($seconds % 3600) / 60);
            $secs = $seconds % 60;
            if ($hours > 0) {
                return sprintf('%d:%02d:%02d', $hours, $mins, $secs);
            }
            return sprintf('%d:%02d', $mins, $secs);
        }

        return '';
    }

    private function formatFilesize(int $bytes): string
    {
        if ($bytes <= 0) {
            return '';
        }
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = (int) floor(log($bytes, 1024));
        $i = min($i, count($units) - 1);
        $val = round($bytes / pow(1024, $i), 1);
        return "{$val} {$units[$i]}";
    }

    private function sanitizeSafeUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        // Only allow absolute http/https URLs or root-relative paths
        if (preg_match('#^https?://#i', $url) || (str_starts_with($url, '/') && !str_starts_with($url, '//'))) {
            // Disallow embedded credentials, javascript:, data:
            if (preg_match('#[<>"\'\s]#', $url)) {
                return '';
            }
            return $url;
        }

        return '';
    }
}

