<?php

declare(strict_types=1);

namespace Lemonade\Image\Tests\Unit\Http;

use Lemonade\Image\Generator\AppGenerator;
use Lemonade\Image\Http\ImageHttpResponseFactory;
use Lemonade\Image\ImageResult;
use PHPUnit\Framework\TestCase;

final class ImageHttpResponseFactoryTest extends TestCase
{
    public function testCreatesBinaryResponseFromImageResult(): void
    {
        $image = AppGenerator::fromBlank(
            width: 10,
            height: 10,
            color: AppGenerator::rgb(
                red: 255,
                green: 255,
                blue: 255,
            ),
        );

        $result = new ImageResult(
            image: $image,
            type: AppGenerator::PNG,
            quality: 9,
        );

        $response = (new ImageHttpResponseFactory())->fromResult(
            result: $result,
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertTrue($response->isBinary());
        self::assertFalse($response->isFile());
        self::assertFalse($response->isNotModified());
        self::assertSame(AppGenerator::PNG, $response->getType());
        self::assertIsString($response->getContent());
        self::assertNotSame('', $response->getContent());
    }
}
