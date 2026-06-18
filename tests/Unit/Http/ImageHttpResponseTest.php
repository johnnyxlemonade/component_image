<?php

declare(strict_types=1);

namespace Lemonade\Image\Tests\Unit\Http;

use Lemonade\Image\Generator\AppGenerator;
use Lemonade\Image\Http\ImageHttpResponse;
use PHPUnit\Framework\TestCase;

final class ImageHttpResponseTest extends TestCase
{
    public function testCreatesBinaryResponse(): void
    {
        $response = ImageHttpResponse::binary(
            content: 'image-content',
            type: AppGenerator::PNG,
            lastModified: 123456789,
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('image-content', $response->getContent());
        self::assertNull($response->getFile());
        self::assertSame(AppGenerator::PNG, $response->getType());
        self::assertSame(123456789, $response->getLastModified());
        self::assertTrue($response->isBinary());
        self::assertFalse($response->isFile());
        self::assertFalse($response->isNotModified());
    }

    public function testCreatesFileResponse(): void
    {
        $response = ImageHttpResponse::file(
            file: '/tmp/image.png',
            type: AppGenerator::PNG,
            lastModified: 123456789,
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertNull($response->getContent());
        self::assertSame('/tmp/image.png', $response->getFile());
        self::assertSame(AppGenerator::PNG, $response->getType());
        self::assertSame(123456789, $response->getLastModified());
        self::assertFalse($response->isBinary());
        self::assertTrue($response->isFile());
        self::assertFalse($response->isNotModified());
    }

    public function testCreatesNotModifiedResponse(): void
    {
        $response = ImageHttpResponse::notModified();

        self::assertSame(304, $response->getStatusCode());
        self::assertNull($response->getContent());
        self::assertNull($response->getFile());
        self::assertNull($response->getType());
        self::assertNull($response->getLastModified());
        self::assertFalse($response->isBinary());
        self::assertFalse($response->isFile());
        self::assertTrue($response->isNotModified());
    }
}
