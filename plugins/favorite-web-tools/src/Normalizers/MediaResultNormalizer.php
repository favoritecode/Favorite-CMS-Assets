<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Normalizers;

class MediaResultNormalizer implements ResultNormalizerInterface
{
    public function supports(string $type, array $data): bool
    {
        if (in_array(strtolower($type), ['media', 'video', 'audio', 'media-downloader', 'media-downloader-cards'], true)) {
            return true;
        }

        // Auto-detect based on payload keys
        if (!empty($data['videos']) || !empty($data['audios']) || !empty($data['formats'])) {
            return true;
        }

        if (isset($data['video']) || isset($data['audio']) || isset($data['download_url']) || isset($data['video_url'])) {
            return true;
        }

        return false;
    }

    public function normalize(array $data): array
    {
        $title = (string) ($data['title'] ?? $data['name'] ?? 'Media Download');
        $thumbnail = $this->sanitizeSafeUrl((string) ($data['thumbnail'] ?? $data['thumb'] ?? $data['image'] ?? ''));
        $durationFormatted = $this->resolveDuration($data);
        $channel = (string) ($data['channel'] ?? $data['uploader'] ?? $data['author'] ?? '');

        $formats = [];

        // 1. Process explicit 'videos'
        if (isset($data['videos']) && is_array($data['videos'])) {
            foreach ($data['videos'] as $index => $v) {
                if (!is_array($v)) {
                    continue;
                }
                $fmt = $this->buildFormatItem($v, 'video', $index);
                if ($fmt !== null) {
                    $formats[] = $fmt;
                }
            }
        }

        // 2. Process explicit 'audios'
        if (isset($data['audios']) && is_array($data['audios'])) {
            foreach ($data['audios'] as $index => $a) {
                if (!is_array($a)) {
                    continue;
                }
                $fmt = $this->buildFormatItem($a, 'audio', $index);
                if ($fmt !== null) {
                    $formats[] = $fmt;
                }
            }
        }

        // 3. Process generic 'formats' if present
        if (empty($formats) && isset($data['formats']) && is_array($data['formats'])) {
            foreach ($data['formats'] as $index => $f) {
                if (!is_array($f)) {
                    continue;
                }
                $streamType = !empty($f['is_audio']) || (isset($f['vcodec']) && $f['vcodec'] === 'none') ? 'audio' : 'video';
                $fmt = $this->buildFormatItem($f, $streamType, $index);
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
                    'url' => $singleUrl,
                    'label' => 'Standard Quality',
                    'quality' => 'Standard',
                    'ext' => pathinfo(parse_url($singleUrl, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION) ?: 'mp4',
                    'hasAudio' => true,
                ], 'video', 0);
                if ($fmt !== null) {
                    $formats[] = $fmt;
                }
            }

            $singleAudio = $data['audio'] ?? $data['audio_url'] ?? null;
            if (is_string($singleAudio) && !empty($singleAudio)) {
                $fmt = $this->buildFormatItem([
                    'url' => $singleAudio,
                    'label' => 'Audio Track',
                    'quality' => 'Audio',
                    'ext' => pathinfo(parse_url($singleAudio, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION) ?: 'mp3',
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
            // For video: compare numeric quality descending
            if ($a['stream_type'] === 'video') {
                $qa = (int) preg_replace('/\D/', '', $a['quality']);
                $qb = (int) preg_replace('/\D/', '', $b['quality']);
                if ($qa !== $qb) {
                    return $qb <=> $qa;
                }
            }
            // Secondary sort: filesize descending
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
            'success' => true,
            'title' => $title,
            'thumbnail' => $thumbnail,
            'has_thumbnail' => !empty($thumbnail),
            'duration' => $durationFormatted,
            'has_duration' => !empty($durationFormatted),
            'channel' => $channel,
            'has_channel' => !empty($channel),
            'formats' => $deduped,
            'total_formats' => count($deduped),
            'has_formats' => count($deduped) > 0,
            'normalized_type' => 'media',
            'raw_json' => json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
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
            if (isset($raw['quality']) && $raw['quality'] !== '') {
                $quality = is_numeric($raw['quality']) ? "{$raw['quality']}p" : (string) $raw['quality'];
            } elseif (preg_match('/(\d{3,4}p)/i', (string) ($raw['label'] ?? ''), $m)) {
                $quality = $m[1];
            } else {
                $quality = 'HD';
            }
        } else {
            if (isset($raw['bitrate']) && $raw['bitrate'] > 0) {
                $quality = "{$raw['bitrate']} kbps";
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
        $hasAudio = !empty($raw['hasAudio']) || ($streamType === 'audio');

        return [
            'format_id' => $formatId,
            'label' => $label,
            'stream_type' => $streamType,
            'badge_class' => $streamType === 'audio' ? 'audio' : 'video',
            'quality' => $quality,
            'ext' => $ext,
            'codec' => $codec,
            'filesize' => $filesize,
            'filesize_formatted' => $filesizeFormatted,
            'has_filesize' => !empty($filesizeFormatted),
            'download_url' => $downloadUrl,
            'has_download_url' => $hasDownloadUrl,
            'has_audio' => $hasAudio,
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

