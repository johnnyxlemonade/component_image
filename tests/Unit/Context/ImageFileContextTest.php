<?php

declare(strict_types=1);

namespace Lemonade\Image\Tests\Unit\Context;

use Lemonade\Image\Context\ImageFileContext;
use Lemonade\Image\Filesystem\ImageDirectoryResolver;
use Lemonade\Image\ImageStorageConfig;
use Lemonade\Image\Options\ImageOptionsDTO;
use Lemonade\Image\Options\ImageResizeMode;
use Lemonade\Image\Utils\FileSystem;
use PHPUnit\Framework\TestCase;

final class ImageFileContextTest extends TestCase
{
    public function testResolvesSourceAndCacheFiles(): void
    {
        $options = new ImageOptionsDTO(
            width: 320,
            height: 240,
            resizeMode: ImageResizeMode::FitWithCanvas,
            canvasColor: 'ffffff',
            quality: 85,
            missing: true,
        );

        $context = new ImageFileContext(
            directory: $this->createDirectoryResolver(),
            options: $options,
            filesystem: new FileSystem(),
            file: 'example.png',
        );

        $cacheHash = substr(
            sha1($context->getSourceFile() . '|' . $options->getHash()),
            0,
            32,
        );

        self::assertSame(
            '/tmp/lemonade/storage/10/1/00/30/39/example.png',
            self::normalizePath($context->getSourceFile()),
        );
        self::assertSame(
            sprintf(
                '/tmp/lemonade/storage/0/cache/10/1/00/30/39/example-%s.png',
                $cacheHash,
            ),
            self::normalizePath($context->getCacheFile()),
        );
        self::assertSame(
            sprintf(
                '/tmp/lemonade/storage/0/cache/10/1/00/30/39/example-%s.webp',
                $cacheHash,
            ),
            self::normalizePath($context->getCacheWebp()),
        );
    }

    public function testResolvesFallbackFilesFromOptionsHash(): void
    {
        $options = new ImageOptionsDTO(
            width: 320,
            height: 240,
            resizeMode: ImageResizeMode::FitWithCanvas,
            canvasColor: 'ffffff',
            quality: 85,
            missing: true,
        );

        $context = new ImageFileContext(
            directory: $this->createDirectoryResolver(),
            options: $options,
            filesystem: new FileSystem(),
            file: 'example.png',
        );

        self::assertSame(
            sprintf(
                '/tmp/lemonade/storage/0/cache/0/0/%s.png',
                $options->getHash(),
            ),
            self::normalizePath($context->getFallbackPng()),
        );
        self::assertSame(
            sprintf(
                '/tmp/lemonade/storage/0/cache/0/0/%s.webp',
                $options->getHash(),
            ),
            self::normalizePath($context->getFallbackWebp()),
        );
    }

    public function testFallsBackToMissingPngWhenFileIsNull(): void
    {
        $options = new ImageOptionsDTO(
            width: 320,
            height: 240,
            resizeMode: ImageResizeMode::FitWithCanvas,
            canvasColor: 'ffffff',
            quality: 85,
            missing: true,
        );

        $context = new ImageFileContext(
            directory: $this->createDirectoryResolver(),
            options: $options,
            filesystem: new FileSystem(),
            file: null,
        );

        self::assertSame(
            '/tmp/lemonade/storage/10/1/00/30/39/missing.png',
            self::normalizePath($context->getSourceFile()),
        );
    }

    public function testEnsureFallbackSizeOnlyAppliesWhenSizeIsMissing(): void
    {
        $options = new ImageOptionsDTO(
            width: null,
            height: null,
            resizeMode: ImageResizeMode::Shrink,
            canvasColor: 'ffffff',
            quality: 85,
            missing: true,
        );

        $context = new ImageFileContext(
            directory: $this->createDirectoryResolver(),
            options: $options,
            filesystem: new FileSystem(),
            file: 'missing.png',
        );

        $context->ensureFallbackSize(
            width: 600,
            height: 600,
        );

        self::assertSame(600, $context->getOptions()->getWidth());
        self::assertSame(600, $context->getOptions()->getHeight());
    }

    public function testEnsureFallbackSizeDoesNotOverrideExistingSize(): void
    {
        $options = new ImageOptionsDTO(
            width: 320,
            height: 240,
            resizeMode: ImageResizeMode::Shrink,
            canvasColor: 'ffffff',
            quality: 85,
            missing: true,
        );

        $context = new ImageFileContext(
            directory: $this->createDirectoryResolver(),
            options: $options,
            filesystem: new FileSystem(),
            file: 'example.png',
        );

        $context->ensureFallbackSize(
            width: 600,
            height: 600,
        );

        self::assertSame(320, $context->getOptions()->getWidth());
        self::assertSame(240, $context->getOptions()->getHeight());
    }

    private function createDirectoryResolver(): ImageDirectoryResolver
    {
        return new ImageDirectoryResolver(
            config: new ImageStorageConfig(
                storageRoot: '/tmp/lemonade',
                storageDirectory: 'storage',
                cacheDirectory: 'cache',
                fallbackModuleId: '0',
                fallbackStorageTypeId: '0',
            ),
            level: 6,
            storageTypeId: 'thumbnail',
            moduleId: 10,
            artId: 12345,
        );
    }

    private static function normalizePath(string $path): string
    {
        return str_replace(
            search: '\\',
            replace: '/',
            subject: $path,
        );
    }
}
