<?php

declare(strict_types=1);

namespace Lemonade\Image\Tests\Unit\Options\Config;

use Lemonade\Image\Options\Config\ImageCanvasConfig;
use Lemonade\Image\Options\Config\ImageDimensionConfig;
use Lemonade\Image\Options\Config\ImageOptionsParserConfig;
use Lemonade\Image\Options\Config\ImageQualityConfig;
use Lemonade\Image\Options\Config\ImageSizePreset;
use Lemonade\Image\Options\Config\ImageSizePresetCollection;
use Lemonade\Image\Options\Config\ImageSizePresetConfig;
use PHPUnit\Framework\TestCase;

final class ImageOptionsParserConfigTest extends TestCase
{
    public function testCreatesDefaultConfig(): void
    {
        $config = ImageOptionsParserConfig::createDefault();

        self::assertSame('ffffff', $config->getCanvas()->getDefaultColor());
        self::assertSame(72, $config->getQuality()->getDefaultQuality());
        self::assertSame(1, $config->getQuality()->getMinQuality());
        self::assertSame(100, $config->getQuality()->getMaxQuality());
        self::assertSame(50, $config->getDimensions()->getMinWidth());
        self::assertSame(50, $config->getDimensions()->getMinHeight());
        self::assertSame(2560, $config->getDimensions()->getMaxWidth());
        self::assertSame(2560, $config->getDimensions()->getMaxHeight());
        self::assertSame(3, $config->getSizePresets()->getMaxScale());

        $preset = $config->getSizePresets()->getPresets()->get('md');

        self::assertNotNull($preset);
        self::assertSame(160, $preset->getWidth());
        self::assertSame(160, $preset->getHeight());
    }

    public function testKeepsCustomConfigParts(): void
    {
        $sizePresets = new ImageSizePresetConfig(
            presets: new ImageSizePresetCollection([
                ImageSizePreset::create(
                    code: 'card',
                    width: 600,
                    height: 400,
                ),
            ]),
            maxScale: 4,
        );

        $canvas = new ImageCanvasConfig(
            defaultColor: 'f5f5f5',
        );

        $quality = new ImageQualityConfig(
            defaultQuality: 85,
            minQuality: 10,
            maxQuality: 95,
        );

        $dimensions = new ImageDimensionConfig(
            minWidth: 100,
            minHeight: 80,
            maxWidth: 1200,
            maxHeight: 900,
        );

        $config = new ImageOptionsParserConfig(
            sizePresets: $sizePresets,
            canvas: $canvas,
            quality: $quality,
            dimensions: $dimensions,
        );

        self::assertSame($sizePresets, $config->getSizePresets());
        self::assertSame($canvas, $config->getCanvas());
        self::assertSame($quality, $config->getQuality());
        self::assertSame($dimensions, $config->getDimensions());
    }
}
