<?php

declare(strict_types=1);

namespace Lemonade\Image\Providers;

use Lemonade\Image\Utils\FileSystem;

/**
 * Resolves source, cache and fallback file paths for image processing.
 *
 * Coordinates filesystem checks, cache paths and browser cache shortcuts
 * for a single image request.
 *
 * @package     Lemonade
 * @subpackage  Image\Providers
 * @category    Provider
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
 *
 * @see DirectoryProvider
 * @see DataProvider
 * @see ImageProvider
 */
final class FileProvider
{
    private ?string $appFileFs = null;
    private ?string $appCacheFile = null;
    private ?string $appCacheWebp = null;
    private ?string $appMissingPng = null;
    private ?string $appMissingWebp = null;

    /**
     * Vytvoří file provider kontext.
     */
    public function __construct(
        private readonly DirectoryProvider $directory,
        private readonly DataProvider $data,
        private readonly FileSystem $filesystem,
        ?string $file = null,
    ) {
        $this->setFile($file ?? 'missing.png');
    }

    /**
     * Vrátí cestu k originálnímu souboru.
     */
    public function getFileFs(): ?string
    {
        return $this->appFileFs;
    }

    /**
     * Vrátí cestu ke cache souboru.
     */
    public function getCacheFile(): ?string
    {
        return $this->appCacheFile;
    }

    /**
     * Vrátí cestu ke cache WebP souboru.
     */
    public function getCacheWebp(): ?string
    {
        return $this->appCacheWebp;
    }

    /**
     * Vrátí cestu k PNG placeholderu.
     */
    public function getMissingPng(): ?string
    {
        return $this->appMissingPng;
    }

    /**
     * Vrátí cestu k WebP placeholderu.
     */
    public function getMissingWebp(): ?string
    {
        return $this->appMissingWebp;
    }

    /**
     * Vrátí data provider.
     */
    public function getData(): DataProvider
    {
        return $this->data;
    }

    /**
     * Vrátí directory provider.
     */
    public function getDirectory(): DirectoryProvider
    {
        return $this->directory;
    }

    /**
     * Returns filesystem utility used by this file provider.
     */
    public function getFilesystem(): FileSystem
    {
        return $this->filesystem;
    }

    /**
     * Inicializuje cesty k souborům.
     */
    protected function setFile(string $file): void
    {
        $info = pathinfo($file);

        $filename = $info['filename'] !== '' ? $info['filename'] : 'missing';
        $extension = $info['extension'] ?? 'png';

        $this->appFileFs = sprintf(
            '%s/%s.%s',
            $this->directory->getStorage(),
            $filename,
            $extension,
        );

        $cacheHash = substr(
            sha1($this->appFileFs . '|' . $this->data->getHash()),
            0,
            32,
        );

        $this->appCacheFile = sprintf(
            '%s/%s-%s.%s',
            $this->directory->getCache(),
            $filename,
            $cacheHash,
            $extension,
        );

        $this->appCacheWebp = sprintf(
            '%s/%s-%s.webp',
            $this->directory->getCache(),
            $filename,
            $cacheHash,
        );

        // error / missing – pouze podle varianty
        $this->appMissingPng = sprintf(
            './storage/0/cache/0/%s.png',
            $this->data->getHash(),
        );

        $this->appMissingWebp = sprintf(
            './storage/0/cache/0/%s.webp',
            $this->data->getHash(),
        );
    }

}
