<?php

declare(strict_types=1);

namespace Lemonade\Image\Exceptions\Image;

use function sprintf;

/**
 * Thrown when the source image path is missing or unavailable.
 *
 * @package     Lemonade
 * @subpackage  Image\Exceptions\Image
 * @category    Exception
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
 */
final class ImageSourceException extends ImageProcessingException
{
    public static function missingSourcePath(): self
    {
        return new self('Missing source image file path.');
    }

    public static function fileNotFound(string $file): self
    {
        return new self(sprintf('Source image file "%s" was not found.', $file));
    }
}
