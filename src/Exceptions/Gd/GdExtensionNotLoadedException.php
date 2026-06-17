<?php

declare(strict_types=1);

namespace Lemonade\Image\Exceptions\Gd;

use Lemonade\Image\Exceptions\ImageException;
use LogicException;

final class GdExtensionNotLoadedException extends LogicException implements ImageException
{
    public static function create(): self
    {
        return new self('PHP extension GD is not loaded.');
    }
}
