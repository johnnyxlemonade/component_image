<?php

declare(strict_types=1);

namespace Lemonade\Image\Tests\Unit\Generator;

use GdImage;
use Lemonade\Image\Exceptions\Image\ImageSourceException;
use Lemonade\Image\Exceptions\Image\ImageTypeException;
use Lemonade\Image\Exceptions\InvalidArgumentException;
use Lemonade\Image\Generator\AppGenerator;
use PHPUnit\Framework\TestCase;

final class AppGeneratorTest extends TestCase
{
    private string $temporaryFile;

    protected function tearDown(): void
    {
        if (isset($this->temporaryFile) && is_file($this->temporaryFile)) {
            unlink($this->temporaryFile);
        }
    }

    public function testCreatesBlankImage(): void
    {
        $image = AppGenerator::fromBlank(
            width: 120,
            height: 80,
            color: AppGenerator::rgb(
                red: 255,
                green: 255,
                blue: 255,
            ),
        );

        self::assertSame(120, $image->getWidth());
        self::assertSame(80, $image->getHeight());
        self::assertInstanceOf(GdImage::class, $image->getImageResource());
    }

    public function testThrowsWhenCreatingBlankImageWithInvalidSize(): void
    {
        $this->expectException(InvalidArgumentException::class);

        AppGenerator::fromBlank(
            width: 0,
            height: 80,
        );
    }

    public function testRgbClampsValues(): void
    {
        self::assertSame(
            [
                'red' => 0,
                'green' => 255,
                'blue' => 128,
                'alpha' => 127,
            ],
            AppGenerator::rgb(
                red: -10,
                green: 300,
                blue: 128,
                transparency: 200,
            ),
        );
    }

    public function testDetectsTypeFromFile(): void
    {
        $this->temporaryFile = $this->createTemporaryPngFile();

        self::assertSame(
            AppGenerator::PNG,
            AppGenerator::detectTypeFromFile(
                file: $this->temporaryFile,
            ),
        );
    }

    public function testDetectsTypeFromString(): void
    {
        $content = AppGenerator::fromBlank(
            width: 10,
            height: 10,
            color: AppGenerator::rgb(
                red: 255,
                green: 255,
                blue: 255,
            ),
        )->toString(
            type: AppGenerator::PNG,
        );

        self::assertSame(
            AppGenerator::PNG,
            AppGenerator::detectTypeFromString(
                s: $content,
            ),
        );
    }

    public function testConvertsTypeToExtensionAndMimeType(): void
    {
        self::assertSame('png', AppGenerator::typeToExtension(AppGenerator::PNG));
        self::assertSame('image/png', AppGenerator::typeToMimeType(AppGenerator::PNG));
    }

    public function testCreatesImageFromFile(): void
    {
        $this->temporaryFile = $this->createTemporaryPngFile();

        $image = AppGenerator::fromFile(
            file: $this->temporaryFile,
        );

        self::assertSame(20, $image->getWidth());
        self::assertSame(20, $image->getHeight());
    }

    public function testThrowsWhenCreatingImageFromMissingFile(): void
    {
        $this->expectException(ImageSourceException::class);

        AppGenerator::fromFile(
            file: sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lemonade-missing-image-' . uniqid('', true) . '.png',
        );
    }

    public function testThrowsWhenCreatingImageFromInvalidString(): void
    {
        $this->expectException(ImageTypeException::class);

        AppGenerator::fromString(
            s: 'not an image',
        );
    }

    public function testResizesImageByWidth(): void
    {
        $image = AppGenerator::fromBlank(
            width: 800,
            height: 600,
        );

        $image->resize(
            width: 400,
            height: null,
            mode: AppGenerator::FIT,
        );

        self::assertSame(400, $image->getWidth());
        self::assertSame(300, $image->getHeight());
    }

    public function testResizesImageToExactSize(): void
    {
        $image = AppGenerator::fromBlank(
            width: 800,
            height: 600,
        );

        $image->resize(
            width: 400,
            height: 400,
            mode: AppGenerator::EXACT,
        );

        self::assertSame(400, $image->getWidth());
        self::assertSame(400, $image->getHeight());
    }

    public function testCropsImage(): void
    {
        $image = AppGenerator::fromBlank(
            width: 800,
            height: 600,
        );

        $image->crop(
            left: '50%',
            top: '50%',
            width: 400,
            height: 300,
        );

        self::assertSame(400, $image->getWidth());
        self::assertSame(300, $image->getHeight());
    }

    public function testClonesImageResource(): void
    {
        $image = AppGenerator::fromBlank(
            width: 120,
            height: 80,
        );

        $clone = clone $image;

        self::assertNotSame(
            $image->getImageResource(),
            $clone->getImageResource(),
        );
        self::assertSame($image->getWidth(), $clone->getWidth());
        self::assertSame($image->getHeight(), $clone->getHeight());
    }

    public function testMagicReadAccessors(): void
    {
        $image = AppGenerator::fromBlank(
            width: 120,
            height: 80,
        );

        self::assertSame(120, $image->width);
        self::assertSame(80, $image->height);
        self::assertInstanceOf(GdImage::class, $image->imageResource);

        self::assertTrue(isset($image->width));
        self::assertTrue(isset($image->height));
        self::assertTrue(isset($image->imageResource));
    }

    public function testToStringReturnsImageContent(): void
    {
        $content = AppGenerator::fromBlank(
            width: 20,
            height: 20,
        )->toString(
            type: AppGenerator::PNG,
        );

        self::assertNotSame('', $content);

        $size = getimagesizefromstring($content);

        if ($size === false) {
            self::fail('Unable to read generated image string.');
        }

        self::assertSame(IMAGETYPE_PNG, $size[2]);
    }

    private function createTemporaryPngFile(): string
    {
        $file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lemonade-app-generator-' . uniqid('', true) . '.png';

        AppGenerator::fromBlank(
            width: 20,
            height: 20,
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

        return $file;
    }
}
