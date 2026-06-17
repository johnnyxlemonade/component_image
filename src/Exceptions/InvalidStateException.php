<?php

declare(strict_types=1);

namespace Lemonade\Image\Exceptions;

use RuntimeException;

/**
 * Thrown when an operation is invalid for the current object state.
 */
class InvalidStateException extends RuntimeException implements ImageException
{
}
