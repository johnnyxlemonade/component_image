<?php

declare(strict_types=1);

namespace Lemonade\Image\Exceptions\Filesystem;

use Lemonade\Image\Exceptions\IOException;

use function sprintf;

final class FileLockException extends IOException
{
    public static function forPath(string $path): self
    {
        return new self(sprintf('Unable to acquire file lock for "%s".', $path));
    }
}
