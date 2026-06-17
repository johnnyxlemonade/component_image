<?php

declare(strict_types=1);

namespace Lemonade\Image\Exceptions;

use RuntimeException;

/**
 * Base exception for filesystem and low-level I/O failures.
 *
 * @package     Lemonade
 * @subpackage  Image\Exceptions
 * @category    Exception
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
 */
class IOException extends RuntimeException implements ImageException
{
}
