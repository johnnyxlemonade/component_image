<?php

declare(strict_types=1);

namespace Lemonade\Image\Exceptions\Filesystem;

use Lemonade\Image\Exceptions\IOException;

use function sprintf;

/**
 * Thrown when a directory cannot be created.
 *
 * @package     Lemonade
 * @subpackage  Image\Exceptions\Filesystem
 * @category    Exception
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
 */
final class DirectoryCreateException extends IOException
{
    public static function forPath(string $path, string $reason = ''): self
    {
        return new self(sprintf(
            'Unable to create directory "%s".%s',
            $path,
            $reason !== '' ? ' ' . $reason : ''
        ));
    }
}
