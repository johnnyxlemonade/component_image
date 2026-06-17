<?php

declare(strict_types=1);

namespace Lemonade\Image;

use Lemonade\Image\Exceptions\IOException;

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
}
