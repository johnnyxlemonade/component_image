<?php

declare(strict_types=1);

namespace Lemonade\Image\Cache;

use Lemonade\Image\Context\ImageContext;
use Lemonade\Image\Detection\WebpSupportDetector;
use Lemonade\Image\Exceptions\IOException;
use Lemonade\Image\Generator\AppGenerator;
use Lemonade\Image\ImageResult;
use Throwable;

/**
 * Stores generated image variants and fallback images in filesystem cache.
 *
 * Treats cache writes and cleanup as best-effort operations so cache failures
 * do not prevent image responses from being emitted.
 *
 * @package     Lemonade
 * @subpackage  Image\Cache
 * @category    Cache
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
 */
final class ImageCacheStorage
{
    public function createDirectoryForFile(ImageContext $context, string $file): void
    {
        try {
            $context->getFilesystem()->createDirForFile(
                file: $file,
            );
        } catch (IOException) {
            // Cache directory creation failure must not prevent image response.
        }
    }

    public function deleteCache(ImageContext $context): void
    {
        try {
            $context->getFilesystem()->delete(
                path: $context->getDirectory()->getCache(),
            );
        } catch (IOException) {
            // Cache cleanup failure must not prevent image response.
        }
    }

    public function saveVariant(ImageContext $context, ImageResult $result): void
    {
        try {
            $this->doSaveVariant(
                context: $context,
                result: $result,
            );
        } catch (Throwable) {
            // Cache write failure must not prevent image response.
        }
    }

    public function saveFallback(ImageContext $context, ImageResult $result): void
    {
        try {
            $this->doSaveFallback(
                context: $context,
                result: $result,
            );
        } catch (Throwable) {
            // Fallback cache write failure must not prevent fallback response.
        }
    }

    private function doSaveVariant(ImageContext $context, ImageResult $result): void
    {
        $cacheFile = $context->getCacheFile();
        $cacheWebp = $context->getCacheWebp();

        $this->createDirectoryForFile(
            context: $context,
            file: $cacheFile,
        );

        $image = $result->getImage();
        $quality = $result->getQuality();
        $type = $result->getType();

        if (!WebpSupportDetector::hasSupport()) {
            $image->save(
                file: $cacheFile,
                quality: $quality,
                type: $type,
                filesystem: $context->getFilesystem(),
            );

            return;
        }

        if ($type === AppGenerator::PNG) {
            $image->paletteToTrueColor();
        }

        $image->save(
            file: $cacheWebp,
            quality: $quality,
            type: AppGenerator::WEBP,
            filesystem: $context->getFilesystem(),
        );
    }

    private function doSaveFallback(ImageContext $context, ImageResult $result): void
    {
        $png = $context->getFallbackPng();
        $webp = $context->getFallbackWebp();

        $this->createDirectoryForFile(
            context: $context,
            file: $png,
        );

        $image = $result->getImage();
        $quality = $result->getQuality();
        $type = $result->getType();

        $image->save(
            file: $png,
            quality: $quality,
            type: AppGenerator::PNG,
            filesystem: $context->getFilesystem(),
        );

        if ($type === AppGenerator::WEBP) {
            $image->paletteToTrueColor();

            $image->save(
                file: $webp,
                quality: $quality,
                type: AppGenerator::WEBP,
                filesystem: $context->getFilesystem(),
            );
        }
    }
}
