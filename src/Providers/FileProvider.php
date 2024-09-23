<?php declare(strict_types=1);

namespace Lemonade\Image\Providers;

use Lemonade\Image\AppGenerator;
use Lemonade\Image\Exceptions\IOException;
use Lemonade\Image\Utils\FileSystem;

final class FileProvider
{

    /**
     * Cesta k souboru (fs)
     * @var string
     */
    private $_appFileFs = null;

    /**
     * Cesta k souboru (cache)
     * @var string
     */
    private $_appCacheFile = null;

    /**
     * Cesta k souboru (webp cache)
     * @var string
     */
    private $_appCacheWebp = null;

    /**
     * Placeholder (png)
     * @var string
     */
    private $_appMissingPng = null;

    /**
     * Placeholder (webp)
     * @var string
     */
    private $_appMissingWebp = null;

    /**
     * DirectoryProvider
     * @var DirectoryProvider
     */
    private $_appDir;

    /**
     * DataProvider
     * @var DataProvider
     */
    private $_appData;

    /**
     * LocalCache
     * @var integer
     */
    private $_localImageMTime = 0;


    /**
     * Costructor
     * @param DirectoryProvider $dir
     * @param DataProvider $data
     * @param string|null $file
     */
    public function __construct(DirectoryProvider $dir, DataProvider $data, string $file = null)
    {

        $this->_appDir = $dir;
        $this->_appData = $data;

        $this->_setFile(($file ?? "missing.png"));
    }


    /**
     *
     * @return string
     */
    public function getFileFs(): ?string
    {

        return $this->_appFileFs;
    }

    /**
     *
     * @return string
     */
    public function getCacheFile(): ?string
    {

        return $this->_appCacheFile;
    }

    /**
     *
     * @return string
     */
    public function getCacheWebp(): ?string
    {

        return $this->_appCacheWebp;
    }

    /**
     *
     * @return string
     */
    public function getMissingPng(): ?string
    {

        return $this->_appMissingPng;
    }

    /**
     *
     * @return string
     */
    public function getMissingWebp(): ?string
    {

        return $this->_appMissingWebp;
    }

    /**
     * Existence souboru
     *
     * @param string|null $file
     * @return bool
     */
    public function isFileExists(string $file = null): bool
    {

        if (empty($file)) {

            return false;
        }

        return file_exists($file);
    }

    /**
     * Vytvorit adresar
     * @param string $dir
     * @return void
     */
    public function createDirectory(string $dir): void
    {

        try {

            FileSystem::createDir(dirname($dir));

        } catch (IOException $e) {
        }

    }

    /**
     * Smazat soubory
     * @return void
     */
    public function deleteCache(): void
    {

        try {

            FileSystem::delete($this->getDirectory()->getCache());

        } catch (IOException $e) {
        }

    }

    /**
     *
     * @return DataProvider
     */
    public function getData(): DataProvider
    {

        return $this->_appData;
    }


    /**
     * @return DirectoryProvider
     */
    public function getDirectory(): DirectoryProvider
    {

        return $this->_appDir;
    }

    /**
     * @param string|null $file
     * @return void
     */
    protected function _setFile(string $file = null): void
    {

        $info = pathinfo($file);

        $this->_appFileFs = sprintf("%s/%s.%s", $this->_appDir->getStorage(), $info["filename"], $info["extension"]);
        $this->_appCacheFile = sprintf("%s/%s-%s.%s", $this->_appDir->getCache(), $info["filename"], $this->_appData->getHash(), $info["extension"]);
        $this->_appCacheWebp = sprintf("%s/%s-%s.webp", $this->_appDir->getCache(), $info["filename"], $this->_appData->getHash());
        $this->_appMissingPng = sprintf("./storage/0/cache/0/%s.png", $this->_appData->getHash());
        $this->_appMissingWebp = sprintf("./storage/0/cache/0/%s.webp", $this->_appData->getHash());

    }

    /**
     * @return bool
     */
    public function sendBrowserImage(): bool
    {

        if (!empty($_SERVER["HTTP_IF_MODIFIED_SINCE"])) {

            $fTime = 0;
            $sTime = (int)strtotime($_SERVER["HTTP_IF_MODIFIED_SINCE"]);

            if ($this->isFileExists($this->getFileFs())) {

                $file = (WebpProvider::hasSupport() ? $this->getCacheWebp() : $this->getCacheFile());

                if ($this->isFileExists($file)) {

                    $fTime = @filemtime($file);
                }

            }

            if (!$fTime || ((int)$sTime < 1) || ($sTime < $fTime)) {

                return false;

            } else {


                ImageProvider::setNoModified();

                return true;
            }
        }

        return false;

    }


    /**
     * @return bool
     */
    public function sendCacheImage(): bool
    {

        if ($this->isFileExists($this->getFileFs())) {

            $file = (WebpProvider::hasSupport() ? $this->getCacheWebp() : $this->getCacheFile());

            if ($this->isFileExists($file)) {

                $ext = (WebpProvider::hasSupport() ? AppGenerator::WEBP : AppGenerator::detectTypeFromFile($file));
                $data = file_get_contents($file);
                $size = filesize($file);

                ImageProvider::sendHeader($ext, $size);
                ImageProvider::sendContent($data);
            }
        }

        return false;

    }


}