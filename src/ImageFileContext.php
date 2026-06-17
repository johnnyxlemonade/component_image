<?php

declare(strict_types=1);

namespace Lemonade\Image;

use Lemonade\Image\Providers\DataProvider;
use Lemonade\Image\Providers\DirectoryProvider;
use Lemonade\Image\Utils\FileSystem;

use function pathinfo;
use function sha1;
use function sprintf;
use function substr;

/**
 * Holds resolved source, cache and fallback paths for one image request.
 */
final class ImageFileContext
{
    private string $sourceFile;
    private string $cacheFile;
    private string $cacheWebp;
    private string $missingPng;
    private string $missingWebp;

    public function __construct(
        private readonly DirectoryProvider $directory,
        private readonly DataProvider $data,
        private readonly FileSystem $filesystem,
        ?string $file = null,
    ) {
        $this->resolveFile(
            file: $file ?? 'missing.png',
        );
    }

    public function getSourceFile(): string
    {
        return $this->sourceFile;
    }

    public function getCacheFile(): string
    {
        return $this->cacheFile;
    }

    public function getCacheWebp(): string
    {
        return $this->cacheWebp;
    }

    public function getMissingPng(): string
    {
        return $this->missingPng;
    }

    public function getMissingWebp(): string
    {
        return $this->missingWebp;
    }

    public function getData(): DataProvider
    {
        return $this->data;
    }

    public function getDirectory(): DirectoryProvider
    {
        return $this->directory;
    }

    public function getFilesystem(): FileSystem
    {
        return $this->filesystem;
    }

    private function resolveFile(string $file): void
    {
        $info = pathinfo($file);

        $filename = isset($info['filename']) && $info['filename'] !== ''
            ? $info['filename']
            : 'missing';

        $extension = isset($info['extension']) && $info['extension'] !== ''
            ? $info['extension']
            : 'png';

        $this->sourceFile = sprintf(
            '%s/%s.%s',
            $this->directory->getStorage(),
            $filename,
            $extension,
        );

        $cacheHash = substr(
            sha1($this->sourceFile . '|' . $this->data->getHash()),
            0,
            32,
        );

        $this->cacheFile = sprintf(
            '%s/%s-%s.%s',
            $this->directory->getCache(),
            $filename,
            $cacheHash,
            $extension,
        );

        $this->cacheWebp = sprintf(
            '%s/%s-%s.webp',
            $this->directory->getCache(),
            $filename,
            $cacheHash,
        );

        $this->missingPng = sprintf(
            './storage/0/cache/0/%s.png',
            $this->data->getHash(),
        );

        $this->missingWebp = sprintf(
            './storage/0/cache/0/%s.webp',
            $this->data->getHash(),
        );
    }
}
