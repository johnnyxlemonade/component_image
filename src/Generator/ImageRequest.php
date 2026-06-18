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
    public static function create(
        int $level,
        string|int|null $storageTypeId,
        string|int|null $moduleId,
        string|int|null $artId,
        ?string $baseName,
        ?string $args,
    ): self {
        return new self(
            level: $level,
            storageTypeId: $storageTypeId,
            moduleId: $moduleId,
            artId: $artId,
            baseName: $baseName,
            args: $args,
        );
    }

    public function __construct(
        private readonly int $level,
        private readonly string|int|null $storageTypeId,
        private readonly string|int|null $moduleId,
        private readonly string|int|null $artId,
        private readonly ?string $baseName,
        private readonly ?string $args,
    ) {}

    public function getLevel(): int
    {
        return $this->level;
    }

    public function getStorageTypeId(): string|int|null
    {
        return $this->storageTypeId;
    }

    public function getModuleId(): string|int|null
    {
        return $this->moduleId;
    }

    public function getArtId(): string|int|null
    {
        return $this->artId;
    }

    public function getBaseName(): ?string
    {
        return $this->baseName;
    }

    public function getArgs(): ?string
    {
        return $this->args;
    }
}
