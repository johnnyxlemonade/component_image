<?php

declare(strict_types=1);

namespace Lemonade\Image;

use Lemonade\Image\Generator\AppGenerator;

/**
 * Carries a generated image with output metadata.
 *
 * Groups the generated image instance, output type and quality value so cache
 * storage and response emitters can handle rendering consistently.
 *
 * @package     Lemonade
 * @subpackage  Image
 * @category    DTO
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
 */
final class ImageResult
{
    public function __construct(
        private readonly AppGenerator $image,
        private readonly int $type,
        private readonly int $quality,
    ) {}

    public function getImage(): AppGenerator
    {
        return $this->image;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function getQuality(): int
    {
        return $this->quality;
    }
}
