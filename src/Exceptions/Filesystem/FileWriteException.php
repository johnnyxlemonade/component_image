<?php

declare(strict_types=1);

namespace Lemonade\Image\Exceptions\Filesystem;

use Lemonade\Image\Exceptions\IOException;

use function sprintf;

final class FileWriteException extends IOException
{
    public static function forPath(string $path, string $reason = ''): self
    {
        return new self(sprintf(
            'Unable to write file "%s".%s',
            $path,
            $reason !== '' ? ' ' . $reason : ''
        ));
    }
}
