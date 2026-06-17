<?php

declare(strict_types=1);

namespace Lemonade\Image\Generator;

/**
 * Carries raw image request input.
 *
 * Preserves framework and routing parameters before they are converted into
 * resolved directories, parsed options and request-specific file context.
 *
 * @package     Lemonade
 * @subpackage  Image
 * @category    DTO
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
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
