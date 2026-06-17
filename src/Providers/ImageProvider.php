<?php

declare(strict_types=1);

namespace Lemonade\Image\Providers;

use Lemonade\Image\ImageFileInspector;
use Lemonade\Image\ImageGenerator;
use Lemonade\Image\ImageResponseEmitter;
use Lemonade\Image\ImageResult;

/**
 * @deprecated Use Lemonade\Image\ImageGenerator instead.
 */
final class ImageProvider
{
    public static function imageCreate(FileProvider $app): ImageResult
    {
        return self::createGenerator()->createVariant(
            provider: $app,
        );
    }

    public static function imageError(FileProvider $app): ImageResult
    {
        return self::createGenerator()->createFallback(
            provider: $app,
        );
    }

    /**
     * @deprecated Use ImageResponseEmitter::sendHeader() instead.
     */
    public static function sendHeader(?int $mime = null, int $size = 0): void
    {
        self::createResponseEmitter()->sendHeader(
            type: $mime,
            size: $size,
        );
    }

    /**
     * @deprecated Use ImageResponseEmitter::sendNotModified() instead.
     */
    public static function setNoModified(): void
    {
        self::createResponseEmitter()->sendNotModified();
    }

    /**
     * @deprecated Use ImageResponseEmitter::sendBinary() instead.
     */
    public static function sendContent(?string $content = null): never
    {
        self::createResponseEmitter()->sendBinary(
            content: $content ?? '',
            type: null,
        );
    }

    private static function createGenerator(): ImageGenerator
    {
        return new ImageGenerator(
            fileInspector: new ImageFileInspector(),
        );
    }

    private static function createResponseEmitter(): ImageResponseEmitter
    {
        return new ImageResponseEmitter();
    }
}
