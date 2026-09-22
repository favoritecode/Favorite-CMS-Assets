<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Controllers\Api;

use FavoriteCMS\Core\Application;
use FavoriteCMS\Core\Request;
use FavoriteCMS\Core\Response;
use FavoriteCMS\Tools\Services\DownloadManagerService;

class DownloadApiController
{
    protected Application $app;
    protected DownloadManagerService $downloadManager;

    public function __construct(Application $app, DownloadManagerService $downloadManager)
    {
        $this->app = $app;
        $this->downloadManager = $downloadManager;
    }

    public function download(Request $request, string $reference): Response
    {
        $fileInfo = $this->downloadManager->resolveDownload($reference);
        if ($fileInfo === null) {
            return Response::make('<h1>404 Not Found</h1><p>The download link is invalid or has expired.</p>', 404);
        }

        $filePath = $fileInfo['path'];
        if (!is_file($filePath)) {
            return Response::make('<h1>404 Not Found</h1><p>The file is no longer available on the server.</p>', 404);
        }

        $filename = $fileInfo['filename'];
        $mime = $fileInfo['mime_type'];
        $size = filesize($filePath);
        $content = (string)file_get_contents($filePath);

        $headers = [
            'Content-Description'       => 'File Transfer',
            'Content-Type'              => $mime,
            'Content-Disposition'        => 'attachment; filename="' . str_replace('"', '', $filename) . '"',
            'Content-Transfer-Encoding' => 'binary',
            'Expires'                   => '0',
            'Cache-Control'             => 'must-revalidate, post-check=0, pre-check=0',
            'Pragma'                    => 'public',
            'Content-Length'            => (string)$size,
        ];

        return new Response($content, 200, $headers);
    }
}

