<?php

declare(strict_types=1);

namespace Lemonade\Image\Exceptions\Filesystem;

use Lemonade\Image\Exceptions\IOException;

use function sprintf;

/**
 * Thrown when a file cannot be copied to the target location.
 *
 * @package     Lemonade
 * @subpackage  Image\Exceptions\Filesystem
 * @category    Exception
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
 */
final class FileCopyException extends IOException
{
    public static function fromTo(string $source, string $destination, string $reason = ''): self
    {
        return new self(sprintf(
            'Unable to copy "%s" to "%s".%s',
            $source,
            $destination,
            $reason !== '' ? ' ' . $reason : ''
        ));
    }
}
