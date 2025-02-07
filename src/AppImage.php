<?php declare(strict_types=1);


namespace Lemonade\Image;

use Lemonade\Image\Traits\ObjectTrait;
use Lemonade\Image\Providers\DirectoryProvider;
use Lemonade\Image\Providers\FileProvider;
use Lemonade\Image\Providers\DataProvider;
use Lemonade\Image\Providers\ImageProvider;
use Exception;

final class AppImage
{

    use ObjectTrait;

    /**
     * @var FileProvider|null
     */
    private ?FileProvider $app = null;

    /**
     * factoryApp
     * @param int $level
     * @param string|null $storageTypId
     * @param string|null $moduleId
     * @param string|null $artId
     * @param string|null $baseName
     * @param string|null $args
     * @return void
     */
    public static function factoryApp(int $level, string $storageTypId = null, string $moduleId = null, string $artId = null, string $baseName = null, string $args = null): void
    {


        try {

            $img = new AppImage($level, $storageTypId, $moduleId, $artId, $baseName, $args);

            if($img->tryBrowserCache()) {

                exit(0);
            }

            if($img->tryServerCache()) {

                exit(0);
            }

            $img->tryAppRun();

        } catch (Exception $e) {

            _vd($e->getMessage());
        }


        exit(0);
    }

    /**
     * @param int $level
     * @param string|null $storageTypId
     * @param string|null $moduleId
     * @param string|null $artId
     * @param string|null $baseName
     * @param string|null $args
     */
    protected function __construct(int $level, string $storageTypId = null, string $moduleId = null, string $artId = null, string $baseName = null, string $args = null)
    {

        $this->app = new FileProvider(dir: new DirectoryProvider(level: $level, storageTypId: $storageTypId, moduleId: $moduleId, artId: $artId), data: new DataProvider(args: $args), file: $baseName);
    }

    /**
     * cacheBrowser
     * @return bool
     */
    protected function tryBrowserCache(): bool
    {
        
        return $this->app->sendBrowserImage();
    }

    /**
     * cacheServer
     * @return bool
     */
    protected function tryServerCache(): bool
    {
        
        return $this->app->sendCacheImage();
    }

    /**
     * outputStandard
     * @return void
     */
    protected function tryAppRun(): void
    {

        try {

            if($this->app->isFileExists(file: $this->app->getFileFs())) {

                // generator
                ImageProvider::imageCreate(app: $this->app);

            } else {

                // smazat soubory
                $this->app->deleteCache();

                // generator
                ImageProvider::imageError(app: $this->app);
            }

        } catch (Exception $e) {

            // chceme original?
            if($this->app->getData()->isMissingAllSize()) {

                $this->app->getData()->setWidth(width: 600);
                $this->app->getData()->setHeight(height: 600);
            }

            // spatna extenze souboru
            ImageProvider::imageError(app: $this->app);
        }

    }
     

    
}
    