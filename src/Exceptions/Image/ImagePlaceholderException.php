<?php

declare(strict_types=1);

namespace Lemonade\Image\Exceptions\Image;

final class ImagePlaceholderException extends ImageProcessingException
{
    public static function createFailed(): self
    {
        return new self('Unable to create fallback error image.');
    }

    public static function colorAllocationFailed(): self
    {
        return new self('Unable to allocate fallback error image color.');
    }

    public static function renderFailed(): self
    {
        return new self('Unable to render fallback error image.');
    }
}
