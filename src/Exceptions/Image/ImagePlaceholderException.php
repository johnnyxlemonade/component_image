<?php

declare(strict_types=1);

namespace Lemonade\Image\Exceptions\Image;

/**
 * Thrown when fallback placeholder image generation fails.
 *
 * @package     Lemonade
 * @subpackage  Image\Exceptions\Image
 * @category    Exception
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
 */
final class ImagePlaceholderException extends ImageProcessingException
{
    public static function createFailed(): self
    {
        return new self('Unable to create fallback error image.');
    }

    public static function colorAllocationFailed(): self
    {
        return new self('Unable to allocate fallback error image color.');
    }

    public static function renderFailed(): self
    {
        return new self('Unable to render fallback error image.');
    }
}
