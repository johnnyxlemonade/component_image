<?php

declare(strict_types=1);

namespace Lemonade\Image\Tests\Unit\Generator;

use Lemonade\Image\Context\ImageFileContext;
use Lemonade\Image\Detection\ImageFileInspector;
use Lemonade\Image\Filesystem\ImageDirectoryResolver;
use Lemonade\Image\Generator\AppGenerator;
use Lemonade\Image\Generator\ImageGenerator;
use Lemonade\Image\ImageStorageConfig;
use Lemonade\Image\Options\ImageOptionsDTO;
use Lemonade\Image\Utils\FileSystem;
use PHPUnit\Framework\TestCase;

final class ImageGeneratorTest extends TestCase
{
    private string $root;

    /** @var array<array-key, mixed> */
    private array $serverBackup = [];

    protected function setUp(): void
    {
        $this->serverBackup = $_SERVER;

        unset($_SERVER['HTTP_ACCEPT']);

        $this->root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lemonade-image-generator-test-' . uniqid('', true);
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->serverBackup;

        $this->removeDirectory(
            directory: $this->root,
        );
    }

    public function testCreatesVariantFromSourceImage(): void
    {
        $sourceFile = $this->root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . '10' . DIRECTORY_SEPARATOR . '1' . DIRECTORY_SEPARATOR . '00' . DIRECTORY_SEPARATOR . '30' . DIRECTORY_SEPARATOR . '39' . DIRECTORY_SEPARATOR . 'example.png';

        $this->createPngFile(
            file: $sourceFile,
            width: 800,
            height: 600,
        );

        $generator = $this->createGenerator();

        $result = $generator->createVariant(
            file: $this->createFileContext(
                width: 400,
                height: 300,
                crop: 3,
                file: 'example.png',
            ),
        );

        self::assertSame(AppGenerator::PNG, $result->getType());
        self::assertSame(85, $result->getQuality());
        self::assertSame(400, $result->getImage()->getWidth());
        self::assertSame(300, $result->getImage()->getHeight());
    }

    public function testCreatesOriginalVariantAsClone(): void
    {
        $sourceFile = $this->root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . '10' . DIRECTORY_SEPARATOR . '1' . DIRECTORY_SEPARATOR . '00' . DIRECTORY_SEPARATOR . '30' . DIRECTORY_SEPARATOR . '39' . DIRECTORY_SEPARATOR . 'example.png';

        $this->createPngFile(
            file: $sourceFile,
            width: 800,
            height: 600,
        );

        $generator = $this->createGenerator();

        $result = $generator->createVariant(
            file: $this->createFileContext(
                width: null,
                height: null,
                crop: -1,
                file: 'example.png',
            ),
        );

        self::assertSame(AppGenerator::PNG, $result->getType());
        self::assertSame(800, $result->getImage()->getWidth());
        self::assertSame(600, $result->getImage()->getHeight());
    }

    public function testCreatesExactVariant(): void
    {
        $sourceFile = $this->root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . '10' . DIRECTORY_SEPARATOR . '1' . DIRECTORY_SEPARATOR . '00' . DIRECTORY_SEPARATOR . '30' . DIRECTORY_SEPARATOR . '39' . DIRECTORY_SEPARATOR . 'example.png';

        $this->createPngFile(
            file: $sourceFile,
            width: 800,
            height: 600,
        );

        $generator = $this->createGenerator();

        $result = $generator->createVariant(
            file: $this->createFileContext(
                width: 300,
                height: 300,
                crop: 2,
                file: 'example.png',
            ),
        );

        self::assertSame(300, $result->getImage()->getWidth());
        self::assertSame(300, $result->getImage()->getHeight());
    }

