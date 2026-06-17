<?php

declare(strict_types=1);

namespace Lemonade\Image;

use function file_exists;

final class ImageFileInspector
{
    public function exists(?string $file): bool
    {
        return $file !== null && $file !== '' && file_exists($file);
    }
}
