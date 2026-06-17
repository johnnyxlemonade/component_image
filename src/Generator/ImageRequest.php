<?php

declare(strict_types=1);

namespace Lemonade\Image\Generator;

/**
 * Carries raw image request input from framework routing.
 *
 * This object represents one image request before it is converted into
 * concrete providers and filesystem paths.
 */
final class ImageRequest
{
    public function __construct(
        public readonly int $level,
        public readonly string|int|null $storageTypeId,
        public readonly string|int|null $moduleId,
        public readonly string|int|null $artId,
        public readonly ?string $baseName,
        public readonly ?string $args,
    ) {}
}
