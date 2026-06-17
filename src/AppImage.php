<?php

declare(strict_types=1);

namespace Lemonade\Image;

use Lemonade\Image\Providers\DataProvider;
use Lemonade\Image\Providers\DirectoryProvider;
use Lemonade\Image\Providers\FileProvider;
use Lemonade\Image\Providers\ImageProvider;
use Lemonade\Image\Utils\FileSystem;
use Throwable;

/**
 * Main facade for handling a single image request.
 *
 * Coordinates option parsing, directory resolution, file path resolution
 * and final image generation through the provider layer.
 *
 * @package     Lemonade
 * @subpackage  Image
 * @category    Facade
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
 *
 * @see ImageProvider
 * @see FileProvider
 * @see DirectoryProvider
 * @see DataProvider
 */
final class AppImage
{
    private ?DirectoryProvider $directory = null;
    private ?FileProvider $provider = null;
    private ?DataProvider $data = null;
    private ?FileSystem $filesystem = null;

    /**
     * Entry-point volaný z frameworku
     */
    public static function factoryApp(
        int                $level,
        string|int|null    $storageTypId,
        string|int|null    $moduleId,
        string|int|null    $artId,
        ?string            $baseName,
        ?string            $args,
    ): void {
        $app = new self(
            level: $level,
            storageTypId: $storageTypId,
            moduleId: $moduleId,
            artId: $artId,
            baseName: $baseName,
            args: $args,
        );

        $app->run();
    }

    /**
     * Vytvoří kontext providerů (adresář, data, soubor)
     */
    protected function __construct(
        private readonly int $level,
        private readonly string|int|null $storageTypId,
        private readonly string|int|null $moduleId,
        private readonly string|int|null $artId,
        private readonly ?string $baseName,
        private readonly ?string $args,
    ) {}

    /**
     * Hlavní workflow:
     * - hotový obrázek z browser cíle (If-Modified)
     * - hotový obrázek z cache
     * - generování z originálu
     * - fallback error image
     */
    public function run(): void
    {
        $provider = $this->getProvider();

        try {

            // 1) klient již má obrazek (304)
            if ($provider->sendBrowserImage()) {
                return;
            }

            // 2) existuje cache verze
            if ($provider->sendCacheImage()) {
                return;
            }

            // 3) existuje originál → vytvořit variantu
            if ($provider->isFileExists($provider->getFileFs())) {
                ImageProvider::imageCreate($provider);
                return;
            }

            // 4) neexistuje → error image
            $provider->deleteCache();
            ImageProvider::imageError($provider);

        } catch (Throwable $e) {

            $data = $provider->getData();

            // fallback minimálních rozměrů
            if ($data->isMissingAllSize()) {
                $data->setWidth(600);
                $data->setHeight(600);
            }

            ImageProvider::imageError($provider);
        }
    }

    private function getDirectory(): DirectoryProvider
    {
        return $this->directory ??= new DirectoryProvider(
            level: $this->level,
            storageTypeId: $this->storageTypId,
            moduleId: $this->moduleId,
            artId: $this->artId,
        );
    }

    private function getData(): DataProvider
    {
        return $this->data ??= new DataProvider($this->args);
    }

    private function getFilesystem(): FileSystem
    {
        return $this->filesystem ??= new FileSystem();
    }

    private function getProvider(): FileProvider
    {
        return $this->provider ??= new FileProvider(
            directory: $this->getDirectory(),
            data: $this->getData(),
            filesystem: $this->getFilesystem(),
            file: $this->baseName,
        );
    }

}
