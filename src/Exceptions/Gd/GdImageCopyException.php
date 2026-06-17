<?php

declare(strict_types=1);

namespace Lemonade\Image\Exceptions\Gd;

/**
 * Thrown when GD image copy or resampling operations fail.
 *
 * @package     Lemonade
 * @subpackage  Image\Exceptions\Gd
 * @category    Exception
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
 */
final class GdImageCopyException extends GdException
{
    public static function copy(string $reason = ''): self
    {
        return new self('Unable to copy image.' . ($reason !== '' ? ' ' . $reason : ''));
    }

    public static function resample(string $reason = ''): self
    {
        return new self('Unable to resample image.' . ($reason !== '' ? ' ' . $reason : ''));
    }
}
