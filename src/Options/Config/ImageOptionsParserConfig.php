<?php

declare(strict_types=1);

namespace Lemonade\Image\Options\Config;

/**
 * Aggregates parser configuration for compact image option arguments.
 *
 * @package     Lemonade
 * @subpackage  Image\Options\Config
 * @category    Options
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
 */
final class ImageOptionsParserConfig
{
    public function __construct(
        private readonly ImageSizePresetConfig $sizePresets,
        private readonly ImageCanvasConfig $canvas,
        private readonly ImageQualityConfig $quality,
        private readonly ImageDimensionConfig $dimensions,
    ) {}

    public static function createDefault(): self
    {
        return new self(
            sizePresets: ImageSizePresetConfig::createDefault(),
            canvas: new ImageCanvasConfig(),
            quality: new ImageQualityConfig(),
            dimensions: new ImageDimensionConfig(),
        );
    }

    public function getSizePresets(): ImageSizePresetConfig
    {
        return $this->sizePresets;
    }

    public function getCanvas(): ImageCanvasConfig
    {
        return $this->canvas;
    }

    public function getQuality(): ImageQualityConfig
    {
        return $this->quality;
    }

    public function getDimensions(): ImageDimensionConfig
    {
        return $this->dimensions;
    }
}
