<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Services;

use RuntimeException;

class DownloadManagerService
{
    protected string $storageDir;

    public function __construct(?string $storageDir = null)
    {
        if ($storageDir !== null) {
            $this->storageDir = $storageDir;
        } elseif (function_exists('storage_path')) {
            $this->storageDir = storage_path('temp/tools');
        } else {
            $this->storageDir = sys_get_temp_dir() . '/favorite_web_tools';
        }

        if (!is_dir($this->storageDir)) {
            @mkdir($this->storageDir, 0755, true);
        }
    }

    /**
     * Store content as a temporary downloadable file and return reference metadata.
     *
     * @param string $content
     * @param string $filename
     * @param string $mimeType
     * @return array{reference: string, filename: string, size: int, download_url: string}
     */
    public function createDownload(string $content, string $filename = 'result.txt', string $mimeType = 'text/plain'): array
    {
        $this->cleanupOldFiles();

        // Sanitize filename to prevent path traversal
        $safeName = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', basename($filename));
        if ($safeName === '' || $safeName === '.') {
            $safeName = 'result.txt';
        }

        $token = bin2hex(random_bytes(16));
        $storedName = $token . '_' . $safeName;
        $filePath = $this->storageDir . '/' . $storedName;

        file_put_contents($filePath, $content);

        $downloadUrl = '/api/tools/download/' . $token;

        return [
            'reference'    => $token,
            'filename'     => $safeName,
            'size'         => strlen($content),
            'mime_type'    => $mimeType,
            'download_url' => $downloadUrl,
        ];
    }

    /**
     * Resolve a download by reference token.
     *
     * @return array{path: string, filename: string, mime_type: string, size: int}|null
     */
    public function resolveDownload(string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{32}$/', $token)) {
            return null;
        }

        $files = glob($this->storageDir . '/' . $token . '_*');
        if (empty($files) || !file_exists($files[0])) {
            return null;
        }

        $path = $files[0];
        $storedName = basename($path);
        $originalFilename = substr($storedName, 33); // skip token and underscore

        $mime = 'application/octet-stream';
        $ext = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
        if ($ext === 'txt') $mime = 'text/plain';
        elseif ($ext === 'html') $mime = 'text/html';
        elseif ($ext === 'css') $mime = 'text/css';
        elseif ($ext === 'js') $mime = 'application/javascript';
        elseif ($ext === 'json') $mime = 'application/json';
        elseif ($ext === 'zip') $mime = 'application/zip';

        return [
            'path'      => $path,
            'filename'  => $originalFilename,
            'mime_type' => $mime,
            'size'      => filesize($path),
        ];
    }

    public function getFile(string $token): ?array
    {
        return $this->resolveDownload($token);
    }

    /**
     * Retrieve download info including file contents.
     *
     * @return array{path: string, filename: string, mime_type: string, size: int, content: string}|null
     */
    public function getDownload(string $token): ?array
    {
        $resolved = $this->resolveDownload($token);
        if ($resolved !== null && file_exists($resolved['path'])) {
            $resolved['content'] = (string)file_get_contents($resolved['path']);
        }
        return $resolved;
    }

    /**
     * Delete temporary download file by reference token.
     */
    public function deleteDownload(string $token): bool
    {
        if (!preg_match('/^[a-f0-9]{32}$/', $token)) {
            return false;
        }

        $files = glob($this->storageDir . '/' . $token . '_*');
        $deleted = false;
        foreach ($files ?: [] as $file) {
            if (is_file($file)) {
                @unlink($file);
                $deleted = true;
            }
        }
        return $deleted;
    }

    protected function cleanupOldFiles(): void
    {
        // Delete files older than 2 hours
        $files = glob($this->storageDir . '/*');
        $now = time();
        foreach ($files ?: [] as $file) {
            if (is_file($file) && ($now - filemtime($file) > 7200)) {
                @unlink($file);
            }
        }
    }
}
