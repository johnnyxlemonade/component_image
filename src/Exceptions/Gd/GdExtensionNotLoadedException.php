<?php

declare(strict_types=1);

namespace Lemonade\Image\Exceptions\Gd;

use Lemonade\Image\Exceptions\ImageException;
use LogicException;

/**
 * Thrown when the required PHP GD extension is not loaded.
 *
 * @package     Lemonade
 * @subpackage  Image\Exceptions\Gd
 * @category    Exception
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
 */
final class GdExtensionNotLoadedException extends LogicException implements ImageException
{
    public static function create(): self
    {
        return new self('PHP extension GD is not loaded.');
    }
}
