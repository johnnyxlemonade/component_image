<?php

declare(strict_types=1);

namespace Lemonade\Image\Exceptions;

use RuntimeException;

/**
 * Thrown when an operation cannot be performed in the current object state.
 *
 * @package     Lemonade
 * @subpackage  Image\Exceptions
 * @category    Exception
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
 */
class InvalidStateException extends RuntimeException implements ImageException {}
