<?php

declare(strict_types=1);

namespace Lemonade\Image\Exceptions\Image;

final class ImageCacheException extends ImageProcessingException
{
    public static function missingCachePath(): self
    {
        return new self('Missing cache file path.');
    }

    public static function missingErrorCachePath(): self
    {
        return new self('Missing error cache file path.');
    }
}
