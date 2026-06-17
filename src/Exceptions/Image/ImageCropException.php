<?php

declare(strict_types=1);

namespace Lemonade\Image\Exceptions\Image;

final class ImageCropException extends ImageProcessingException
{
    public static function failed(string $reason = ''): self
    {
        return new self('Unable to crop image.' . ($reason !== '' ? ' ' . $reason : ''));
    }
}
