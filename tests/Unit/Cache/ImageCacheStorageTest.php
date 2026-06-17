<?php

declare(strict_types=1);

namespace Lemonade\Image\Tests\Unit\Cache;

use Lemonade\Image\Cache\ImageCacheStorage;
use Lemonade\Image\Context\ImageContext;
use Lemonade\Image\Context\ImageFileContext;
use Lemonade\Image\Filesystem\ImageDirectoryResolver;
use Lemonade\Image\Generator\AppGenerator;
use Lemonade\Image\ImageResult;
use Lemonade\Image\ImageStorageConfig;
use Lemonade\Image\Options\ImageOptionsDTO;
use Lemonade\Image\Utils\FileSystem;
use PHPUnit\Framework\TestCase;

final class ImageCacheStorageTest extends TestCase
{
    private string $root;

    /** @var array<array-key, mixed> */
    private array $serverBackup = [];

    protected function setUp(): void
    {
        $this->serverBackup = $_SERVER;

        unset($_SERVER['HTTP_ACCEPT']);

        $this->root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lemonade-image-cache-test-' . uniqid('', true);
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->serverBackup;

        $this->removeDirectory(
            directory: $this->root,
        );
    }

    public function testCreatesDirectoryForFile(): void
    {
        $context = $this->createContext();
        $storage = new ImageCacheStorage();

        $file = $this->root . DIRECTORY_SEPARATOR . 'nested' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'image.png';

        $storage->createDirectoryForFile(
            context: $context,
            file: $file,
        );

        self::assertDirectoryExists(dirname($file));
    }

    public function testSavesPngVariantWhenWebpIsNotSupportedByRequest(): void
    {
        $context = $this->createContext();
        $storage = new ImageCacheStorage();

        $storage->saveVariant(
            context: $context,
            result: $this->createPngResult(),
        );

        self::assertFileExists($context->getCacheFile());
        self::assertFileDoesNotExist($context->getCacheWebp());
        self::assertImageType(
            file: $context->getCacheFile(),
            expectedType: IMAGETYPE_PNG,
        );
    }

    public function testSavesFallbackPng(): void
    {
        $context = $this->createContext();
        $storage = new ImageCacheStorage();

        $storage->saveFallback(
            context: $context,
            result: $this->createPngResult(),
        );

        self::assertFileExists($context->getFallbackPng());
        self::assertFileDoesNotExist($context->getFallbackWebp());
        self::assertImageType(
            file: $context->getFallbackPng(),
            expectedType: IMAGETYPE_PNG,
        );
    }

    public function testDeletesCacheDirectory(): void
    {
        $context = $this->createContext();
        $storage = new ImageCacheStorage();

        $storage->saveVariant(
            context: $context,
            result: $this->createPngResult(),
        );

        self::assertDirectoryExists($context->getDirectory()->getCache());

        $storage->deleteCache(
            context: $context,
        );

        self::assertDirectoryDoesNotExist($context->getDirectory()->getCache());
    }

    private function createContext(): ImageContext
    {
        return new ImageContext(
            file: new ImageFileContext(
                directory: new ImageDirectoryResolver(
                    config: new ImageStorageConfig(
                        storageRoot: $this->root,
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
                    width: 320,
                    height: 240,
                    crop: 1,
                    canvasColor: 'ffffff',
                    quality: 85,
                    missing: true,
                ),
                filesystem: new FileSystem(),
                file: 'example.png',
            ),
        );
    }

    private function createPngResult(): ImageResult
    {
        return new ImageResult(
            image: AppGenerator::fromBlank(
                width: 120,
                height: 80,
                color: [
                    'red' => 255,
                    'green' => 255,
                    'blue' => 255,
                ],
            ),
            type: AppGenerator::PNG,
            quality: 85,
        );
    }

    private static function assertImageType(string $file, int $expectedType): void
    {
        $size = getimagesize($file);

        if ($size === false) {
            self::fail(sprintf('Unable to read image size from "%s".', $file));
        }

        self::assertSame(
            $expectedType,
            $size[2],
        );
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = scandir($directory);

        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $item;

            if (is_dir($path)) {
                $this->removeDirectory(
                    directory: $path,
                );

                continue;
            }

            if (is_file($path)) {
                unlink($path);
            }
        }

        rmdir($directory);
    }
}
