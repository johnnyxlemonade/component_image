<?php

declare(strict_types=1);

namespace Lemonade\Image\Exceptions\Gd;

final class GdImageAlphaException extends GdException
{
    public static function saveAlpha(string $reason = ''): self
    {
        return new self('Unable to set alpha saving.' . ($reason !== '' ? ' ' . $reason : ''));
    }

    public static function alphaBlending(string $reason = ''): self
    {
        return new self('Unable to set alpha blending.' . ($reason !== '' ? ' ' . $reason : ''));
    }
}
