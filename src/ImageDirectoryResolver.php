<?php

declare(strict_types=1);

namespace Lemonade\Image;

use Lemonade\Image\Utils\PathHelper;

use function array_key_exists;
use function chunk_split;
use function dechex;
use function is_string;
use function rtrim;
use function sprintf;
use function str_pad;

use const DIRECTORY_SEPARATOR;
use const STR_PAD_LEFT;

/**
 * Resolves storage and cache directories for generated image files.
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
        $directoryId = $this->resolveStorageTypeId(
            typeId: $storageTypeId,
        );

        $structure = $this->buildDirectoryStructure(
            artId: $artId,
        );

        $this->storageDirectory = PathHelper::join(
            $this->config->getStorageBase(),
            (string) ($moduleId ?? '0'),
            $directoryId,
            $structure,
        );

        $this->cacheDirectory = PathHelper::join(
            $this->config->getCacheBase(),
            (string) ($moduleId ?? '0'),
            $directoryId,
            $structure,
        );
    }

    private function buildDirectoryStructure(string|int|null $artId): string
    {
        return rtrim(
            chunk_split(
                str_pad(
                    dechex((int) $artId),
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

        return (string) $typeId;
    }
}
