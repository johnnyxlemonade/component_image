<?php

declare(strict_types=1);

namespace Lemonade\Image\Exceptions;

use RuntimeException;

/**
 * Base exception for filesystem I/O failures.
 */
class IOException extends RuntimeException implements ImageException
{
}
