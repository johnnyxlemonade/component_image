<?php

declare(strict_types=1);

namespace Lemonade\Image;

use Lemonade\Image\Providers\DirectoryProvider;
use Lemonade\Image\Utils\FileSystem;

/**
 * Holds runtime state for one image request.
 */
final class ImageContext
{
    public function __construct(
        private readonly ImageFileContext $file,
    ) {}

    public function getFile(): ImageFileContext
    {
        return $this->file;
    }

    public function getOptions(): ImageOptionsDTO
    {
        return $this->file->getOptions();
    }

    public function ensureFallbackSize(int $width, int $height): void
    {
        $this->file->ensureFallbackSize(
            width: $width,
            height: $height,
        );
    }

    public function getDirectory(): DirectoryProvider
    {
        return $this->file->getDirectory();
    }

    public function getFilesystem(): FileSystem
    {
        return $this->file->getFilesystem();
    }

    public function getSourceFile(): string
    {
        return $this->file->getSourceFile();
    }

    public function getCacheFile(): string
    {
        return $this->file->getCacheFile();
    }

    public function getCacheWebp(): string
    {
        return $this->file->getCacheWebp();
    }

    public function getMissingPng(): string
    {
        return $this->file->getMissingPng();
    }

    public function getMissingWebp(): string
    {
        return $this->file->getMissingWebp();
    }
}
