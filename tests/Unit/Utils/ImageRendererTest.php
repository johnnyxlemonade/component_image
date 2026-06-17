<?php

declare(strict_types=1);

namespace Lemonade\Image\Tests\Unit\Utils;

use GdImage;
use Lemonade\Image\Exceptions\Image\ImageTypeException;
use Lemonade\Image\Generator\AppGenerator;
use Lemonade\Image\Utils\GdImageOperations;
use Lemonade\Image\Utils\ImageRenderer;
use PHPUnit\Framework\TestCase;

final class ImageRendererTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lemonade-image-renderer-test-' . uniqid('', true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory(
            directory: $this->root,
        );
    }

    public function testSavesPngImageByExplicitType(): void
    {
        $file = $this->root . DIRECTORY_SEPARATOR . 'image.png';

        ImageRenderer::save(
            image: $this->createImage(),
            file: $file,
            quality: 9,
            type: AppGenerator::PNG,
        );

        self::assertFileExists($file);
        self::assertImageType(
            file: $file,
            expectedType: IMAGETYPE_PNG,
        );
    }

    public function testSavesJpegImageByExtension(): void
    {
        $file = $this->root . DIRECTORY_SEPARATOR . 'image.jpg';

        ImageRenderer::save(
            image: $this->createImage(),
            file: $file,
            quality: 85,
            type: null,
        );

        self::assertFileExists($file);
        self::assertImageType(
            file: $file,
            expectedType: IMAGETYPE_JPEG,
        );
    }

    public function testSavesGifImageByExtension(): void
    {
        $file = $this->root . DIRECTORY_SEPARATOR . 'image.gif';

        ImageRenderer::save(
            image: $this->createImage(),
            file: $file,
            type: null,
        );

        self::assertFileExists($file);
        self::assertImageType(
            file: $file,
            expectedType: IMAGETYPE_GIF,
        );
    }

    public function testDoesNotOverwriteExistingCacheFile(): void
    {
        $file = $this->root . DIRECTORY_SEPARATOR . 'image.png';

        ImageRenderer::save(
            image: $this->createImage(),
            file: $file,
            quality: 9,
            type: AppGenerator::PNG,
        );

        $originalContent = file_get_contents($file);

        if ($originalContent === false) {
            self::fail('Unable to read generated image.');
        }

        ImageRenderer::save(
            image: $this->createImage(
                red: 0,
                green: 0,
                blue: 0,
            ),
            file: $file,
            quality: 9,
            type: AppGenerator::PNG,
        );

        self::assertSame(
            $originalContent,
            file_get_contents($file),
        );
    }

    public function testReturnsImageString(): void
    {
        $content = ImageRenderer::toString(
            image: $this->createImage(),
            type: AppGenerator::PNG,
            quality: 9,
        );

        self::assertNotSame('', $content);
        self::assertSame(
            IMAGETYPE_PNG,
            $this->detectImageTypeFromString(
                content: $content,
            ),
        );
    }

    public function testThrowsForUnsupportedSaveExtension(): void
    {
        $this->expectException(ImageTypeException::class);

        ImageRenderer::save(
            image: $this->createImage(),
            file: $this->root . DIRECTORY_SEPARATOR . 'image.bmp',
            quality: 85,
            type: null,
        );
    }

    public function testThrowsForUnsupportedOutputType(): void
    {
        $this->expectException(ImageTypeException::class);

        ImageRenderer::toString(
            image: $this->createImage(),
            type: 999,
            quality: 85,
        );
    }

    private function createImage(int $red = 255, int $green = 255, int $blue = 255): GdImage
    {
        $image = GdImageOperations::createTrueColor(
            width: 20,
            height: 20,
        );

        $color = GdImageOperations::allocateAlpha(
            image: $image,
            red: $red,
            green: $green,
            blue: $blue,
            alpha: 0,
        );

        GdImageOperations::filledRectangle(
            image: $image,
            x1: 0,
            y1: 0,
            x2: 19,
            y2: 19,
            color: $color,
        );

        return $image;
    }

    private static function assertImageType(string $file, int $expectedType): void
    {
        $size = getimagesize($file);

        if ($size === false) {
            self::fail(sprintf('Unable to read image "%s".', $file));
        }

        self::assertSame(
            $expectedType,
            $size[2],
        );
    }

    private function detectImageTypeFromString(string $content): int
    {
        $size = getimagesizefromstring($content);

        if ($size === false) {
            self::fail('Unable to detect image type from string.');
        }

        return $size[2];
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
