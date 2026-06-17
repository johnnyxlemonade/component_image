<?php

declare(strict_types=1);

namespace Lemonade\Image\Exceptions\Gd;

use function sprintf;

/**
 * Thrown when a GD image resource cannot be created.
 *
 * @package     Lemonade
 * @subpackage  Image\Exceptions\Gd
 * @category    Exception
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
 */
final class GdImageCreateException extends GdException
{
    public static function forTrueColorImage(int $width, int $height, string $reason = ''): self
    {
        return new self(sprintf(
            'Unable to create true color image %dx%d.%s',
            $width,
            $height,
            $reason !== '' ? ' ' . $reason : ''
        ));
    }

    public static function fromString(string $reason = ''): self
    {
        return new self('Unable to create image from string.' . ($reason !== '' ? ' ' . $reason : ''));
    }
}
