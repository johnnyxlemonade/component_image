<?php

declare(strict_types=1);

namespace Lemonade\Image\Cache;

use Lemonade\Image\Context\ImageFileContext;
use Lemonade\Image\Detection\ImageFileInspector;
use Lemonade\Image\Detection\WebpSupportDetector;
use Lemonade\Image\Generator\AppGenerator;
use Lemonade\Image\Http\ImageResponseEmitter;
use Lemonade\Image\Http\ServerRequest;

use function filemtime;
use function strtotime;

final class ImageCacheResponder
{
    public function __construct(
        private readonly ImageResponseEmitter $responseEmitter,
        private readonly ImageFileInspector $fileInspector,
    ) {}

    public function sendBrowserCacheIfFresh(ImageFileContext $file): bool
    {
        if (!ServerRequest::has('HTTP_IF_MODIFIED_SINCE')) {
            return false;
        }

        $clientTime = (int) strtotime(
            ServerRequest::get('HTTP_IF_MODIFIED_SINCE'),
        );

        if ($clientTime < 1) {
            return false;
        }

        $cacheFile = $this->resolveFreshCacheFile(
            file: $file,
        );

        if ($cacheFile === null) {
            return false;
        }

        $cacheTime = $this->getFileMTime(
            file: $cacheFile,
        );

        if ($cacheTime === null || $clientTime < $cacheTime) {
            return false;
        }

        $this->responseEmitter->sendNotModified();
    }

    public function sendCacheImageIfExists(ImageFileContext $file): bool
    {
        $cacheFile = $this->resolveFreshCacheFile(
            file: $file,
        );

        if ($cacheFile === null) {
            return false;
        }

        $type = $this->resolveOutputType(
            file: $cacheFile,
        );

        if ($type === null) {
            return false;
        }

        $this->responseEmitter->sendFile(
            file: $cacheFile,
            type: $type,
        );
    }

    private function resolveFreshCacheFile(ImageFileContext $file): ?string
    {
        if (!$this->fileInspector->exists(
            file: $file->getSourceFile(),
        )) {
            return null;
        }

        $cacheFile = $this->resolveCacheFile(
            file: $file,
        );

        if (!$this->fileInspector->exists(
            file: $cacheFile,
        )) {
            return null;
        }

        if (!$this->isCacheFresh(
            sourceFile: $file->getSourceFile(),
            cacheFile: $cacheFile,
        )) {
            return null;
        }

        return $cacheFile;
    }

    private function resolveCacheFile(ImageFileContext $file): string
    {
        return WebpSupportDetector::hasSupport()
            ? $file->getCacheWebp()
            : $file->getCacheFile();
    }

    private function resolveOutputType(string $file): ?int
    {
        if (WebpSupportDetector::hasSupport()) {
            return AppGenerator::WEBP;
        }

        return AppGenerator::detectTypeFromFile(
            file: $file,
        );
    }

    private function isCacheFresh(string $sourceFile, string $cacheFile): bool
    {
        $sourceTime = $this->getFileMTime(
            file: $sourceFile,
        );

        $cacheTime = $this->getFileMTime(
            file: $cacheFile,
        );

        if ($sourceTime === null || $cacheTime === null) {
            return false;
        }

        return $cacheTime >= $sourceTime;
    }

    private function getFileMTime(string $file): ?int
    {
        $time = @filemtime($file);

        if ($time === false) {
            return null;
        }

        return $time;
    }
}
