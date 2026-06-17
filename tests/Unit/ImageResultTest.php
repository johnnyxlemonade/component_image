<?php

declare(strict_types=1);

namespace Lemonade\Image\Tests\Unit;

use Lemonade\Image\Generator\AppGenerator;
use Lemonade\Image\ImageResult;
use PHPUnit\Framework\TestCase;

final class ImageResultTest extends TestCase
{
    public function testCarriesGeneratedImageOutputMetadata(): void
    {
        $image = AppGenerator::fromBlank(
            width: 100,
            height: 50,
            color: [
                'red' => 255,
                'green' => 255,
                'blue' => 255,
            ],
        );

        $result = new ImageResult(
            image: $image,
            type: AppGenerator::PNG,
            quality: 85,
        );

        self::assertSame($image, $result->getImage());
        self::assertSame(AppGenerator::PNG, $result->getType());
        self::assertSame(85, $result->getQuality());
    }

    public function testAllowsWebpOutputType(): void
    {
        $image = AppGenerator::fromBlank(
            width: 100,
            height: 50,
            color: [
                'red' => 245,
                'green' => 245,
                'blue' => 245,
            ],
        );

        $result = new ImageResult(
            image: $image,
            type: AppGenerator::WEBP,
            quality: 90,
        );

        self::assertSame($image, $result->getImage());
        self::assertSame(AppGenerator::WEBP, $result->getType());
        self::assertSame(90, $result->getQuality());
    }
}
