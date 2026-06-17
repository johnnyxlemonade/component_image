<?php

declare(strict_types=1);

namespace Lemonade\Image\Exceptions\Gd;

/**
 * Thrown when GD image output generation fails.
 *
 * @package     Lemonade
 * @subpackage  Image\Exceptions\Gd
 * @category    Exception
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
 */
final class GdImageOutputException extends GdException
{
    public static function output(string $reason = ''): self
    {
        return new self('Image output failed.' . ($reason !== '' ? ' ' . $reason : ''));
    }
}
