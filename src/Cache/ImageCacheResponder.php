<?php

declare(strict_types=1);

namespace Lemonade\Image\Cache;

use Lemonade\Image\Context\ImageFileContext;
use Lemonade\Image\Detection\ImageFileInspector;
use Lemonade\Image\Detection\WebpSupportDetector;
use Lemonade\Image\Generator\AppGenerator;
use Lemonade\Image\Http\ImageHttpResponse;
use Lemonade\Image\Http\ServerRequest;

use function filemtime;
use function strtotime;

final class ImageCacheResponder
{
    public function __construct(
        private readonly ImageFileInspector $fileInspector,
    ) {}

    public function createNotModifiedResponseIfFresh(ImageFileContext $file): ?ImageHttpResponse
    {
        if (!ServerRequest::has('HTTP_IF_MODIFIED_SINCE')) {
            return null;
        }

        $clientTime = (int) strtotime(
            ServerRequest::get('HTTP_IF_MODIFIED_SINCE'),
        );

        if ($clientTime < 1) {
            return null;
        }

        $cacheFile = $this->resolveFreshCacheFile(
            file: $file,
        );

        if ($cacheFile === null) {
            return null;
        }

        $cacheTime = $this->getFileMTime(
            file: $cacheFile,
        );

        if ($cacheTime === null || $clientTime < $cacheTime) {
            return null;
        }

        return ImageHttpResponse::notModified();
    }

    public function createCacheResponseIfExists(ImageFileContext $file): ?ImageHttpResponse
    {
        $cacheFile = $this->resolveFreshCacheFile(
            file: $file,
        );

        if ($cacheFile === null) {
            return null;
        }

        $type = $this->resolveOutputType(
            file: $cacheFile,
        );

        if ($type === null) {
            return null;
        }

        return ImageHttpResponse::file(
            file: $cacheFile,
            type: $type,
            lastModified: $this->getFileMTime(
                file: $cacheFile,
            ),
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
