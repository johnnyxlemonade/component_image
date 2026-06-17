<?php

declare(strict_types=1);

namespace Lemonade\Image\Tests\Unit\Utils;

use Lemonade\Image\Exceptions\InvalidArgumentException;
use Lemonade\Image\Generator\AppGenerator;
use Lemonade\Image\Utils\ImageGeometry;
use PHPUnit\Framework\TestCase;

final class ImageGeometryTest extends TestCase
{
    public function testCalculatesFitSizeByWidth(): void
    {
        self::assertSame(
            [400, 300],
            ImageGeometry::calculateSize(
                srcWidth: 800,
                srcHeight: 600,
                newWidth: 400,
                newHeight: null,
                mode: AppGenerator::FIT,
            ),
        );
    }

    public function testCalculatesFitSizeByHeight(): void
    {
        self::assertSame(
            [400, 300],
            ImageGeometry::calculateSize(
                srcWidth: 800,
                srcHeight: 600,
                newWidth: null,
                newHeight: 300,
                mode: AppGenerator::FIT,
            ),
        );
    }

    public function testCalculatesFitSizeInsideTargetBox(): void
    {
        self::assertSame(
            [400, 300],
            ImageGeometry::calculateSize(
                srcWidth: 800,
                srcHeight: 600,
                newWidth: 400,
                newHeight: 400,
                mode: AppGenerator::FIT,
            ),
        );
    }

    public function testCalculatesFillSizeCoveringTargetBox(): void
    {
        self::assertSame(
            [533, 400],
            ImageGeometry::calculateSize(
                srcWidth: 800,
                srcHeight: 600,
                newWidth: 400,
                newHeight: 400,
                mode: AppGenerator::FILL,
            ),
        );
    }

    public function testCalculatesStretchSize(): void
    {
        self::assertSame(
            [400, 400],
            ImageGeometry::calculateSize(
                srcWidth: 800,
                srcHeight: 600,
                newWidth: 400,
                newHeight: 400,
                mode: AppGenerator::STRETCH,
            ),
        );
    }

    public function testCalculatesPercentageStretchSize(): void
    {
        self::assertSame(
            [400, 300],
            ImageGeometry::calculateSize(
                srcWidth: 800,
                srcHeight: 600,
                newWidth: '50%',
                newHeight: '50%',
                mode: AppGenerator::FIT,
            ),
        );
    }

    public function testShrinkOnlyDoesNotUpscaleImage(): void
    {
        self::assertSame(
            [800, 600],
            ImageGeometry::calculateSize(
                srcWidth: 800,
                srcHeight: 600,
                newWidth: 1600,
                newHeight: 1200,
                mode: AppGenerator::FIT,
                shrinkOnly: true,
            ),
        );
    }

    public function testThrowsWhenNoTargetDimensionIsSpecified(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ImageGeometry::calculateSize(
            srcWidth: 800,
            srcHeight: 600,
            newWidth: null,
            newHeight: null,
        );
    }

    public function testThrowsWhenStretchHasMissingDimension(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ImageGeometry::calculateSize(
            srcWidth: 800,
            srcHeight: 600,
            newWidth: 400,
            newHeight: null,
            mode: AppGenerator::STRETCH,
        );
    }

    public function testCalculatesCenteredCutout(): void
    {
        self::assertSame(
            [200, 150, 400, 300],
            ImageGeometry::calculateCutout(
                srcWidth: 800,
                srcHeight: 600,
                left: '50%',
                top: '50%',
                newWidth: 400,
                newHeight: 300,
            ),
        );
    }

    public function testCalculatesPercentageCutoutSize(): void
    {
        self::assertSame(
            [200, 150, 400, 300],
            ImageGeometry::calculateCutout(
                srcWidth: 800,
                srcHeight: 600,
                left: '50%',
                top: '50%',
                newWidth: '50%',
                newHeight: '50%',
            ),
        );
    }

    public function testClampsCutoutWhenOffsetIsNegative(): void
    {
        self::assertSame(
            [0, 0, 300, 200],
            ImageGeometry::calculateCutout(
                srcWidth: 800,
                srcHeight: 600,
                left: -100,
                top: -100,
                newWidth: 400,
                newHeight: 300,
            ),
        );
    }

    public function testClampsCutoutToSourceBounds(): void
    {
        self::assertSame(
            [700, 500, 100, 100],
            ImageGeometry::calculateCutout(
                srcWidth: 800,
                srcHeight: 600,
                left: 700,
                top: 500,
                newWidth: 400,
                newHeight: 300,
            ),
        );
    }

    public function testResolvesOffsetFromPercentage(): void
    {
        self::assertSame(
            200,
            ImageGeometry::resolveOffset(
                offset: '50%',
                availableSpace: 400,
            ),
        );
    }

    public function testResolvesFlipMode(): void
    {
        self::assertSame(
            IMG_FLIP_HORIZONTAL,
            ImageGeometry::resolveFlipMode(
                width: -400,
                height: 300,
            ),
        );

        self::assertSame(
            IMG_FLIP_VERTICAL,
            ImageGeometry::resolveFlipMode(
                width: 400,
                height: -300,
            ),
        );

        self::assertSame(
            IMG_FLIP_BOTH,
            ImageGeometry::resolveFlipMode(
                width: '-400',
                height: '-300',
            ),
        );

        self::assertNull(
            ImageGeometry::resolveFlipMode(
                width: 400,
                height: 300,
            ),
        );
    }
}
