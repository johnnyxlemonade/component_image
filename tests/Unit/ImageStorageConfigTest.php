<?php

declare(strict_types=1);

namespace Lemonade\Image\Tests\Unit;

use Lemonade\Image\ImageStorageConfig;
use PHPUnit\Framework\TestCase;

final class ImageStorageConfigTest extends TestCase
{
    public function testDefaultStoragePaths(): void
    {
        $config = ImageStorageConfig::createDefault();

        self::assertSame(
            './storage',
            self::normalizePath($config->getStorageBase()),
        );
        self::assertSame(
            './storage/0/cache',
            self::normalizePath($config->getCacheBase()),
        );
        self::assertSame(
            './storage/0/cache/0/0',
            self::normalizePath($config->getFallbackCacheDirectory()),
        );
        self::assertSame(
            './themes/frontend/error.png',
            self::normalizePath($config->getPlaceholderImageFile()),
        );
    }

    public function testCustomStoragePaths(): void
    {
        $config = new ImageStorageConfig(
            storageRoot: '/tmp/lemonade',
            storageDirectory: 'files',
            cacheDirectory: 'image-cache',
            fallbackModuleId: 'fallback-module',
            fallbackStorageTypeId: 'fallback-type',
            placeholderImageFile: '/tmp/lemonade/placeholder.png',
        );

        self::assertSame(
            '/tmp/lemonade/files',
            self::normalizePath($config->getStorageBase()),
        );
        self::assertSame(
            '/tmp/lemonade/files/0/image-cache',
            self::normalizePath($config->getCacheBase()),
        );
        self::assertSame(
            '/tmp/lemonade/files/0/image-cache/fallback-module/fallback-type',
            self::normalizePath($config->getFallbackCacheDirectory()),
        );
        self::assertSame(
            '/tmp/lemonade/placeholder.png',
            self::normalizePath($config->getPlaceholderImageFile()),
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
