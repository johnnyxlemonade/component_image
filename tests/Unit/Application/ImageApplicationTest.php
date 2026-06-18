<?php

declare(strict_types=1);

namespace Lemonade\Image\Tests\Unit\Application;

use Lemonade\Image\Application\ImageApplication;
use Lemonade\Image\Cache\ImageCacheResponder;
use Lemonade\Image\Cache\ImageCacheStorage;
use Lemonade\Image\Context\ImageContext;
use Lemonade\Image\Context\ImageFileContext;
use Lemonade\Image\Detection\ImageFileInspector;
use Lemonade\Image\Filesystem\ImageDirectoryResolver;
use Lemonade\Image\Generator\AppGenerator;
use Lemonade\Image\Generator\ImageGenerator;
use Lemonade\Image\Http\ImageHttpResponseFactory;
use Lemonade\Image\ImageStorageConfig;
use Lemonade\Image\Options\ImageOptionsDTO;
use Lemonade\Image\Options\ImageResizeMode;
use Lemonade\Image\Utils\FileSystem;
use PHPUnit\Framework\TestCase;

use function time;
use function touch;
use function gmdate;

final class ImageApplicationTest extends TestCase
{
    private string $root;

    /** @var array<array-key, mixed> */
    private array $serverBackup = [];

    protected function setUp(): void
    {
        $this->serverBackup = $_SERVER;

        unset($_SERVER['HTTP_ACCEPT']);
        unset($_SERVER['HTTP_IF_MODIFIED_SINCE']);

        $this->root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lemonade-image-application-test-' . uniqid('', true);
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->serverBackup;

        $this->removeDirectory(
            directory: $this->root,
        );
    }

    public function testCreatesBinaryResponseFromSourceImage(): void
    {
        $sourceFile = $this->getSourceFile(
            file: 'example.png',
        );

        $this->createPngFile(
            file: $sourceFile,
            width: 800,
            height: 600,
        );

        $application = $this->createApplication(
            file: 'example.png',
            width: 400,
            height: 300,
            resizeMode: ImageResizeMode::Fit,
        );

        $response = $application->handle();

        self::assertSame(200, $response->getStatusCode());
        self::assertTrue($response->isBinary());
        self::assertFalse($response->isFile());
        self::assertFalse($response->isNotModified());
        self::assertSame(AppGenerator::PNG, $response->getType());
        self::assertIsString($response->getContent());
        self::assertNotSame('', $response->getContent());
    }

    public function testReturnsCacheFileResponseWhenFreshCacheExists(): void
    {
        $sourceFile = $this->getSourceFile(
            file: 'example.png',
        );

        $this->createPngFile(
            file: $sourceFile,
            width: 800,
            height: 600,
        );

        $fileContext = $this->createFileContext(
            file: 'example.png',
            width: 400,
            height: 300,
            resizeMode: ImageResizeMode::Fit,
        );

        $cacheFile = $fileContext->getCacheFile();

        $this->createPngFile(
            file: $cacheFile,
            width: 400,
            height: 300,
        );

        touch(
            filename: $sourceFile,
            mtime: time() - 10,
        );

        touch(
            filename: $cacheFile,
            mtime: time(),
        );

        $application = $this->createApplication(
            file: 'example.png',
            width: 400,
            height: 300,
            resizeMode: ImageResizeMode::Fit,
        );

        $response = $application->handle();

        self::assertSame(200, $response->getStatusCode());
        self::assertFalse($response->isBinary());
        self::assertTrue($response->isFile());
        self::assertFalse($response->isNotModified());
        self::assertSame($cacheFile, $response->getFile());
        self::assertSame(AppGenerator::PNG, $response->getType());
    }

    public function testCreatesFallbackResponseWhenSourceImageIsMissing(): void
    {
        $placeholder = $this->root . DIRECTORY_SEPARATOR . 'placeholder.png';

        $this->createPngFile(
            file: $placeholder,
            width: 100,
            height: 80,
        );

        $application = $this->createApplication(
            file: 'missing.png',
            width: 320,
            height: 240,
            resizeMode: ImageResizeMode::Shrink,
            placeholderImageFile: $placeholder,
        );

        $response = $application->handle();

        self::assertSame(200, $response->getStatusCode());
        self::assertTrue($response->isBinary());
        self::assertFalse($response->isFile());
        self::assertFalse($response->isNotModified());
        self::assertSame(AppGenerator::PNG, $response->getType());
        self::assertIsString($response->getContent());
        self::assertNotSame('', $response->getContent());
    }

