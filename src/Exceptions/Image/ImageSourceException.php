<?php

declare(strict_types=1);

namespace Lemonade\Image\Exceptions\Image;

use function sprintf;

final class ImageSourceException extends ImageProcessingException
{
    public static function missingSourcePath(): self
    {
        return new self('Missing source image file path.');
    }

    public static function fileNotFound(string $file): self
    {
        return new self(sprintf('Source image file "%s" was not found.', $file));
    }
}
