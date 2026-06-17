<?php

declare(strict_types=1);

namespace Lemonade\Image\Exceptions\Image;

final class ImageRenderException extends ImageProcessingException
{
    public static function failed(): self
    {
        return new self('Image rendering failed.');
    }

    public static function outputBufferFailed(): self
    {
        return new self('Unable to capture output buffer.');
    }

    public static function paletteToTrueColorFailed(string $reason = ''): self
    {
        return new self('Unable to convert palette image to true color.' . ($reason !== '' ? ' ' . $reason : ''));
    }
}
