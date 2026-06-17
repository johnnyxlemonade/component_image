<?php

declare(strict_types=1);

namespace Lemonade\Image\Context;

use Lemonade\Image\Filesystem\ImageDirectoryResolver;
use Lemonade\Image\Options\ImageOptionsDTO;
use Lemonade\Image\Utils\FileSystem;

/**
 * Holds runtime context for one image request.
 *
 * Exposes resolved file paths, parsed options, filesystem access and directory
 * metadata to application services involved in request processing.
 *
 * @package     Lemonade
 * @subpackage  Image\Context
 * @category    Context
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
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

    public function getDirectory(): ImageDirectoryResolver
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

    public function getFallbackPng(): string
    {
        return $this->file->getFallbackPng();
    }

    public function getFallbackWebp(): string
    {
        return $this->file->getFallbackWebp();
    }
}
