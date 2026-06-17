<?php

declare(strict_types=1);

namespace Lemonade\Image\Exceptions\Gd;

use Lemonade\Image\Exceptions\ImageException;
use RuntimeException;

class GdException extends RuntimeException implements ImageException
{
}
