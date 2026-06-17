<?php

declare(strict_types=1);

namespace Lemonade\Image\Exceptions\Image;

use function sprintf;

/**
 * Thrown when an image type, MIME type or file extension is unsupported.
 *
 * @package     Lemonade
 * @subpackage  Image\Exceptions\Image
 * @category    Exception
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
 */
final class ImageTypeException extends ImageProcessingException
{
    public static function unknownFile(string $file): self
    {
        return new self(sprintf('Unknown type of image file "%s".', $file));
    }

    public static function unknownString(): self
    {
        return new self('Unknown type of image string.');
    }

    public static function unsupported(int|string $type): self
    {
        return new self(sprintf('Unsupported image type "%s".', (string) $type));
    }

    public static function unsupportedExtension(string $extension): self
    {
        return new self(sprintf('Unsupported image file extension "%s".', $extension));
    }

    public static function detectionFailed(): self
    {
        return new self('Unable to detect source image type.');
    }
}
