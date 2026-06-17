<?php

declare(strict_types=1);

namespace Lemonade\Image;

use Lemonade\Image\Providers\FileProvider;
use Lemonade\Image\Providers\ServerProvider;
use Lemonade\Image\Providers\WebpProvider;

use function file_get_contents;
use function filemtime;
use function strtotime;

/**
 * Handles browser and filesystem cache shortcuts for image responses.
 */
final class ImageCacheResponder
{
    public function __construct(
        private readonly ImageResponseEmitter $responseEmitter,
    ) {}

    public function sendBrowserCacheIfFresh(FileProvider $provider): bool
    {
        if (!ServerProvider::has('HTTP_IF_MODIFIED_SINCE')) {
            return false;
        }

        $clientTime = (int) strtotime(
            ServerProvider::get('HTTP_IF_MODIFIED_SINCE'),
        );

        if ($clientTime < 1 || !$provider->isFileExists($provider->getFileFs())) {
            return false;
        }

        $cacheFile = $this->resolveCacheFile($provider);
        if ($cacheFile === null || !$provider->isFileExists($cacheFile)) {
            return false;
        }

        $cacheTime = (int) @filemtime($cacheFile);
        if ($cacheTime === 0 || $clientTime < $cacheTime) {
            return false;
        }

        $this->responseEmitter->sendNotModified();

        return true;
    }

    public function sendCacheImageIfExists(FileProvider $provider): bool
    {
        if (!$provider->isFileExists($provider->getFileFs())) {
            return false;
        }

        $cacheFile = $this->resolveCacheFile($provider);
        if ($cacheFile === null || !$provider->isFileExists($cacheFile)) {
            return false;
        }

        $content = file_get_contents($cacheFile);
        if ($content === false) {
            return false;
        }

        $type = $this->resolveOutputType($cacheFile);
        if ($type === null) {
            return false;
        }

        $this->responseEmitter->sendBinary(
            content: $content,
            type: $type,
        );
    }

    private function resolveCacheFile(FileProvider $provider): ?string
    {
        return WebpProvider::hasSupport()
            ? $provider->getCacheWebp()
            : $provider->getCacheFile();
    }

    private function resolveOutputType(string $file): ?int
    {
        if (WebpProvider::hasSupport()) {
            return AppGenerator::WEBP;
        }

        return AppGenerator::detectTypeFromFile($file);
    }
}
