<?php

declare(strict_types=1);

namespace Lemonade\Image\Exceptions\Gd;

use function sprintf;

final class GdImageLoadException extends GdException
{
    public static function fromFile(string $file, string $reason = ''): self
    {
        return new self(sprintf(
            'Unable to create image from file "%s".%s',
            $file,
            $reason !== '' ? ' ' . $reason : ''
        ));
    }
}
