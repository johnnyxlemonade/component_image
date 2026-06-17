<?php

declare(strict_types=1);

namespace Lemonade\Image\Exceptions\Filesystem;

use Lemonade\Image\Exceptions\IOException;

use function sprintf;

final class FileCopyException extends IOException
{
    public static function fromTo(string $source, string $destination, string $reason = ''): self
    {
        return new self(sprintf(
            'Unable to copy "%s" to "%s".%s',
            $source,
            $destination,
            $reason !== '' ? ' ' . $reason : ''
        ));
    }
}
