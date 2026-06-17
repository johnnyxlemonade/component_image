<?php

declare(strict_types=1);

namespace Lemonade\Image\Exceptions;

/**
 * Thrown when an invalid argument is passed to the image component API.
 *
 * @package     Lemonade
 * @subpackage  Image\Exceptions
 * @category    Exception
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
 */
class InvalidArgumentException extends \InvalidArgumentException implements ImageException {}
