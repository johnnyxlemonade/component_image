<?php

declare(strict_types=1);

namespace Lemonade\Image\Filesystem;

use Lemonade\Image\Exceptions\InvalidStateException;
use Lemonade\Image\ImageStorageConfig;
use Lemonade\Image\Utils\PathHelper;

use function array_key_exists;
use function chunk_split;
use function dechex;
use function is_string;
use function preg_match;
use function rtrim;
use function sprintf;
use function str_pad;

use const DIRECTORY_SEPARATOR;
use const STR_PAD_LEFT;

/**
 * Resolves storage and cache directories for generated image files.
 *
 * Builds deterministic storage, cache and fallback cache paths from request
 * identifiers and storage configuration.
 *
 * @package     Lemonade
 * @subpackage  Image\Filesystem
 * @category    Resolver
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
 */
final class ImageDirectoryResolver
{
    private const STORAGE_TYPE_MAP = [
        'template' => 'template',
        'thumbnail' => 1,
        'gallery' => 2,
        'editor' => 5,
    ];

    private string $storageDirectory;
    private string $cacheDirectory;

    public function __construct(
        private readonly ImageStorageConfig $config,
        private readonly int $level,
        string|int|null $storageTypeId = null,
        string|int|null $moduleId = null,
        string|int|null $artId = null,
    ) {
        $this->resolveDirectories(
            storageTypeId: $storageTypeId,
            moduleId: $moduleId,
            artId: $artId,
        );
    }

    public function getStorage(): string
    {
        return $this->storageDirectory;
    }

    public function getCache(): string
    {
        return $this->cacheDirectory;
    }

    public function getFallbackCache(): string
    {
        return $this->config->getFallbackCacheDirectory();
    }

    public function getFallbackPng(string $hash): string
    {
        return PathHelper::file(
            directory: $this->getFallbackCache(),
            filename: sprintf(
                '%s.png',
                $hash,
            ),
        );
    }

    public function getFallbackWebp(string $hash): string
    {
        return PathHelper::file(
            directory: $this->getFallbackCache(),
            filename: sprintf(
                '%s.webp',
                $hash,
            ),
        );
    }

    private function resolveDirectories(
        string|int|null $storageTypeId = null,
        string|int|null $moduleId = null,
        string|int|null $artId = null,
    ): void {
        $moduleDirectory = $this->resolvePathSegment(
            value: $moduleId,
            fallback: '0',
            name: 'moduleId',
        );

        $directoryId = $this->resolveStorageTypeId(
            typeId: $storageTypeId,
        );

        $structure = $this->buildDirectoryStructure(
            artId: $artId,
        );

        $this->storageDirectory = PathHelper::join(
            $this->config->getStorageBase(),
            $moduleDirectory,
            $directoryId,
            $structure,
        );

        $this->cacheDirectory = PathHelper::join(
            $this->config->getCacheBase(),
            $moduleDirectory,
            $directoryId,
            $structure,
        );
    }

    private function buildDirectoryStructure(string|int|null $artId): string
    {
        $normalizedArtId = $this->resolveNumericId(
            value: $artId,
            fallback: 0,
            name: 'artId',
        );

        return rtrim(
            chunk_split(
                str_pad(
                    dechex($normalizedArtId),
                    $this->level,
                    '0',
                    STR_PAD_LEFT,
                ),
                2,
                DIRECTORY_SEPARATOR,
            ),
            DIRECTORY_SEPARATOR,
        );
    }

    private function resolveStorageTypeId(string|int|null $typeId): string
    {
        if ($typeId === null) {
            return '0';
        }

        if (is_string($typeId) && array_key_exists($typeId, self::STORAGE_TYPE_MAP)) {
            return (string) self::STORAGE_TYPE_MAP[$typeId];
        }

        return $this->resolvePathSegment(
            value: $typeId,
            fallback: '0',
            name: 'storageTypeId',
        );
    }

    private function resolvePathSegment(
        string|int|null $value,
        string $fallback,
        string $name,
    ): string {
        if ($value === null || $value === '') {
            return $fallback;
        }

        $segment = (string) $value;

        if (preg_match('~^[a-zA-Z0-9_-]+$~', $segment) !== 1) {
            throw new InvalidStateException(
                sprintf(
                    'Invalid image path segment "%s".',
                    $name,
                ),
            );
        }

        return $segment;
    }

    private function resolveNumericId(
        string|int|null $value,
        int $fallback,
        string $name,
    ): int {
        if ($value === null || $value === '') {
            return $fallback;
        }

        if (is_int($value)) {
            if ($value < 0) {
                throw new InvalidStateException(
                    sprintf(
                        'Invalid negative image identifier "%s".',
                        $name,
                    ),
                );
            }

            return $value;
        }

        if (preg_match('~^\d+$~', $value) !== 1) {
            throw new InvalidStateException(
                sprintf(
                    'Invalid image numeric identifier "%s".',
                    $name,
                ),
            );
        }

        return (int) $value;
    }
}
