<?php

declare(strict_types=1);

namespace Lemonade\Image\Providers;

use Lemonade\Image\ImageGenerator;
use Lemonade\Image\ImageResult;

/**
 * @deprecated Use Lemonade\Image\ImageGenerator instead.
 */
final class ImageProvider
{
    public static function imageCreate(FileProvider $app): ImageResult
    {
        return (new ImageGenerator())->createVariant($app);
    }

    public static function imageError(FileProvider $app): ImageResult
    {
        return (new ImageGenerator())->createFallback($app);
    }

    /**
     * @deprecated Use Lemonade\Image\ImageResponseEmitter::sendHeader() instead.
     */
    public static function sendHeader(?int $mime = null, int $size = 0): void
    {
        (new \Lemonade\Image\ImageResponseEmitter())->sendHeader(
            type: $mime,
            size: $size,
        );
    }

    /**
     * @deprecated Use Lemonade\Image\ImageResponseEmitter::sendNotModified() instead.
     */
    public static function setNoModified(): void
    {
        (new \Lemonade\Image\ImageResponseEmitter())->sendNotModified();
    }

    /**
     * @deprecated Use Lemonade\Image\ImageResponseEmitter::sendBinary() instead.
     */
    public static function sendContent(?string $content = null): never
    {
        (new \Lemonade\Image\ImageResponseEmitter())->sendBinary(
            content: $content ?? '',
            type: null,
        );
    }
}
