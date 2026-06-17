<?php

declare(strict_types=1);

namespace Lemonade\Image\Tests\Unit\Filesystem;

use Lemonade\Image\Filesystem\ImageDirectoryResolver;
use Lemonade\Image\ImageStorageConfig;
use PHPUnit\Framework\TestCase;

final class ImageDirectoryResolverTest extends TestCase
{
    public function testResolvesThumbnailStorageAndCacheDirectories(): void
    {
        $resolver = new ImageDirectoryResolver(
            config: new ImageStorageConfig(
                storageRoot: '/tmp/lemonade',
                storageDirectory: 'storage',
                cacheDirectory: 'cache',
            ),
            level: 6,
            storageTypeId: 'thumbnail',
            moduleId: 10,
            artId: 12345,
        );

        self::assertSame(
            '/tmp/lemonade/storage/10/1/00/30/39',
            self::normalizePath($resolver->getStorage()),
        );
        self::assertSame(
            '/tmp/lemonade/storage/0/cache/10/1/00/30/39',
            self::normalizePath($resolver->getCache()),
        );
    }

    public function testResolvesGalleryStorageTypeAlias(): void
    {
        $resolver = new ImageDirectoryResolver(
            config: new ImageStorageConfig(
                storageRoot: '/tmp/lemonade',
                storageDirectory: 'storage',
                cacheDirectory: 'cache',
            ),
            level: 6,
            storageTypeId: 'gallery',
            moduleId: 20,
            artId: 54321,
        );

        self::assertSame(
            '/tmp/lemonade/storage/20/2/00/d4/31',
            self::normalizePath($resolver->getStorage()),
        );
        self::assertSame(
            '/tmp/lemonade/storage/0/cache/20/2/00/d4/31',
            self::normalizePath($resolver->getCache()),
        );
    }

    public function testResolvesNumericStorageType(): void
    {
        $resolver = new ImageDirectoryResolver(
            config: new ImageStorageConfig(
                storageRoot: '/tmp/lemonade',
                storageDirectory: 'storage',
                cacheDirectory: 'cache',
            ),
            level: 6,
            storageTypeId: 99,
            moduleId: 7,
            artId: 255,
        );

        self::assertSame(
            '/tmp/lemonade/storage/7/99/00/00/ff',
            self::normalizePath($resolver->getStorage()),
        );
        self::assertSame(
            '/tmp/lemonade/storage/0/cache/7/99/00/00/ff',
            self::normalizePath($resolver->getCache()),
        );
    }

    public function testResolvesFallbackCacheDirectory(): void
    {
        $resolver = new ImageDirectoryResolver(
            config: new ImageStorageConfig(
                storageRoot: '/tmp/lemonade',
                storageDirectory: 'storage',
                cacheDirectory: 'cache',
                fallbackModuleId: 'fallback-module',
                fallbackStorageTypeId: 'fallback-type',
            ),
            level: 6,
            storageTypeId: 'thumbnail',
            moduleId: 10,
            artId: 12345,
        );

        self::assertSame(
            '/tmp/lemonade/storage/0/cache/fallback-module/fallback-type',
            self::normalizePath($resolver->getFallbackCache()),
        );
        self::assertSame(
            '/tmp/lemonade/storage/0/cache/fallback-module/fallback-type/abc123.png',
            self::normalizePath(
                $resolver->getFallbackPng(
                    hash: 'abc123',
                ),
            ),
        );
        self::assertSame(
            '/tmp/lemonade/storage/0/cache/fallback-module/fallback-type/abc123.webp',
            self::normalizePath(
                $resolver->getFallbackWebp(
                    hash: 'abc123',
                ),
            ),
        );
    }

    public function testNullIdentifiersFallBackToZeroDirectories(): void
    {
        $resolver = new ImageDirectoryResolver(
            config: new ImageStorageConfig(
                storageRoot: '/tmp/lemonade',
                storageDirectory: 'storage',
                cacheDirectory: 'cache',
            ),
            level: 6,
            storageTypeId: null,
            moduleId: null,
            artId: null,
        );

        self::assertSame(
            '/tmp/lemonade/storage/0/0/00/00/00',
            self::normalizePath($resolver->getStorage()),
        );
        self::assertSame(
            '/tmp/lemonade/storage/0/cache/0/0/00/00/00',
            self::normalizePath($resolver->getCache()),
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
