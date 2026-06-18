<?php

declare(strict_types=1);

namespace Lemonade\Image\Options\Config;

/**
 * Represents one named image size preset used by compact option parsing.
 *
 * @package     Lemonade
 * @subpackage  Image\Options\Config
 * @category    Options
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
 */
final class ImageSizePreset
{
    public function __construct(
        private readonly string $code,
        private readonly int $width,
        private readonly int $height,
    ) {}

    public static function create(string $code, int $width, int $height): self
    {
        return new self(
            code: $code,
            width: $width,
            height: $height,
        );
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function getHeight(): int
    {
        return $this->height;
    }
}
