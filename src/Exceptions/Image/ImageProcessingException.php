<?php

declare(strict_types=1);

namespace Lemonade\Image\Exceptions\Image;

use Lemonade\Image\Exceptions\ImageException;
use RuntimeException;

class ImageProcessingException extends RuntimeException implements ImageException
{
}
