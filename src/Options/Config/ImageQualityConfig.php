<?php

declare(strict_types=1);

namespace Lemonade\Image\Options\Config;

/**
 * Configures default and allowed output quality range.
 *
 * @package     Lemonade
 * @subpackage  Image\Options\Config
 * @category    Options
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
 */
final class ImageQualityConfig
{
    public function __construct(
        private readonly int $defaultQuality = 72,
        private readonly int $minQuality = 1,
        private readonly int $maxQuality = 100,
    ) {}

    public function getDefaultQuality(): int
    {
        return $this->defaultQuality;
    }

    public function getMinQuality(): int
    {
        return $this->minQuality;
    }

    public function getMaxQuality(): int
    {
        return $this->maxQuality;
    }
}
