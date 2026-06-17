<?php

declare(strict_types=1);

namespace Lemonade\Image;

use Lemonade\Image\Utils\PathHelper;

/**
 * Defines filesystem layout for image storage and cache paths.
 *
 * Provides configurable base directories and fallback cache identifiers used
 * by directory resolvers when building request-specific filesystem paths.
 *
 * @package     Lemonade
 * @subpackage  Image
 * @category    Configuration
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
 */
final class ImageStorageConfig
{
    public function __construct(
        private readonly string $storageRoot = '.',
        private readonly string $storageDirectory = 'storage',
        private readonly string $cacheDirectory = 'cache',
        private readonly string $fallbackModuleId = '0',
        private readonly string $fallbackStorageTypeId = '0',
    ) {}

    public static function createDefault(): self
    {
        return new self();
    }

    public function getStorageBase(): string
    {
        return PathHelper::join(
            $this->storageRoot,
            $this->storageDirectory,
        );
    }

    public function getCacheBase(): string
    {
        return PathHelper::join(
            $this->storageRoot,
            $this->storageDirectory,
            '0',
            $this->cacheDirectory,
        );
    }

    public function getFallbackCacheDirectory(): string
    {
        return PathHelper::join(
            $this->getCacheBase(),
            $this->fallbackModuleId,
            $this->fallbackStorageTypeId,
        );
    }
}
