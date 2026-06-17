<?php

declare(strict_types=1);

namespace Lemonade\Image\Cache;

use Lemonade\Image\Context\ImageFileContext;
use Lemonade\Image\Detection\ImageFileInspector;
use Lemonade\Image\Detection\WebpSupportDetector;
use Lemonade\Image\Generator\AppGenerator;
use Lemonade\Image\Http\ImageResponseEmitter;
use Lemonade\Image\Http\ServerRequest;

use function file_get_contents;
use function filemtime;
use function strtotime;

/**
 * Handles browser and filesystem cache shortcuts for image responses.
 *
 * Resolves cache freshness, emits 304 responses and serves existing cached
 * image binaries when a valid cache file is available.
 *
 * @package     Lemonade
 * @subpackage  Image\Cache
 * @category    Cache
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
 */
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

        if (
            $clientTime < 1 ||
            !$this->fileInspector->exists(
                file: $file->getSourceFile(),
            )
        ) {
            return false;
        }

        $cacheFile = $this->resolveCacheFile(
            file: $file,
        );

        if (!$this->fileInspector->exists(
            file: $cacheFile,
        )) {
            return false;
        }

        $cacheTime = (int) @filemtime($cacheFile);
        if ($cacheTime === 0 || $clientTime < $cacheTime) {
            return false;
        }

        $this->responseEmitter->sendNotModified();

        return true;
    }

    public function sendCacheImageIfExists(ImageFileContext $file): bool
    {
        if (!$this->fileInspector->exists(
            file: $file->getSourceFile(),
        )) {
            return false;
        }

        $cacheFile = $this->resolveCacheFile(
            file: $file,
        );

        if (!$this->fileInspector->exists(
            file: $cacheFile,
        )) {
            return false;
        }

        $content = file_get_contents($cacheFile);
        if ($content === false) {
            return false;
        }

        $type = $this->resolveOutputType(
            file: $cacheFile,
        );

        if ($type === null) {
            return false;
        }

        $this->responseEmitter->sendBinary(
            content: $content,
            type: $type,
        );
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
}
