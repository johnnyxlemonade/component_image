<?php

declare(strict_types=1);

namespace Lemonade\Image\Exceptions\Filesystem;

use Lemonade\Image\Exceptions\IOException;

use function sprintf;

final class FileNotFoundException extends IOException
{
    public static function forPath(string $path): self
    {
        return new self(sprintf('File or directory "%s" was not found.', $path));
    }
}
