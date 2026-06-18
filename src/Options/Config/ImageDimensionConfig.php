<?php

declare(strict_types=1);

namespace Lemonade\Image\Options\Config;

/**
 * Configures allowed parsed image dimension limits.
 *
 * @package     Lemonade
 * @subpackage  Image\Options\Config
 * @category    Options
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
 */
final class ImageDimensionConfig
{
    public function __construct(
        private readonly int $minWidth = 50,
        private readonly int $minHeight = 50,
        private readonly int $maxWidth = 2560,
        private readonly int $maxHeight = 2560,
    ) {}

    public function getMinWidth(): int
    {
        return $this->minWidth;
    }

    public function getMinHeight(): int
    {
        return $this->minHeight;
    }

    public function getMaxWidth(): int
    {
        return $this->maxWidth;
    }

    public function getMaxHeight(): int
    {
        return $this->maxHeight;
    }
}
