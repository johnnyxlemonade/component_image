<?php

declare(strict_types=1);

namespace Lemonade\Image\Options\Config;

/**
 * Configures default canvas color for parsed image options.
 *
 * @package     Lemonade
 * @subpackage  Image\Options\Config
 * @category    Options
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
 */
final class ImageCanvasConfig
{
    public function __construct(
        private readonly string $defaultColor = 'ffffff',
    ) {}

    public function getDefaultColor(): string
    {
        return $this->defaultColor;
    }
}
