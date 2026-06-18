<?php

declare(strict_types=1);

namespace Lemonade\Image\Tests\Unit\Cache;

use Lemonade\Image\Cache\ImageCacheResponder;
use Lemonade\Image\Context\ImageFileContext;
use Lemonade\Image\Detection\ImageFileInspector;
use Lemonade\Image\Filesystem\ImageDirectoryResolver;
use Lemonade\Image\Generator\AppGenerator;
use Lemonade\Image\ImageStorageConfig;
use Lemonade\Image\Options\ImageOptionsDTO;
use Lemonade\Image\Options\ImageResizeMode;
use Lemonade\Image\Utils\FileSystem;
use PHPUnit\Framework\TestCase;

use function gmdate;
use function time;
use function touch;

final class ImageCacheResponderTest extends TestCase
{
    private string $root;

    /** @var array<array-key, mixed> */
    private array $serverBackup = [];

    protected function setUp(): void
    {
        $this->serverBackup = $_SERVER;

        unset($_SERVER['HTTP_ACCEPT']);
        unset($_SERVER['HTTP_IF_MODIFIED_SINCE']);

        $this->root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lemonade-image-cache-responder-test-' . uniqid('', true);
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->serverBackup;

        $this->removeDirectory(
            directory: $this->root,
        );
    }

    public function testReturnsNullWhenBrowserCacheHeaderIsMissing(): void
    {
        $file = $this->createFileContext();

        $response = $this->createResponder()
            ->createNotModifiedResponseIfFresh(
                file: $file,
            );

        self::assertNull($response);
    }

    public function testReturnsNullWhenBrowserCacheHeaderIsInvalid(): void
    {
        $_SERVER['HTTP_IF_MODIFIED_SINCE'] = 'invalid-date';

        $file = $this->createFileContext();

        $response = $this->createResponder()
            ->createNotModifiedResponseIfFresh(
                file: $file,
            );

        self::assertNull($response);
    }

    public function testReturnsNotModifiedResponseWhenBrowserCacheIsFresh(): void
    {
        $file = $this->createFileContext();

        $sourceFile = $file->getSourceFile();
        $cacheFile = $file->getCacheFile();

        $this->createPngFile(
            file: $sourceFile,
            width: 800,
            height: 600,
        );

        $this->createPngFile(
            file: $cacheFile,
            width: 320,
            height: 240,
        );

        $sourceTime = time() - 20;
        $cacheTime = time() - 10;

        touch(
            filename: $sourceFile,
            mtime: $sourceTime,
        );

        touch(
            filename: $cacheFile,
            mtime: $cacheTime,
        );

        $_SERVER['HTTP_IF_MODIFIED_SINCE'] = gmdate(
                format: 'D, d M Y H:i:s',
                timestamp: $cacheTime,
            ) . ' GMT';

        $response = $this->createResponder()
            ->createNotModifiedResponseIfFresh(
                file: $file,
            );

        self::assertNotNull($response);
        self::assertSame(304, $response->getStatusCode());
        self::assertTrue($response->isNotModified());
        self::assertFalse($response->isBinary());
        self::assertFalse($response->isFile());
        self::assertNull($response->getContent());
        self::assertNull($response->getFile());
        self::assertNull($response->getType());
    }

    public function testReturnsNullWhenBrowserCacheIsOlderThanCacheFile(): void
    {
        $file = $this->createFileContext();

        $sourceFile = $file->getSourceFile();
        $cacheFile = $file->getCacheFile();

        $this->createPngFile(
            file: $sourceFile,
            width: 800,
            height: 600,
        );

        $this->createPngFile(
            file: $cacheFile,
            width: 320,
            height: 240,
        );

        $sourceTime = time() - 20;
        $cacheTime = time() - 10;

        touch(
            filename: $sourceFile,
            mtime: $sourceTime,
        );

        touch(
            filename: $cacheFile,
            mtime: $cacheTime,
        );

        $_SERVER['HTTP_IF_MODIFIED_SINCE'] = gmdate(
                format: 'D, d M Y H:i:s',
                timestamp: $cacheTime - 1,
            ) . ' GMT';

        $response = $this->createResponder()
            ->createNotModifiedResponseIfFresh(
                file: $file,
            );

        self::assertNull($response);
    }

    public function testReturnsNullWhenCacheFileIsMissing(): void
    {
        $file = $this->createFileContext();

        $this->createPngFile(
            file: $file->getSourceFile(),
            width: 800,
            height: 600,
        );

        $response = $this->createResponder()
            ->createCacheResponseIfExists(
                file: $file,
            );

        self::assertNull($response);
    }

    public function testReturnsNullWhenCacheFileIsOlderThanSourceFile(): void
    {
        $file = $this->createFileContext();

        $sourceFile = $file->getSourceFile();
        $cacheFile = $file->getCacheFile();

        $this->createPngFile(
            file: $sourceFile,
            width: 800,
            height: 600,
        );

        $this->createPngFile(
            file: $cacheFile,
            width: 320,
            height: 240,
        );

        touch(
            filename: $sourceFile,
            mtime: time(),
        );

        touch(
            filename: $cacheFile,
            mtime: time() - 10,
        );

        $response = $this->createResponder()
            ->createCacheResponseIfExists(
                file: $file,
            );

        self::assertNull($response);
    }

    public function testReturnsCacheFileResponseWhenFreshCacheExists(): void
    {
        $file = $this->createFileContext();

        $sourceFile = $file->getSourceFile();
        $cacheFile = $file->getCacheFile();

        $this->createPngFile(
            file: $sourceFile,
            width: 800,
            height: 600,
        );

        $this->createPngFile(
            file: $cacheFile,
            width: 320,
            height: 240,
        );

        $sourceTime = time() - 20;
        $cacheTime = time() - 10;

        touch(
            filename: $sourceFile,
            mtime: $sourceTime,
        );

        touch(
            filename: $cacheFile,
            mtime: $cacheTime,
        );

        $response = $this->createResponder()
            ->createCacheResponseIfExists(
                file: $file,
            );

        self::assertNotNull($response);
        self::assertSame(200, $response->getStatusCode());
        self::assertFalse($response->isBinary());
        self::assertTrue($response->isFile());
        self::assertFalse($response->isNotModified());
        self::assertSame($cacheFile, $response->getFile());
        self::assertSame(AppGenerator::PNG, $response->getType());
        self::assertSame($cacheTime, $response->getLastModified());
    }

    private function createResponder(): ImageCacheResponder
    {
        return new ImageCacheResponder(
            fileInspector: new ImageFileInspector(),
        );
    }

    private function createFileContext(): ImageFileContext
    {
        return new ImageFileContext(
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
                resizeMode: ImageResizeMode::FitWithCanvas,
                canvasColor: 'ffffff',
                quality: 85,
                missing: true,
            ),
            filesystem: new FileSystem(),
            file: 'example.png',
        );
    }

    private function createPngFile(string $file, int $width, int $height): void
    {
        $directory = dirname($file);

        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            self::fail(sprintf('Unable to create directory "%s".', $directory));
        }

        AppGenerator::fromBlank(
            width: $width,
            height: $height,
            color: AppGenerator::rgb(
                red: 255,
                green: 255,
                blue: 255,
            ),
        )->save(
            file: $file,
            quality: 9,
            type: AppGenerator::PNG,
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

            if (is_dir($path) && !is_link($path)) {
                $this->removeDirectory(
                    directory: $path,
                );

                continue;
            }

            if (is_file($path) || is_link($path)) {
                unlink($path);
            }
        }

        rmdir($directory);
    }
}
