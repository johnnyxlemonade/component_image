<?php

declare(strict_types=1);

namespace Lemonade\Image\Exceptions\Image;

/**
 * Thrown when image cache paths or cache write operations are invalid.
 *
 * @package     Lemonade
 * @subpackage  Image\Exceptions\Image
 * @category    Exception
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
 */
final class ImageCacheException extends ImageProcessingException
{
    public static function missingCachePath(): self
    {
        return new self('Missing cache file path.');
    }

    public static function missingErrorCachePath(): self
    {
        return new self('Missing error cache file path.');
    }
}