    public function testReturnsNotModifiedResponseWhenBrowserCacheIsFresh(): void
    {
        $sourceFile = $this->getSourceFile(
            file: 'example.png',
        );

        $this->createPngFile(
            file: $sourceFile,
            width: 800,
            height: 600,
        );

        $fileContext = $this->createFileContext(
            file: 'example.png',
            width: 400,
            height: 300,
            resizeMode: ImageResizeMode::Fit,
        );

        $cacheFile = $fileContext->getCacheFile();

        $this->createPngFile(
            file: $cacheFile,
            width: 400,
            height: 300,
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

        $application = $this->createApplication(
            file: 'example.png',
            width: 400,
            height: 300,
            resizeMode: ImageResizeMode::Fit,
        );

        $response = $application->handle();

        self::assertSame(304, $response->getStatusCode());
        self::assertFalse($response->isBinary());
        self::assertFalse($response->isFile());
        self::assertTrue($response->isNotModified());
        self::assertNull($response->getContent());
        self::assertNull($response->getFile());
        self::assertNull($response->getType());
    }

    private function createApplication(
        string $file,
        ?int $width,
        ?int $height,
        ImageResizeMode $resizeMode,
        ?string $placeholderImageFile = null,
    ): ImageApplication {
        $storageConfig = $this->createStorageConfig(
            placeholderImageFile: $placeholderImageFile,
        );

        $fileInspector = new ImageFileInspector();

        return new ImageApplication(
            context: new ImageContext(
                file: $this->createFileContext(
                    file: $file,
                    width: $width,
                    height: $height,
                    resizeMode: $resizeMode,
                    placeholderImageFile: $placeholderImageFile,
                ),
            ),
            cacheResponder: new ImageCacheResponder(
                fileInspector: $fileInspector,
            ),
            cacheStorage: new ImageCacheStorage(),
            responseFactory: new ImageHttpResponseFactory(),
            generator: new ImageGenerator(
                fileInspector: $fileInspector,
                storageConfig: $storageConfig,
            ),
            fileInspector: $fileInspector,
        );
    }

    private function createFileContext(
        string $file,
        ?int $width,
        ?int $height,
        ImageResizeMode $resizeMode,
        ?string $placeholderImageFile = null,
    ): ImageFileContext {
        return new ImageFileContext(
            directory: new ImageDirectoryResolver(
                config: $this->createStorageConfig(
                    placeholderImageFile: $placeholderImageFile,
                ),
                level: 6,
                storageTypeId: 'thumbnail',
                moduleId: 10,
                artId: 12345,
            ),
            options: new ImageOptionsDTO(
                width: $width,
                height: $height,
                resizeMode: $resizeMode,
                canvasColor: 'ffffff',
                quality: 85,
                missing: true,
            ),
            filesystem: new FileSystem(),
            file: $file,
        );
    }

    private function createStorageConfig(?string $placeholderImageFile = null): ImageStorageConfig
    {
        return new ImageStorageConfig(
            storageRoot: $this->root,
            storageDirectory: 'storage',
            cacheDirectory: 'cache',
            fallbackModuleId: '0',
            fallbackStorageTypeId: '0',
            placeholderImageFile: $placeholderImageFile ?? $this->root . DIRECTORY_SEPARATOR . 'placeholder.png',
        );
    }

    private function getSourceFile(string $file): string
    {
        return $this->root
            . DIRECTORY_SEPARATOR . 'storage'
            . DIRECTORY_SEPARATOR . '10'
            . DIRECTORY_SEPARATOR . '1'
            . DIRECTORY_SEPARATOR . '00'
            . DIRECTORY_SEPARATOR . '30'
            . DIRECTORY_SEPARATOR . '39'
            . DIRECTORY_SEPARATOR . $file;
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
