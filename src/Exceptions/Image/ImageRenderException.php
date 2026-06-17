<?php

declare(strict_types=1);

namespace Lemonade\Image\Exceptions\Image;

/**
 * Thrown when final image rendering or output buffering fails.
 *
 * @package     Lemonade
 * @subpackage  Image\Exceptions\Image
 * @category    Exception
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
 */
final class ImageRenderException extends ImageProcessingException
{
    public static function failed(): self
    {
        return new self('Image rendering failed.');
    }

    public static function outputBufferFailed(): self
    {
        return new self('Unable to capture output buffer.');
    }

    public static function paletteToTrueColorFailed(string $reason = ''): self
    {
        return new self('Unable to convert palette image to true color.' . ($reason !== '' ? ' ' . $reason : ''));
    }
}
