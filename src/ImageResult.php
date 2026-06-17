<?php

declare(strict_types=1);

namespace Lemonade\Image;

use Lemonade\Image\Generator\AppGenerator;

/**
 * Carries a generated image together with its output metadata.
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
