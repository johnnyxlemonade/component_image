<?php

declare(strict_types=1);

namespace Lemonade\Image;

use Lemonade\Image\Providers\DataProvider;
use Lemonade\Image\Providers\DirectoryProvider;
use Lemonade\Image\Providers\FileProvider;
use Lemonade\Image\Utils\FileSystem;

/**
 * Holds runtime context for one image request.
 *
 * This is the application-level context created from ImageRequest.
 */
final class ImageContext
{
    public function __construct(
        private readonly FileProvider $fileProvider,
    ) {}

    public function getFileProvider(): FileProvider
    {
        return $this->fileProvider;
    }

    public function getData(): DataProvider
    {
        return $this->fileProvider->getData();
    }

    public function getDirectory(): DirectoryProvider
    {
        return $this->fileProvider->getDirectory();
    }

    public function getFilesystem(): FileSystem
    {
        return $this->fileProvider->getFilesystem();
    }

    public function getSourceFile(): ?string
    {
        return $this->fileProvider->getFileFs();
    }

    public function getCacheFile(): ?string
    {
        return $this->fileProvider->getCacheFile();
    }

    public function getCacheWebp(): ?string
    {
        return $this->fileProvider->getCacheWebp();
    }

    public function getMissingPng(): ?string
    {
        return $this->fileProvider->getMissingPng();
    }

    public function getMissingWebp(): ?string
    {
        return $this->fileProvider->getMissingWebp();
    }
}
