<?php

declare(strict_types=1);

namespace Lemonade\Image\Tests\Unit\Utils;

use Lemonade\Image\Exceptions\Image\ImageTypeException;
use Lemonade\Image\Generator\AppGenerator;
use Lemonade\Image\Utils\ImageFormat;
use PHPUnit\Framework\TestCase;

final class ImageFormatTest extends TestCase
{
    private string $temporaryFile;

    protected function tearDown(): void
    {
        if (isset($this->temporaryFile) && is_file($this->temporaryFile)) {
            unlink($this->temporaryFile);
        }
    }

    public function testReturnsSupportedFormatMap(): void
    {
        self::assertSame(
            [
                AppGenerator::JPEG => 'jpeg',
                AppGenerator::PNG => 'png',
                AppGenerator::GIF => 'gif',
                AppGenerator::WEBP => 'webp',
            ],
            ImageFormat::getFormats(),
        );
    }

    public function testConvertsTypeToExtension(): void
    {
        self::assertSame('jpeg', ImageFormat::typeToExtension(AppGenerator::JPEG));
        self::assertSame('png', ImageFormat::typeToExtension(AppGenerator::PNG));
        self::assertSame('gif', ImageFormat::typeToExtension(AppGenerator::GIF));
        self::assertSame('webp', ImageFormat::typeToExtension(AppGenerator::WEBP));
    }

    public function testConvertsTypeToMimeType(): void
    {
        self::assertSame('image/jpeg', ImageFormat::typeToMimeType(AppGenerator::JPEG));
        self::assertSame('image/png', ImageFormat::typeToMimeType(AppGenerator::PNG));
        self::assertSame('image/gif', ImageFormat::typeToMimeType(AppGenerator::GIF));
        self::assertSame('image/webp', ImageFormat::typeToMimeType(AppGenerator::WEBP));
    }

    public function testThrowsForUnsupportedExtensionType(): void
    {
        $this->expectException(ImageTypeException::class);

        ImageFormat::typeToExtension(999);
    }

    public function testDetectsPngTypeFromFile(): void
    {
        $this->temporaryFile = $this->createTemporaryPngFile();

        self::assertSame(
            AppGenerator::PNG,
            ImageFormat::detectTypeFromFile(
                file: $this->temporaryFile,
            ),
        );
    }

    public function testDetectsPngTypeFromString(): void
    {
        $content = AppGenerator::fromBlank(
            width: 10,
            height: 10,
            color: [
                'red' => 255,
                'green' => 255,
                'blue' => 255,
            ],
        )->toString(
            type: AppGenerator::PNG,
        );

        self::assertSame(
            AppGenerator::PNG,
            ImageFormat::detectTypeFromString(
                content: $content,
            ),
        );
    }

    public function testReturnsNullForUnknownFileType(): void
    {
        $this->temporaryFile = $this->createTemporaryTextFile();

        self::assertNull(
            ImageFormat::detectTypeFromFile(
                file: $this->temporaryFile,
            ),
        );
    }

    public function testReturnsNullForMissingFile(): void
    {
        self::assertNull(
            ImageFormat::detectTypeFromFile(
                file: sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lemonade-missing-image-' . uniqid('', true) . '.png',
            ),
        );
    }

    public function testReturnsNullForUnknownStringType(): void
    {
        self::assertNull(
            ImageFormat::detectTypeFromString(
                content: 'not an image',
            ),
        );
    }

    private function createTemporaryPngFile(): string
    {
        $file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lemonade-image-format-' . uniqid('', true) . '.png';

        AppGenerator::fromBlank(
            width: 10,
            height: 10,
            color: [
                'red' => 255,
                'green' => 255,
                'blue' => 255,
            ],
        )->save(
            file: $file,
            quality: 85,
            type: AppGenerator::PNG,
        );

        return $file;
    }

    private function createTemporaryTextFile(): string
    {
        $file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lemonade-image-format-' . uniqid('', true) . '.txt';

        file_put_contents(
            filename: $file,
            data: 'not an image',
        );

        return $file;
    }

}
