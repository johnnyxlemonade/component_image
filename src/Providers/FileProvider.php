<?php

declare(strict_types=1);

namespace Lemonade\Image\Providers;

use Lemonade\Image\AppGenerator;
use Lemonade\Image\Exceptions\IOException;
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
    private ?string $appFileFs      = null;
    private ?string $appCacheFile   = null;
    private ?string $appCacheWebp   = null;
    private ?string $appMissingPng  = null;
    private ?string $appMissingWebp = null;

    /**
     * Vytvoří file provider kontext.
     */
    public function __construct(
        private readonly DirectoryProvider $directory,
        private readonly DataProvider $data,
        private readonly FileSystem $filesystem,
        ?string $file = null
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
     * Ověří existenci souboru.
     */
    public function isFileExists(?string $file): bool
    {
        return $file !== null && $file !== '' && file_exists($file);
    }

    /**
     * Vytvoří adresář pro daný soubor.
     */
    public function createDirectory(string $file): void
    {
        try {
            $this->filesystem->createDirForFile($file);
        } catch (IOException) {
            // silent by design
        }
    }

    /**
     * Smaže cache adresář.
     */
    public function deleteCache(): void
    {
        try {
            $this->filesystem->delete($this->directory->getCache());
        } catch (IOException) {
            // silent by design
        }
    }

    /**
     * Ošetří HTTP 304 Not Modified.
     */
    public function sendBrowserImage(): bool
    {
        if (!ServerProvider::has('HTTP_IF_MODIFIED_SINCE')) {
            return false;
        }

        $sTime = (int) strtotime(
            ServerProvider::get('HTTP_IF_MODIFIED_SINCE')
        );

        if ($sTime < 1 || !$this->isFileExists($this->appFileFs)) {
            return false;
        }

        $cacheFile = $this->resolveCacheFile();
        if ($cacheFile === null || !$this->isFileExists($cacheFile)) {
            return false;
        }

        $fTime = (int) @filemtime($cacheFile);
        if ($fTime === 0 || $sTime < $fTime) {
            return false;
        }

        ImageProvider::setNoModified();

        return true;
    }

    /**
     * Odešle existující cache obrázek.
     */
    public function sendCacheImage(): bool
    {
        if (!$this->isFileExists($this->appFileFs)) {
            return false;
        }

        $cacheFile = $this->resolveCacheFile();
        if ($cacheFile === null || !$this->isFileExists($cacheFile)) {
            return false;
        }

        $content = file_get_contents($cacheFile);
        if ($content === false) {
            return false;
        }

        $size = filesize($cacheFile);
        if ($size === false) {
            return false;
        }

        $type = $this->resolveOutputType($cacheFile);

        if ($type === null) {
            return false;
        }

        ImageProvider::sendHeader($type, $size);
        ImageProvider::sendContent($content);
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
            $extension
        );

        $cacheHash = substr(
            sha1($this->appFileFs . '|' . $this->data->getHash()),
            0,
            32
        );

        $this->appCacheFile = sprintf(
            '%s/%s-%s.%s',
            $this->directory->getCache(),
            $filename,
            $cacheHash,
            $extension
        );

        $this->appCacheWebp = sprintf(
            '%s/%s-%s.webp',
            $this->directory->getCache(),
            $filename,
            $cacheHash
        );

        // error / missing – pouze podle varianty
        $this->appMissingPng = sprintf(
            './storage/0/cache/0/%s.png',
            $this->data->getHash()
        );

        $this->appMissingWebp = sprintf(
            './storage/0/cache/0/%s.webp',
            $this->data->getHash()
        );
    }

    /**
     * Vrátí správný cache soubor podle podpory WebP.
     */
    private function resolveCacheFile(): ?string
    {
        return WebpProvider::hasSupport()
            ? $this->appCacheWebp
            : $this->appCacheFile;
    }

    /**
     * Detekuje MIME typ pro odeslání cache souboru.
     */
    private function resolveOutputType(string $file): ?int
    {
        if (WebpProvider::hasSupport()) {
            return AppGenerator::WEBP;
        }

        return AppGenerator::detectTypeFromFile($file);
    }
}
