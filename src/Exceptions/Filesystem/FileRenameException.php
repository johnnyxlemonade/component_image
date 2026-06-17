<?php

declare(strict_types=1);

namespace Lemonade\Image\Exceptions\Filesystem;

use Lemonade\Image\Exceptions\IOException;

use function sprintf;

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
