<?php

declare(strict_types=1);

namespace Lemonade\Image\Exceptions\Gd;

use function sprintf;

final class GdImageCreateException extends GdException
{
    public static function forTrueColorImage(int $width, int $height, string $reason = ''): self
    {
        return new self(sprintf(
            'Unable to create true color image %dx%d.%s',
            $width,
            $height,
            $reason !== '' ? ' ' . $reason : ''
        ));
    }

    public static function fromString(string $reason = ''): self
    {
        return new self('Unable to create image from string.' . ($reason !== '' ? ' ' . $reason : ''));
    }
}
