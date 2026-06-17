<?php

declare(strict_types=1);

namespace Lemonade\Image\Tests\Unit\Utils;

use GdImage;
use Lemonade\Image\Exceptions\Gd\GdImageCreateException;
use Lemonade\Image\Exceptions\Gd\GdImageLoadException;
use Lemonade\Image\Utils\GdImageOperations;
use PHPUnit\Framework\TestCase;

final class GdImageOperationsTest extends TestCase
{
    private string $temporaryFile;

    protected function tearDown(): void
    {
        if (isset($this->temporaryFile) && is_file($this->temporaryFile)) {
            unlink($this->temporaryFile);
        }
    }

    public function testCreatesTrueColorImage(): void
    {
        $image = GdImageOperations::createTrueColor(
            width: 100,
            height: 50,
        );

        self::assertInstanceOf(GdImage::class, $image);
        self::assertSame(100, imagesx($image));
        self::assertSame(50, imagesy($image));
        self::assertTrue(imageistruecolor($image));
    }

    public function testCreateTrueColorNormalizesInvalidSizeToOnePixel(): void
    {
        $image = GdImageOperations::createTrueColor(
            width: 0,
            height: -10,
        );

        self::assertSame(1, imagesx($image));
        self::assertSame(1, imagesy($image));
    }

    public function testAllocatesAlphaColorWithClampedValues(): void
    {
        $image = GdImageOperations::createTrueColor(
            width: 10,
            height: 10,
        );

        $color = GdImageOperations::allocateAlpha(
            image: $image,
            red: -10,
            green: 300,
            blue: 128,
            alpha: 200,
        );

        GdImageOperations::fill(
            image: $image,
            x: 0,
            y: 0,
            color: $color,
        );

        $pixel = imagecolorat(
            image: $image,
            x: 0,
            y: 0,
        );

        self::assertIsInt($pixel);
    }

    public function testFillsRectangle(): void
    {
        $image = GdImageOperations::createTrueColor(
            width: 20,
            height: 20,
        );

        $color = GdImageOperations::allocateAlpha(
            image: $image,
            red: 255,
            green: 0,
            blue: 0,
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

        self::assertIsInt(
            imagecolorat(
                image: $image,
                x: 10,
                y: 10,
            ),
        );
    }

    public function testCopiesImage(): void
    {
        $source = GdImageOperations::createTrueColor(
            width: 10,
            height: 10,
        );
        $destination = GdImageOperations::createTrueColor(
            width: 20,
            height: 20,
        );

        GdImageOperations::copy(
            destination: $destination,
            source: $source,
            dstX: 5,
            dstY: 5,
            srcX: 0,
            srcY: 0,
            srcWidth: 10,
            srcHeight: 10,
        );

        self::assertSame(20, imagesx($destination));
        self::assertSame(20, imagesy($destination));
    }

    public function testCopiesResampledImage(): void
    {
        $source = GdImageOperations::createTrueColor(
            width: 10,
            height: 10,
        );
        $destination = GdImageOperations::createTrueColor(
            width: 20,
            height: 20,
        );

        GdImageOperations::copyResampled(
            destination: $destination,
            source: $source,
            dstX: 0,
            dstY: 0,
            srcX: 0,
            srcY: 0,
            dstWidth: 20,
            dstHeight: 20,
            srcWidth: 10,
            srcHeight: 10,
        );

        self::assertSame(20, imagesx($destination));
        self::assertSame(20, imagesy($destination));
    }

    public function testSavesAlphaAndSetsAlphaBlending(): void
    {
        $image = GdImageOperations::createTrueColor(
            width: 10,
            height: 10,
        );

        GdImageOperations::saveAlpha(
            image: $image,
            enabled: true,
        );
        GdImageOperations::alphaBlending(
            image: $image,
            enabled: false,
        );

        self::assertSame(10, imagesx($image));
    }

    public function testCreatesImageFromPngFile(): void
    {
        $this->temporaryFile = $this->createTemporaryPngFile();

        $image = GdImageOperations::createFromFile(
            file: $this->temporaryFile,
            type: IMAGETYPE_PNG,
        );

        self::assertInstanceOf(GdImage::class, $image);
        self::assertSame(10, imagesx($image));
        self::assertSame(10, imagesy($image));
    }

    public function testThrowsWhenCreatingImageFromMissingFile(): void
    {
        $this->expectException(GdImageLoadException::class);

        GdImageOperations::createFromFile(
            file: sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lemonade-missing-image-' . uniqid('', true) . '.png',
            type: IMAGETYPE_PNG,
        );
    }

    public function testCreatesImageFromString(): void
    {
        $this->temporaryFile = $this->createTemporaryPngFile();

        $content = file_get_contents($this->temporaryFile);

        if ($content === false) {
            self::fail('Unable to read temporary PNG file.');
        }

        $image = GdImageOperations::createFromString(
            content: $content,
        );

        self::assertInstanceOf(GdImage::class, $image);
        self::assertSame(10, imagesx($image));
        self::assertSame(10, imagesy($image));
    }

    public function testThrowsWhenCreatingImageFromInvalidString(): void
    {
        $this->expectException(GdImageCreateException::class);

        GdImageOperations::createFromString(
            content: 'not an image',
        );
    }

    public function testOutputsPngFile(): void
    {
        $image = GdImageOperations::createTrueColor(
            width: 10,
            height: 10,
        );

        $this->temporaryFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lemonade-gd-output-' . uniqid('', true) . '.png';

        self::assertTrue(
            GdImageOperations::outputPng(
                image: $image,
                file: $this->temporaryFile,
                quality: 9,
            ),
        );
        self::assertFileExists($this->temporaryFile);

        $size = getimagesize($this->temporaryFile);

        if ($size === false) {
            self::fail('Unable to read generated PNG file.');
        }

        self::assertSame(
            IMAGETYPE_PNG,
            $size[2],
        );
    }

    private function createTemporaryPngFile(): string
    {
        $file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lemonade-gd-image-' . uniqid('', true) . '.png';

        $image = GdImageOperations::createTrueColor(
            width: 10,
            height: 10,
        );

        GdImageOperations::outputPng(
            image: $image,
            file: $file,
            quality: 9,
        );

        return $file;
    }
}
