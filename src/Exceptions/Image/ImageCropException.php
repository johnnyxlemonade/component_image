<?php

declare(strict_types=1);

namespace Lemonade\Image\Exceptions\Image;

/**
 * Thrown when image crop processing fails.
 *
 * @package     Lemonade
 * @subpackage  Image\Exceptions\Image
 * @category    Exception
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
 */
final class ImageCropException extends ImageProcessingException
{
    public static function failed(string $reason = ''): self
    {
        return new self('Unable to crop image.' . ($reason !== '' ? ' ' . $reason : ''));
    }
}
