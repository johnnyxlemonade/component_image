<?php

declare(strict_types=1);

namespace Lemonade\Image\Exceptions\Filesystem;

use Lemonade\Image\Exceptions\IOException;

use function sprintf;

/**
 * Thrown when a file cannot be renamed or moved.
 *
 * @package     Lemonade
 * @subpackage  Image\Exceptions\Filesystem
 * @category    Exception
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
 */
final class FileRenameException extends IOException
{
    public static function fromTo(string $source, string $destination, string $reason = ''): self
    {
        return new self(sprintf(
            'Unable to rename "%s" to "%s".%s',
            $source,
            $destination,
            $reason !== '' ? ' ' . $reason : ''
        ));
    }
}
