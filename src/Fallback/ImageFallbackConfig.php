<?php

declare(strict_types=1);

namespace Lemonade\Image\Fallback;

/**
 * Configures fallback image generation defaults.
 *
 * @package     Lemonade
 * @subpackage  Image\Fallback
 * @category    Fallback
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
 */
final class ImageFallbackConfig
{
    public function __construct(
        private readonly int $defaultWidth = 600,
        private readonly int $defaultHeight = 600,
    ) {}

    public function getDefaultWidth(): int
    {
        return $this->defaultWidth;
    }

    public function getDefaultHeight(): int
    {
        return $this->defaultHeight;
    }
}
