<?php

declare(strict_types=1);

namespace Lemonade\Image\Exceptions\Gd;

/**
 * Thrown when GD alpha channel configuration fails.
 *
 * @package     Lemonade
 * @subpackage  Image\Exceptions\Gd
 * @category    Exception
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
 */
final class GdImageAlphaException extends GdException
{
    public static function saveAlpha(string $reason = ''): self
    {
        return new self('Unable to set alpha saving.' . ($reason !== '' ? ' ' . $reason : ''));
    }

    public static function alphaBlending(string $reason = ''): self
    {
        return new self('Unable to set alpha blending.' . ($reason !== '' ? ' ' . $reason : ''));
    }
}
