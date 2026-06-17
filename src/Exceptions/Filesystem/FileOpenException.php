<?php

declare(strict_types=1);

namespace Lemonade\Image\Exceptions\Filesystem;

use Lemonade\Image\Exceptions\IOException;

use function sprintf;

/**
 * Thrown when a file cannot be opened in the requested mode.
 *
 * @package     Lemonade
 * @subpackage  Image\Exceptions\Filesystem
 * @category    Exception
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
 */
final class FileOpenException extends IOException
{
    public static function forPath(string $path, string $mode, string $reason = ''): self
    {
        return new self(sprintf(
            'Unable to open file "%s" using mode "%s".%s',
            $path,
            $mode,
            $reason !== '' ? ' ' . $reason : '',
        ));
    }
}
