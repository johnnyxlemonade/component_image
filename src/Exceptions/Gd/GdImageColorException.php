<?php

declare(strict_types=1);

namespace Lemonade\Image\Exceptions\Gd;

/**
 * Thrown when GD color allocation, resolution or fill operations fail.
 *
 * @package     Lemonade
 * @subpackage  Image\Exceptions\Gd
 * @category    Exception
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
 */
final class GdImageColorException extends GdException
{
    public static function allocate(string $reason = ''): self
    {
        return new self('Unable to allocate image color.' . ($reason !== '' ? ' ' . $reason : ''));
    }

    public static function resolve(string $reason = ''): self
    {
        return new self('Unable to resolve image color.' . ($reason !== '' ? ' ' . $reason : ''));
    }

    public static function fill(string $reason = ''): self
    {
        return new self('Unable to fill image.' . ($reason !== '' ? ' ' . $reason : ''));
    }

    public static function filledRectangle(string $reason = ''): self
    {
        return new self('Unable to draw filled rectangle.' . ($reason !== '' ? ' ' . $reason : ''));
    }
}
