<?php

declare(strict_types=1);

namespace Lemonade\Image\Tests\Unit\Value;

use Lemonade\Image\Value\RgbColor;
use PHPUnit\Framework\TestCase;

final class RgbColorTest extends TestCase
{
    public function testCreatesRgbArrayFromHexColor(): void
    {
        $color = RgbColor::fromHex('285aa0');

        self::assertSame(
            [
                'red' => 40,
                'green' => 90,
                'blue' => 160,
            ],
            $color->toArray(),
        );
    }

    public function testCreatesRgbArrayFromHashPrefixedHexColor(): void
    {
        $color = RgbColor::fromHex('#50783c');

        self::assertSame(
            [
                'red' => 80,
                'green' => 120,
                'blue' => 60,
            ],
            $color->toArray(),
        );
    }

    public function testInvalidHexFallsBackToBlack(): void
    {
        $color = RgbColor::fromHex('invalid');

        self::assertSame(
            [
                'red' => 0,
                'green' => 0,
                'blue' => 0,
            ],
            $color->toArray(),
        );
    }

    public function testShortHexFallsBackToBlack(): void
    {
        $color = RgbColor::fromHex('fff');

        self::assertSame(
            [
                'red' => 0,
                'green' => 0,
                'blue' => 0,
            ],
            $color->toArray(),
        );
    }

    public function testConstructorValuesAreClampedToRgbRange(): void
    {
        $color = new RgbColor(
            red: -10,
            green: 300,
            blue: 128,
        );

        self::assertSame(
            [
                'red' => 0,
                'green' => 255,
                'blue' => 128,
            ],
            $color->toArray(),
        );
    }
}
