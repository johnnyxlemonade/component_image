<?php

declare(strict_types=1);

namespace Lemonade\Image\Exceptions\Filesystem;

use Lemonade\Image\Exceptions\IOException;

use function sprintf;

/**
 * Thrown when a file permission operation fails.
 *
 * @package     Lemonade
 * @subpackage  Image\Exceptions\Filesystem
 * @category    Exception
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
 */
final class FilePermissionException extends IOException
{
    public static function forPath(string $path, string $operation, string $reason = ''): self
    {
        return new self(sprintf(
            'Unable to change permissions for "%s" during "%s".%s',
            $path,
            $operation,
            $reason !== '' ? ' ' . $reason : '',
        ));
    }
}
