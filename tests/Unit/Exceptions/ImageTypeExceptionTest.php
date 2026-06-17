<?php

declare(strict_types=1);

namespace Lemonade\Image\Tests\Unit\Exceptions;

use Lemonade\Image\Exceptions\Image\ImageProcessingException;
use Lemonade\Image\Exceptions\Image\ImageTypeException;
use PHPUnit\Framework\TestCase;

final class ImageTypeExceptionTest extends TestCase
{
    public function testCreatesUnknownFileException(): void
    {
        $exception = ImageTypeException::unknownFile(
            file: '/tmp/image.dat',
        );

        self::assertInstanceOf(ImageTypeException::class, $exception);
        self::assertInstanceOf(ImageProcessingException::class, $exception);
        self::assertSame(
            'Unknown type of image file "/tmp/image.dat".',
            $exception->getMessage(),
        );
    }

    public function testCreatesUnknownStringException(): void
    {
        $exception = ImageTypeException::unknownString();

        self::assertInstanceOf(ImageTypeException::class, $exception);
        self::assertSame(
            'Unknown type of image string.',
            $exception->getMessage(),
        );
    }

    public function testCreatesUnsupportedTypeExceptionFromInteger(): void
    {
        $exception = ImageTypeException::unsupported(
            type: 999,
        );

        self::assertInstanceOf(ImageTypeException::class, $exception);
        self::assertSame(
            'Unsupported image type "999".',
            $exception->getMessage(),
        );
    }

    public function testCreatesUnsupportedTypeExceptionFromString(): void
    {
        $exception = ImageTypeException::unsupported(
            type: 'bmp',
        );

        self::assertInstanceOf(ImageTypeException::class, $exception);
        self::assertSame(
            'Unsupported image type "bmp".',
            $exception->getMessage(),
        );
    }

    public function testCreatesUnsupportedExtensionException(): void
    {
        $exception = ImageTypeException::unsupportedExtension(
            extension: 'bmp',
        );

        self::assertInstanceOf(ImageTypeException::class, $exception);
        self::assertSame(
            'Unsupported image file extension "bmp".',
            $exception->getMessage(),
        );
    }

    public function testCreatesDetectionFailedException(): void
    {
        $exception = ImageTypeException::detectionFailed();

        self::assertInstanceOf(ImageTypeException::class, $exception);
        self::assertSame(
            'Unable to detect source image type.',
            $exception->getMessage(),
        );
    }
}
