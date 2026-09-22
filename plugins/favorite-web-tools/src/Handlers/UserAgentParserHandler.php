<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Handlers;

use FavoriteCMS\Tools\Support\ResultType;

class UserAgentParserHandler extends AbstractToolHandler
{
    public function getId(): string
    {
        return 'user_agent_parser';
    }

    public function getName(): string
    {
        return 'User Agent Parser';
    }

    public function execute(array $inputs, array $config = []): array
    {
        $ua = trim((string)($inputs['input'] ?? $inputs['user_agent'] ?? $_SERVER['HTTP_USER_AGENT'] ?? ''));

        if ($ua === '') {
            return [
                'success' => false,
                'error'   => 'User agent string cannot be empty.',
                'type'    => ResultType::JSON,
                'data'    => null,
                'value'   => null,
            ];
        }

        $parsed = $this->parseUserAgent($ua);

        return [
            'success' => true,
            'type'    => ResultType::JSON,
            'data'    => $parsed,
            'value'   => json_encode($parsed, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            'meta'    => [
                'is_bot'      => $parsed['is_bot'],
                'browser'     => $parsed['browser'],
                'os'          => $parsed['operating_system'],
                'device_type' => $parsed['device_type'],
            ],
        ];
    }

    protected function parseUserAgent(string $ua): array
    {
        $isBot = false;
        $botName = null;

        // Bot detection
        $botPatterns = [
            'Googlebot' => 'Googlebot',
            'Bingbot' => 'Bingbot',
            'Slurp' => 'Yahoo! Slurp',
            'DuckDuckBot' => 'DuckDuckBot',
            'Baiduspider' => 'Baiduspider',
            'YandexBot' => 'YandexBot',
            'Sogou' => 'Sogou',
            'Exabot' => 'Exabot',
            'facebot' => 'Facebook External Hit',
            'facebookexternalhit' => 'Facebook External Hit',
            'ia_archiver' => 'Alexa Crawler',
            'Twitterbot' => 'Twitterbot',
            'LinkedInBot' => 'LinkedInBot',
            'Applebot' => 'Applebot',
        ];

        foreach ($botPatterns as $key => $name) {
            if (stripos($ua, $key) !== false) {
                $isBot = true;
                $botName = $name;
                break;
            }
        }

        // Operating System detection
        $os = 'Unknown OS';
        $osVersion = null;
        if (preg_match('/Windows NT ([0-9.]+)/i', $ua, $matches)) {
            $os = 'Windows';
            $verMap = [
                '10.0' => '10 / 11',
                '6.3'  => '8.1',
                '6.2'  => '8',
                '6.1'  => '7',
                '6.0'  => 'Vista',
                '5.2'  => 'Server 2003',
                '5.1'  => 'XP',
            ];
            $osVersion = $verMap[$matches[1]] ?? $matches[1];
        } elseif (preg_match('/Mac OS X ([0-9_\.]+)/i', $ua, $matches)) {
            $os = 'macOS';
            $osVersion = str_replace('_', '.', $matches[1]);
        } elseif (preg_match('/Android[ \/]([0-9.]+)/i', $ua, $matches)) {
            $os = 'Android';
            $osVersion = $matches[1];
        } elseif (preg_match('/(?:iPhone|iPad|iPod).*?OS ([0-9_\.]+)/i', $ua, $matches)) {
            $os = 'iOS';
            $osVersion = str_replace('_', '.', $matches[1]);
        } elseif (stripos($ua, 'Linux') !== false) {
            $os = 'Linux';
        }

        // Browser detection
        $browser = 'Unknown Browser';
        $browserVersion = null;

        if (preg_match('/Edg(?:e)?\/([0-9.]+)/i', $ua, $matches)) {
            $browser = 'Microsoft Edge';
            $browserVersion = $matches[1];
        } elseif (preg_match('/OPR\/([0-9.]+)/i', $ua, $matches) || preg_match('/Opera\/([0-9.]+)/i', $ua, $matches)) {
            $browser = 'Opera';
            $browserVersion = $matches[1];
        } elseif (preg_match('/Chrome\/([0-9.]+)/i', $ua, $matches) && stripos($ua, 'Safari') !== false) {
            $browser = 'Google Chrome';
            $browserVersion = $matches[1];
        } elseif (preg_match('/Firefox\/([0-9.]+)/i', $ua, $matches)) {
            $browser = 'Mozilla Firefox';
            $browserVersion = $matches[1];
        } elseif (preg_match('/Version\/([0-9.]+).*?Safari/i', $ua, $matches)) {
            $browser = 'Apple Safari';
            $browserVersion = $matches[1];
        } elseif (preg_match('/MSIE ([0-9.]+)/i', $ua, $matches) || preg_match('/Trident\/.*?rv:([0-9.]+)/i', $ua, $matches)) {
            $browser = 'Internet Explorer';
            $browserVersion = $matches[1];
        }

        // Device Type detection
        $deviceType = 'Desktop';
        if ($isBot) {
            $deviceType = 'Bot';
        } elseif (stripos($ua, 'Tablet') !== false || stripos($ua, 'iPad') !== false) {
            $deviceType = 'Tablet';
        } elseif (stripos($ua, 'Mobile') !== false || stripos($ua, 'iPhone') !== false || stripos($ua, 'Android') !== false) {
            $deviceType = 'Mobile';
        }

        return [
            'browser'          => $browser,
            'browser_version'  => $browserVersion,
            'operating_system' => $os,
            'os_version'       => $osVersion,
            'device_type'      => $deviceType,
            'is_bot'           => $isBot,
            'bot_name'         => $botName,
            'raw_user_agent'   => $ua,
        ];
    }
}

