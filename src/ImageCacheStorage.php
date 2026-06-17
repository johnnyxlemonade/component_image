<?php

declare(strict_types=1);

namespace Lemonade\Image;

use Lemonade\Image\Exceptions\Image\ImageCacheException;
use Lemonade\Image\Exceptions\IOException;
use Lemonade\Image\Providers\WebpProvider;

/**
 * Handles filesystem operations related to generated image cache files.
 */
final class ImageCacheStorage
{
    public function createDirectoryForFile(ImageContext $context, string $file): void
    {
        try {
            $context->getFilesystem()->createDirForFile($file);
        } catch (IOException) {
            // silent by design
        }
    }

    public function deleteCache(ImageContext $context): void
    {
        try {
            $context->getFilesystem()->delete(
                $context->getDirectory()->getCache(),
            );
        } catch (IOException) {
            // silent by design
        }
    }

    public function saveVariant(ImageContext $context, ImageResult $result): void
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

        if (!WebpProvider::hasSupport()) {
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

    public function saveFallback(ImageContext $context, ImageResult $result): void
    {
        $png = $context->getMissingPng();
        $webp = $context->getMissingWebp();

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
