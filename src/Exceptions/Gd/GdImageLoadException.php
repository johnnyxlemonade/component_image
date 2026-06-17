<?php

declare(strict_types=1);

namespace Lemonade\Image\Exceptions\Gd;

use function sprintf;

/**
 * Thrown when GD cannot load an image from a source file.
 *
 * @package     Lemonade
 * @subpackage  Image\Exceptions\Gd
 * @category    Exception
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
 */
final class GdImageLoadException extends GdException
{
    public static function fromFile(string $file, string $reason = ''): self
    {
        return new self(sprintf(
            'Unable to create image from file "%s".%s',
            $file,
            $reason !== '' ? ' ' . $reason : ''
        ));
    }
}
