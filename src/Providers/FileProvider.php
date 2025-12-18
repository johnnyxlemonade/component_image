<?php declare(strict_types=1);

namespace Lemonade\Image\Providers;

use Lemonade\Image\AppGenerator;
use Lemonade\Image\Exceptions\IOException;
use Lemonade\Image\Utils\FileSystem;

/**
 * FileProvider
 *
 * Provider zodpovědný za práci se soubory v image pipeline.
 * Řeší mapování:
 *
 * - originálního souboru (filesystem)
 * - cache variant (PNG / WebP)
 * - placeholder / error obrázků
 *
 * a jejich bezpečné odeslání klientovi.
 *
 * Zodpovědnosti:
 * - sestavení cest k originálu a cache na základě DirectoryProvider + DataProvider
 * - deterministický cache klíč (origin + args)
 * - rozhodování o výstupu (WebP vs originální formát)
 * - obsluha HTTP cache hlaviček (If-Modified-Since → 304)
 * - bezpečné čtení a odesílání cache souborů
 * - mazání cache při chybových stavech
 *
 * FileProvider:
 * - negeneruje obrázky
 * - neřeší transformace
 * - neřeší validaci vstupů
 *
 * Slouží výhradně jako „file & cache orchestrace“ mezi:
 * - DirectoryProvider (kde leží data)
 * - DataProvider (jaká varianta se chce)
 * - ImageProvider (kdo obrázek vytvoří / odešle)
 *
 * @package     Lemonade Framework
 * @subpackage  Image\Providers
 * @category    Providers
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
 * @see         DirectoryProvider
 * @see         DataProvider
 * @see         ImageProvider
 * @see         WebpProvider
 */
final class FileProvider
{
    private ?string $appFileFs       = null;
    private ?string $appCacheFile    = null;
    private ?string $appCacheWebp    = null;
    private ?string $appMissingPng   = null;
    private ?string $appMissingWebp  = null;

    private DirectoryProvider $appDir;
    private DataProvider $appData;

    private int $localImageMTime = 0;

    /**
     * Vytvoří file provider kontext.
     */
    public function __construct(
        DirectoryProvider $dir,
        DataProvider $data,
        ?string $file = null
    ) {
        $this->appDir  = $dir;
        $this->appData = $data;

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
        return $this->appData;
    }

    /**
     * Vrátí directory provider.
     */
    public function getDirectory(): DirectoryProvider
    {
        return $this->appDir;
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
    public function createDirectory(string $dir): void
    {
        try {
            FileSystem::createDir(dirname($dir));
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
            FileSystem::delete($this->appDir->getCache());
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
        if (!$this->isFileExists($cacheFile)) {
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
        if (!$this->isFileExists($cacheFile)) {
            return false;
        }

        $ext  = $this->resolveOutputType($cacheFile);
        $data = file_get_contents($cacheFile);
        $size = filesize($cacheFile);

        ImageProvider::sendHeader($ext, $size);
        ImageProvider::sendContent($data);

        return true;
    }

    /**
     * Inicializuje cesty k souborům.
     */
    protected function setFile(string $file): void
    {
        $info = pathinfo($file);

        $this->appFileFs = sprintf(
            '%s/%s.%s',
            $this->appDir->getStorage(),
            $info['filename'],
            $info['extension']
        );

        // === ROZŠÍŘENÝ HASH (origin + args) ===
        $cacheHash = substr(
            sha1($this->appFileFs . '|' . $this->appData->getHash()),
            0,
            32
        );

        $this->appCacheFile = sprintf(
            '%s/%s-%s.%s',
            $this->appDir->getCache(),
            $info['filename'],
            $cacheHash,
            $info['extension']
        );

        $this->appCacheWebp = sprintf(
            '%s/%s-%s.webp',
            $this->appDir->getCache(),
            $info['filename'],
            $cacheHash
        );

        // error / missing – pouze podle varianty
        $this->appMissingPng = sprintf(
            './storage/0/cache/0/%s.png',
            $this->appData->getHash()
        );

        $this->appMissingWebp = sprintf(
            './storage/0/cache/0/%s.webp',
            $this->appData->getHash()
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
    private function resolveOutputType(string $file): int
    {
        return WebpProvider::hasSupport()
            ? AppGenerator::WEBP
            : AppGenerator::detectTypeFromFile($file);
    }
}
