<?php

declare(strict_types=1);

namespace Lemonade\Image\Tests\Unit\Context;

use Lemonade\Image\Context\ImageContext;
use Lemonade\Image\Context\ImageFileContext;
use Lemonade\Image\Filesystem\ImageDirectoryResolver;
use Lemonade\Image\ImageStorageConfig;
use Lemonade\Image\Options\ImageOptionsDTO;
use Lemonade\Image\Utils\FileSystem;
use PHPUnit\Framework\TestCase;

final class ImageContextTest extends TestCase
{
    public function testDelegatesRuntimeStateToFileContext(): void
    {
        $fileContext = $this->createFileContext();
        $context = new ImageContext(
            file: $fileContext,
        );

        self::assertSame($fileContext, $context->getFile());
        self::assertSame($fileContext->getOptions(), $context->getOptions());
        self::assertSame($fileContext->getDirectory(), $context->getDirectory());
        self::assertSame($fileContext->getFilesystem(), $context->getFilesystem());
        self::assertSame($fileContext->getSourceFile(), $context->getSourceFile());
        self::assertSame($fileContext->getCacheFile(), $context->getCacheFile());
        self::assertSame($fileContext->getCacheWebp(), $context->getCacheWebp());
        self::assertSame($fileContext->getFallbackPng(), $context->getFallbackPng());
        self::assertSame($fileContext->getFallbackWebp(), $context->getFallbackWebp());
    }

    public function testEnsureFallbackSizeDelegatesToFileContext(): void
    {
        $context = new ImageContext(
            file: $this->createFileContext(
                width: null,
                height: null,
            ),
        );

        $context->ensureFallbackSize(
            width: 600,
            height: 400,
        );

        self::assertSame(600, $context->getOptions()->getWidth());
        self::assertSame(400, $context->getOptions()->getHeight());
    }

    public function testEnsureFallbackSizeDoesNotOverrideExistingSize(): void
    {
        $context = new ImageContext(
            file: $this->createFileContext(
                width: 320,
                height: 240,
            ),
        );

        $context->ensureFallbackSize(
            width: 600,
            height: 400,
        );

        self::assertSame(320, $context->getOptions()->getWidth());
        self::assertSame(240, $context->getOptions()->getHeight());
    }

    private function createFileContext(?int $width = 320, ?int $height = 240): ImageFileContext
    {
        return new ImageFileContext(
            directory: new ImageDirectoryResolver(
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
            ),
            options: new ImageOptionsDTO(
                width: $width,
                height: $height,
                crop: 1,
                canvasColor: 'ffffff',
                quality: 85,
                missing: true,
            ),
            filesystem: new FileSystem(),
            file: 'example.png',
        );
    }
}
