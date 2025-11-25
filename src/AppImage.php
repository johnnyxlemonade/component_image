<?php declare(strict_types=1);

namespace Lemonade\Image;

use Lemonade\Image\Providers\DirectoryProvider;
use Lemonade\Image\Providers\FileProvider;
use Lemonade\Image\Providers\DataProvider;
use Lemonade\Image\Providers\ImageProvider;
use Throwable;

/**
 * AppImage
 *
 * Hlavní vstupní třída pro generování obrázků v Lemonade Frameworku.
 * Řeší kompletní pipeline zpracování:
 *
 * - načtení požadavku (rozměry, crop, kvalita, canvas) přes `DataProvider`
 * - sestavení kontextu (adresář, cesta k souboru, argumenty, cache)
 * - řízení toku: 304 Not Modified → cache → originál → fallback error image
 * - bezpečné generování výstupu přes `ImageProvider`
 *
 * Funkce třídy:
 * - jednotný entry-point pro všechny obrázkové endpointy (factoryApp)
 * - konzistentní práce s provider vrstvou (DirectoryProvider, FileProvider, DataProvider)
 * - fallback logika pro chybějící soubory a definované rozměry
 * - automatická invalidace cache pokud originál neexistuje
 *
 * Třída sama negeneruje obrázky – pouze řídí tok, správně vybere, kdy:
 * - vrátit `304 Not Modified`
 * - obsloužit již existující cache
 * - vytvořit nový render
 * - vygenerovat error placeholder
 *
 * @package     Lemonade Framework
 * @subpackage  Image
 * @category    Core
 * @author      Honza Mudrák
 * @license     MIT
 * @since       1.0.0
 * @see         ImageProvider
 * @see         FileProvider
 * @see         DataProvider
 * @see         DirectoryProvider
 */
final class AppImage
{
    /**
     * File + directory + data context pro aktuální požadavek
     */
    private FileProvider $provider;

    /**
     * Entry-point volaný z frameworku
     */
    public static function factoryApp(
        int     $level,
        ?string $storageTypId,
        ?string $moduleId,
        ?string $artId,
        ?string $baseName,
        ?string $args
    ): void
    {
        $app = new self(
            level: $level,
            storageTypId: $storageTypId,
            moduleId: $moduleId,
            artId: $artId,
            baseName: $baseName,
            args: $args
        );

        $app->run();
    }

    /**
     * Vytvoří kontext providerů (adresář, data, soubor)
     */
    protected function __construct(
        int     $level,
        ?string $storageTypId,
        ?string $moduleId,
        ?string $artId,
        ?string $baseName,
        ?string $args
    )
    {
        $this->provider = new FileProvider(
            dir: new DirectoryProvider(
                level: $level,
                storageTypId: $storageTypId,
                moduleId: $moduleId,
                artId: $artId
            ),
            data: new DataProvider($args),
            file: $baseName
        );

    }

    /**
     * Hlavní workflow:
     * - hotový obrázek z browser cíle (If-Modified)
     * - hotový obrázek z cache
     * - generování z originálu
     * - fallback error image
     */
    public function run(): void
    {
        try {
            // 1) klient již má obrazek (304)
            if ($this->provider->sendBrowserImage()) {
                return;
            }

            // 2) existuje cache verze
            if ($this->provider->sendCacheImage()) {
                return;
            }

            // 3) existuje originál → vytvořit variantu
            if ($this->provider->isFileExists($this->provider->getFileFs())) {
                ImageProvider::imageCreate($this->provider);
                return;
            }

            // 4) neexistuje → error image
            $this->provider->deleteCache();
            ImageProvider::imageError($this->provider);

        } catch (Throwable $e) {

            $data = $this->provider->getData();

            // fallback minimálních rozměrů
            if ($data->isMissingAllSize()) {
                $data->setWidth(600);
                $data->setHeight(600);
            }

            ImageProvider::imageError($this->provider);
        }
    }
}
