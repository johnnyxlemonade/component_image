<?php

declare(strict_types=1);

namespace Lemonade\Image\Exceptions\Image;

use Lemonade\Image\Exceptions\ImageException;
use RuntimeException;

/**
 * Base exception for high-level image processing failures.
 *
 * @package     Lemonade
 * @subpackage  Image\Exceptions\Image
 * @category    Exception
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
 */
class ImageProcessingException extends RuntimeException implements ImageException
{
}
