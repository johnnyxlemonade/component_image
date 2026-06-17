<?php

declare(strict_types=1);

namespace Lemonade\Image;

use Lemonade\Image\Utils\FileSystem;
use Lemonade\Image\Utils\PathHelper;

use function pathinfo;
use function sha1;
use function sprintf;
use function substr;

/**
 * Holds resolved source, cache and fallback paths for one image request.
 */
final class ImageFileContext
{
    private ImageOptionsDTO $options;

    private string $sourceFile;
    private string $cacheFile;
    private string $cacheWebp;
    private string $fallbackPng;
    private string $fallbackWebp;

    public function __construct(
        private readonly ImageDirectoryResolver $directory,
        ImageOptionsDTO $options,
        private readonly FileSystem $filesystem,
        ?string $file = null,
    ) {
        $this->options = $options;

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

    public function getFallbackPng(): string
    {
        return $this->fallbackPng;
    }

    public function getFallbackWebp(): string
    {
        return $this->fallbackWebp;
    }

    public function getOptions(): ImageOptionsDTO
    {
        return $this->options;
    }

    public function setOptions(ImageOptionsDTO $options): void
    {
        $this->options = $options;

        $this->resolveFallbackPaths();
    }

    public function ensureFallbackSize(int $width, int $height): void
    {
        if (!$this->options->isMissingAllSize()) {
            return;
        }

        $this->setOptions(
            options: $this->options
                ->withWidth($width)
                ->withHeight($height),
        );
    }

    public function getDirectory(): ImageDirectoryResolver
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

        $this->sourceFile = PathHelper::file(
            directory: $this->directory->getStorage(),
            filename: sprintf(
                '%s.%s',
                $filename,
                $extension,
            ),
        );

        $cacheHash = substr(
            sha1($this->sourceFile . '|' . $this->options->getHash()),
            0,
            32,
        );

        $this->cacheFile = PathHelper::file(
            directory: $this->directory->getCache(),
            filename: sprintf(
                '%s-%s.%s',
                $filename,
                $cacheHash,
                $extension,
            ),
        );

        $this->cacheWebp = PathHelper::file(
            directory: $this->directory->getCache(),
            filename: sprintf(
                '%s-%s.webp',
                $filename,
                $cacheHash,
            ),
        );

        $this->resolveFallbackPaths();
    }

    private function resolveFallbackPaths(): void
    {
        $this->fallbackPng = $this->directory->getFallbackPng(
            hash: $this->options->getHash(),
        );

        $this->fallbackWebp = $this->directory->getFallbackWebp(
            hash: $this->options->getHash(),
        );
    }
}
