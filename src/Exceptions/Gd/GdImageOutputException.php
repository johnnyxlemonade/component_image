<?php

declare(strict_types=1);

namespace Lemonade\Image\Exceptions\Gd;

final class GdImageOutputException extends GdException
{
    public static function output(string $reason = ''): self
    {
        return new self('Image output failed.' . ($reason !== '' ? ' ' . $reason : ''));
    }
}