    public function testCreatesCanvasVariant(): void
    {
        $sourceFile = $this->root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . '10' . DIRECTORY_SEPARATOR . '1' . DIRECTORY_SEPARATOR . '00' . DIRECTORY_SEPARATOR . '30' . DIRECTORY_SEPARATOR . '39' . DIRECTORY_SEPARATOR . 'example.png';

        $this->createPngFile(
            file: $sourceFile,
            width: 800,
            height: 600,
        );

        $generator = $this->createGenerator();

        $result = $generator->createVariant(
            file: $this->createFileContext(
                width: 300,
                height: 300,
                crop: 1,
                file: 'example.png',
            ),
        );

        self::assertSame(300, $result->getImage()->getWidth());
        self::assertSame(300, $result->getImage()->getHeight());
    }

    public function testCreatesFallbackFromConfiguredPlaceholder(): void
    {
        $placeholder = $this->root . DIRECTORY_SEPARATOR . 'placeholder.png';

        $this->createPngFile(
            file: $placeholder,
            width: 100,
            height: 80,
        );

        $generator = $this->createGenerator(
            placeholderImageFile: $placeholder,
        );

        $result = $generator->createFallback(
            file: $this->createFileContext(
                width: 320,
                height: 240,
                crop: 0,
                file: 'missing.png',
            ),
        );

        self::assertSame(AppGenerator::PNG, $result->getType());
        self::assertSame(85, $result->getQuality());
        self::assertSame(320, $result->getImage()->getWidth());
        self::assertSame(240, $result->getImage()->getHeight());
    }

    public function testCreatesFallbackWithoutConfiguredPlaceholderFile(): void
    {
        $generator = $this->createGenerator(
            placeholderImageFile: $this->root . DIRECTORY_SEPARATOR . 'missing-placeholder.png',
        );

        $result = $generator->createFallback(
            file: $this->createFileContext(
                width: 200,
                height: 100,
                crop: 0,
                file: 'missing.png',
            ),
        );

        self::assertSame(AppGenerator::PNG, $result->getType());
        self::assertSame(200, $result->getImage()->getWidth());
        self::assertSame(100, $result->getImage()->getHeight());
    }

    public function testCreatesFallbackWithDefaultSizeWhenDimensionsAreMissing(): void
    {
        $generator = $this->createGenerator(
            placeholderImageFile: $this->root . DIRECTORY_SEPARATOR . 'missing-placeholder.png',
        );

        $result = $generator->createFallback(
            file: $this->createFileContext(
                width: null,
                height: null,
                crop: 0,
                file: 'missing.png',
            ),
        );

        self::assertSame(AppGenerator::PNG, $result->getType());
        self::assertSame(600, $result->getImage()->getWidth());
        self::assertSame(600, $result->getImage()->getHeight());
    }

    private function createGenerator(?string $placeholderImageFile = null): ImageGenerator
    {
        return new ImageGenerator(
            fileInspector: new ImageFileInspector(),
            storageConfig: new ImageStorageConfig(
                storageRoot: $this->root,
                storageDirectory: 'storage',
                cacheDirectory: 'cache',
                fallbackModuleId: '0',
                fallbackStorageTypeId: '0',
                placeholderImageFile: $placeholderImageFile ?? $this->root . DIRECTORY_SEPARATOR . 'placeholder.png',
            ),
        );
    }

    private function createFileContext(
        ?int $width,
        ?int $height,
        int $crop,
        string $file,
    ): ImageFileContext {
        return new ImageFileContext(
            directory: new ImageDirectoryResolver(
                config: new ImageStorageConfig(
                    storageRoot: $this->root,
                    storageDirectory: 'storage',
                    cacheDirectory: 'cache',
                    fallbackModuleId: '0',
                    fallbackStorageTypeId: '0',
                    placeholderImageFile: $this->root . DIRECTORY_SEPARATOR . 'placeholder.png',
                ),
                level: 6,
                storageTypeId: 'thumbnail',
                moduleId: 10,
                artId: 12345,
            ),
            options: new ImageOptionsDTO(
                width: $width,
                height: $height,
                crop: $crop,
                canvasColor: 'ffffff',
                quality: 85,
                missing: true,
            ),
            filesystem: new FileSystem(),
            file: $file,
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
