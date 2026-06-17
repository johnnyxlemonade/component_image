<?php

declare(strict_types=1);

namespace Lemonade\Image\Exceptions\Gd;

final class GdImageCopyException extends GdException
{
    public static function copy(string $reason = ''): self
    {
        return new self('Unable to copy image.' . ($reason !== '' ? ' ' . $reason : ''));
    }

    public static function resample(string $reason = ''): self
    {
        return new self('Unable to resample image.' . ($reason !== '' ? ' ' . $reason : ''));
    }
}
