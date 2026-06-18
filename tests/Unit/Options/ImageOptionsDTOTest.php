<?php

declare(strict_types=1);

namespace Lemonade\Image\Tests\Unit\Options;

use Lemonade\Image\Options\ImageOptionsDTO;
use Lemonade\Image\Options\ImageResizeMode;
use PHPUnit\Framework\TestCase;

final class ImageOptionsDTOTest extends TestCase
{
    public function testExposesOptionValues(): void
    {
        $options = $this->createOptions();

        self::assertSame(320, $options->getWidth());
        self::assertSame(240, $options->getHeight());
        self::assertSame(ImageResizeMode::FitWithCanvas, $options->getResizeMode());
        self::assertSame('ffffff', $options->getCanvasColor());
        self::assertSame(85, $options->getQuality());
        self::assertTrue($options->isMissing());
    }

    public function testDetectsMissingSizeOnlyWhenBothDimensionsAreNull(): void
    {
        self::assertTrue(
            (new ImageOptionsDTO(
                width: null,
                height: null,
                resizeMode: ImageResizeMode::Shrink,
                canvasColor: 'ffffff',
                quality: 85,
                missing: true,
            ))->isMissingAllSize(),
        );

        self::assertFalse(
            (new ImageOptionsDTO(
                width: 320,
                height: null,
                resizeMode: ImageResizeMode::Shrink,
                canvasColor: 'ffffff',
                quality: 85,
                missing: true,
            ))->isMissingAllSize(),
        );

        self::assertFalse(
            (new ImageOptionsDTO(
                width: null,
                height: 240,
                resizeMode: ImageResizeMode::Shrink,
                canvasColor: 'ffffff',
                quality: 85,
                missing: true,
            ))->isMissingAllSize(),
        );
    }

    public function testWithWidthReturnsModifiedCopy(): void
    {
        $original = $this->createOptions();
        $modified = $original->withWidth(
            width: 640,
        );

        self::assertNotSame($original, $modified);
        self::assertSame(320, $original->getWidth());
        self::assertSame(640, $modified->getWidth());
        self::assertSame(240, $modified->getHeight());
    }

    public function testWithHeightReturnsModifiedCopy(): void
    {
        $original = $this->createOptions();
        $modified = $original->withHeight(
            height: 480,
        );

        self::assertNotSame($original, $modified);
        self::assertSame(240, $original->getHeight());
        self::assertSame(480, $modified->getHeight());
        self::assertSame(320, $modified->getWidth());
    }

    public function testWithResizeModeReturnsModifiedCopy(): void
    {
        $original = $this->createOptions();
        $modified = $original->withResizeMode(
            resizeMode: ImageResizeMode::Exact,
        );

        self::assertNotSame($original, $modified);
        self::assertSame(ImageResizeMode::FitWithCanvas, $original->getResizeMode());
        self::assertSame(ImageResizeMode::Exact, $modified->getResizeMode());
    }

    public function testWithCanvasColorReturnsModifiedCopy(): void
    {
        $original = $this->createOptions();
        $modified = $original->withCanvasColor(
            color: 'f7f7f7',
        );

        self::assertNotSame($original, $modified);
        self::assertSame('ffffff', $original->getCanvasColor());
        self::assertSame('f7f7f7', $modified->getCanvasColor());
    }

    public function testWithQualityReturnsModifiedCopy(): void
    {
        $original = $this->createOptions();
        $modified = $original->withQuality(
            quality: 90,
        );

        self::assertNotSame($original, $modified);
        self::assertSame(85, $original->getQuality());
        self::assertSame(90, $modified->getQuality());
    }

    public function testWithMissingReturnsModifiedCopy(): void
    {
        $original = $this->createOptions();
        $modified = $original->withMissing(
            missing: false,
        );

        self::assertNotSame($original, $modified);
        self::assertTrue($original->isMissing());
        self::assertFalse($modified->isMissing());
    }

    public function testHashIsStableForSameValues(): void
    {
        $first = $this->createOptions();
        $second = $this->createOptions();

        self::assertSame($first->getHash(), $second->getHash());
        self::assertSame($first->toHash(), $first->getHash());
    }

    public function testHashChangesWhenValueChanges(): void
    {
        $original = $this->createOptions();
        $modified = $original->withQuality(
            quality: 90,
        );

        self::assertNotSame($original->getHash(), $modified->getHash());
    }

    private function createOptions(): ImageOptionsDTO
    {
        return new ImageOptionsDTO(
            width: 320,
            height: 240,
            resizeMode: ImageResizeMode::FitWithCanvas,
            canvasColor: 'ffffff',
            quality: 85,
            missing: true,
        );
    }
}
