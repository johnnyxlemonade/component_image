<?php

declare(strict_types=1);

namespace Lemonade\Image\Exceptions\Filesystem;

use Lemonade\Image\Exceptions\IOException;

use function sprintf;

final class FileOpenException extends IOException
{
    public static function forPath(string $path, string $mode, string $reason = ''): self
    {
        return new self(sprintf(
            'Unable to open file "%s" using mode "%s".%s',
            $path,
            $mode,
            $reason !== '' ? ' ' . $reason : ''
        ));
    }
}
