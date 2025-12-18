<?php declare(strict_types=1);

namespace Lemonade\Image\Providers;

use function rtrim;
use function sprintf;
use function chunk_split;
use function str_pad;
use function dechex;
use const DIRECTORY_SEPARATOR;
use const PHP_EOL;
use const STR_PAD_LEFT;

/**
 * DirectoryProvider
 *
 * Provider zodpovědný za sestavení adresářové struktury
 * pro originální soubory a cache varianty obrázků.
 *
 * Řeší:
 * - mapování storage typu na adresář
 * - generování hierarchické struktury podle ID
 * - oddělení storage a cache stromu
 *
 * @package     Lemonade Framework
 * @subpackage  Image\Providers
 * @category    Providers
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
 */
final class DirectoryProvider
{
    /**
     * Mapování typů uložišť na interní ID.
     */
    private const MODULE_MAP = [
        'template'  => 'template',
        'thumbnail' => 1,
        'gallery'   => 2,
        'editor'    => 5,
    ];

    /**
     * Formát adresářové struktury (OS-safe).
     */
    private string $pathFormat;

    /**
     * Úroveň zanoření adresářů.
     */
    private int $level = 0;

    /**
     * Cesta k originálním souborům.
     */
    private ?string $storageDirectory = null;

    /**
     * Cesta ke cache souborům.
     */
    private ?string $cacheDirectory = null;

    public function __construct(
        int $level,
        string|int|null $storageTypeId = null,
        string|int|null $moduleId = null,
        string|int|null $artId = null
    ) {

        $this->pathFormat = self::pathFormat();

        $this->setLevel($level);
        $this->setDirectories($storageTypeId, $moduleId, $artId);
    }

    /**
     * Vrátí cestu ke storage adresáři.
     */
    public function getStorage(): ?string
    {
        return $this->storageDirectory;
    }

    /**
     * Vrátí cestu ke cache adresáři.
     */
    public function getCache(): ?string
    {
        return $this->cacheDirectory;
    }

    /**
     * Nastaví úroveň zanoření adresářů.
     */
    protected function setLevel(int $level): void
    {
        $this->level = $level;
    }

    /**
     * Sestaví storage a cache adresáře.
     */
    protected function setDirectories(
        string|int|null $storageTypeId = null,
        string|int|null $moduleId = null,
        string|int|null $artId = null
    ): void {
        $directoryId = $this->resolveDirectoryId($storageTypeId);
        $structure   = $this->buildDirectoryStructure($artId);

        $this->storageDirectory = sprintf(
            $this->pathFormat,
            'storage',
            $moduleId,
            $directoryId,
            $structure
        );

        $this->cacheDirectory = sprintf(
            $this->pathFormat,
            'storage' . DIRECTORY_SEPARATOR . '0' . DIRECTORY_SEPARATOR . 'cache',
            $moduleId,
            $directoryId,
            $structure
        );
    }

    /**
     * Vrátí aktuální úroveň zanoření.
     */
    protected function getLevel(): int
    {
        return $this->level;
    }

    /**
     * Vygeneruje hierarchickou strukturu adresářů z ID.
     */
    private function buildDirectoryStructure(string|int|null $artId): string
    {
        return rtrim(
            chunk_split(
                str_pad(
                    dechex((int) $artId),
                    $this->getLevel(),
                    '0',
                    STR_PAD_LEFT
                ),
                2,
                DIRECTORY_SEPARATOR
            ),
            DIRECTORY_SEPARATOR
        );
    }

    /**
     * Přeloží typ uložiště na adresářový identifikátor.
     */
    private function resolveDirectoryId(?string $typeId): string
    {
        return (string) (self::MODULE_MAP[$typeId] ?? $typeId ?? '0');
    }

    private static function pathFormat(): string
    {
        $ds = DIRECTORY_SEPARATOR;

        return
            '.' . $ds .
            '%s' . $ds .
            '%s' . $ds .
            '%s' . $ds .
            '%s';
    }
}
