<?php declare(strict_types=1);

namespace Lemonade\Image\Providers;

final class DirectoryProvider
{

    /**
     * Vychozi
     * @var array
     */
    private $_appModule = [
        "template" => "template",
        "thumbnail" => 1,
        "gallery" => 2,
        "editor" => 5
    ];

    /**
     * Formatovani uloziste
     * @var string
     */
    private $_appFormat = "./%s/%s/%s/%s";

    /**
     * Level zanoreni adresaru
     * @var integer
     */
    private $_appLevel = 0;

    /**
     * Storage adresar
     * @var string
     */
    private $_appStorageDirectory = null;

    /**
     * Cacche adresar
     * @var string
     */
    private $_appCacheDirectory = null;

    /**
     * @param int $level
     * @param string|null $storageTypId
     * @param string|null $moduleId
     * @param string|null $artId
     */
    public function __construct(int $level, string $storageTypId = null, string $moduleId = null, string $artId = null)
    {

        $this->_setLevel($level);
        $this->_setDirectory($storageTypId, $moduleId, $artId);
    }

    /**
     * @return string|null
     */
    public function getStorage(): ?string
    {

        return $this->_appStorageDirectory;
    }

    /**
     * @return string|null
     */
    public function getCache(): ?string
    {

        return $this->_appCacheDirectory;
    }


    /**
     * @param int $level
     * @return void
     */
    protected function _setLevel(int $level): void
    {

        $this->_appLevel = $level;
    }

    /**
     * Adresare
     *
     * @param string|null $storageTypId
     * @param string|null $moduleId
     * @param string|null $artId
     * @return void
     */
    protected function _setDirectory(string $storageTypId = null, string $moduleId = null, string $artId = null)
    {

        $this->_appStorageDirectory = sprintf($this->_appFormat, "storage", $moduleId, $this->_getDirectoryId($storageTypId), $this->_getFileDirectoryStructure($artId));
        $this->_appCacheDirectory = sprintf($this->_appFormat, "storage/0/cache", $moduleId, $this->_getDirectoryId($storageTypId), $this->_getFileDirectoryStructure($artId));

    }

    /**
     * @return int
     */
    protected function _getLevel(): int
    {

        return $this->_appLevel;
    }

    /**
     * Generovani adresarove struktury pro konkretni id
     *
     * @param string|null $artId
     * @return string
     */
    private function _getFileDirectoryStructure(string $artId = null): string
    {

        return rtrim(chunk_split(str_pad(dechex((int)$artId), $this->_getLevel(), "0", STR_PAD_LEFT), 2, "/"), "/");
    }

    /**
     * Id uloziste
     *
     * @param string|null $typId
     * @return string
     */
    private function _getDirectoryId(string $typId = null): string
    {

        return (string)($this->_appModule[$typId] ?? $typId ?? "0");
    }


}