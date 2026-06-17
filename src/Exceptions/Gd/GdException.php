<?php

declare(strict_types=1);

namespace Lemonade\Image\Exceptions\Gd;

use Lemonade\Image\Exceptions\ImageException;
use RuntimeException;

/**
 * Base exception for GD image processing failures.
 *
 * @package     Lemonade
 * @subpackage  Image\Exceptions\Gd
 * @category    Exception
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
 */
class GdException extends RuntimeException implements ImageException {}
