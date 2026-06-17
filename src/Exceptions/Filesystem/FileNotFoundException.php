<?php

declare(strict_types=1);

namespace Lemonade\Image\Exceptions\Filesystem;

use Lemonade\Image\Exceptions\IOException;

use function sprintf;

/**
 * Thrown when a required file or directory cannot be found.
 *
 * @package     Lemonade
 * @subpackage  Image\Exceptions\Filesystem
 * @category    Exception
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
 */
final class FileNotFoundException extends IOException
{
    public static function forPath(string $path): self
    {
        return new self(sprintf('File or directory "%s" was not found.', $path));
    }
}
