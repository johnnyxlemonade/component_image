<?php

declare(strict_types=1);

namespace Lemonade\Image\Tests\Unit\Exceptions;

use Lemonade\Image\Exceptions\Image\ImageProcessingException;
use Lemonade\Image\Exceptions\Image\ImageSourceException;
use PHPUnit\Framework\TestCase;

final class ImageSourceExceptionTest extends TestCase
{
    public function testCreatesMissingSourcePathException(): void
    {
        $exception = ImageSourceException::missingSourcePath();

        self::assertInstanceOf(ImageSourceException::class, $exception);
        self::assertInstanceOf(ImageProcessingException::class, $exception);
        self::assertSame(
            'Missing source image file path.',
            $exception->getMessage(),
        );
    }

    public function testCreatesFileNotFoundException(): void
    {
        $exception = ImageSourceException::fileNotFound(
            file: '/tmp/missing.png',
        );

        self::assertInstanceOf(ImageSourceException::class, $exception);
        self::assertInstanceOf(ImageProcessingException::class, $exception);
        self::assertSame(
            'Source image file "/tmp/missing.png" was not found.',
            $exception->getMessage(),
        );
    }
}
