<?php

declare(strict_types=1);

namespace Lemonade\Image\Options\Config;

/**
 * Configures available size presets and their maximum scale factor.
 *
 * @package     Lemonade
 * @subpackage  Image\Options\Config
 * @category    Options
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
 */
final class ImageSizePresetConfig
{
    public function __construct(
        private readonly ImageSizePresetCollection $presets,
        private readonly int $maxScale = 3,
    ) {}

    public static function createDefault(): self
    {
        return new self(
            presets: ImageSizePresetCollection::createDefault(),
            maxScale: 3,
        );
    }

    public function getPresets(): ImageSizePresetCollection
    {
        return $this->presets;
    }

    public function getMaxScale(): int
    {
        return $this->maxScale;
    }
}
