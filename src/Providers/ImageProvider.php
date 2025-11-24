<?php declare(strict_types=1);

namespace Lemonade\Image\Providers;

use Lemonade\Image\Traits\StaticTrait;
use Lemonade\Image\AppGenerator;
use Datetime;

final class ImageProvider {

    use StaticTrait;

    /**
     * Cas mezi pregenerovanim (30 dnu)
     * @var int
     */
    const APP_TIME = 31536000;

    /**
     * Povolene velikost
     * @var array
     */
    private static $_allowedSize = [
        "minw" => 50,
        "minh" => 50,
        "maxw" => 2560,
        "maxh" => 2560
    ];

    /**
     * Mimes
     * @var array
     */
    private static $_appMimes = [
        AppGenerator::JPEG => "image/jpg",
        AppGenerator::PNG => "image/png",
        AppGenerator::GIF => "image/gif",
        AppGenerator::WEBP => "image/webp"
    ];


    /**
     * @param FileProvider $app
     * @return void
     * @throws \Exception
     */
    public static function imageCreate(FileProvider $app): void
    {

        $imgExt = self::_getType($app);
        $width  = self::_getWidth($app->getData());
        $height = self::_getHeight($app->getData());

        // 0 h, 0 w 
        if($width === 0 && $height === 0) {
            $width = self::$_allowedSize["minw"];
            $height = self::$_allowedSize["minh"];
        }

        switch ($app->getData()->getCrop()) {

            case 1: // dopocitat a dokreslit platno (vcetne barevneho podkladu)

                // podklad
                $thumb = AppGenerator::fromFile($app->getFileFs());
                $thumb->resize(($width ? ((int) round($width * 0.75)) : null), ($height ? ((int) round($height * 0.75)) : null), AppGenerator::FIT, true);

                // obrazek
                $image = AppGenerator::fromBlank(($width ?? $height ?? $thumb->getWidth()), ($height ?? $width ?? $thumb->getHeight()), ColorProvider::hexRgb($app->getData()->getCanvasColor())->toArray());
                $image->saveAlpha(true);

                $image->place($thumb, "50%", "50%");
                // $image->sharpen();

            break;
            case 2: // pevne oriznuti (vyplni cilove platno a vsechno ostatni orizene)

                $image = AppGenerator::fromFile($app->getFileFs());
                $image->resize(($width ?? $height ?? $image->getWidth()), ($height ?? $width ?? $image->getHeight()), AppGenerator::EXACT, true);
                // $image->sharpen();

            break;
            case 3: // fit (muze mit jine nez pozadovane rozmery, aspect ratio)

                $image = AppGenerator::fromFile($app->getFileFs());
                $image->resize($width, $height, AppGenerator::FIT|AppGenerator::SHRINK_ONLY);
                // $image->sharpen();

            break;
            case 0: // nepouziva orez
            default:

                $image = AppGenerator::fromFile($app->getFileFs());
                $image->resize(($width ?? $height ?? $image->getWidth()), ($height ?? $width ?? $image->getHeight()), AppGenerator::SHRINK_ONLY, true);
                // $image->sharpen();

            break;
        }

        // adresar
        $app->createDirectory($app->getCacheFile());

        // vystup
        if(!WebpProvider::hasSupport()) {

            $image->save($app->getCacheFile(), $app->getData()->getQuality(), $imgExt);

        } else {

            if($imgExt === AppGenerator::PNG) {

                $image->paletteToTrueColor(); // webp musime prevest do true color
            }

            $imgExt = AppGenerator::WEBP;

            $image->save($app->getCacheWebp(), $app->getData()->getQuality(), $imgExt);
        }

        // pro vystup
        $data = $image->toString($imgExt, $app->getData()->getQuality());
        $iext = $imgExt;

        // hlavicky
        ImageProvider::sendHeader($iext);
        ImageProvider::sendContent($data);
    }


    /**
     * @param FileProvider $app
     * @return void
     * @throws \Exception
     */
    public static function imageError(FileProvider $app) {

        $imgExt = AppGenerator::PNG;
        $width  = (self::_getWidth($app->getData()) ?? self::_getHeight($app->getData()));
        $height = (self::_getHeight($app->getData()) ?? self::_getWidth($app->getData()));

        if(!$app->isFileExists($app->getMissingPng())) {

            // nacteni error obrazku
            $thumb = AppGenerator::fromString(base64_decode(static::imageErrorString()));
            $thumb->resize(($width ? ((int) round($width * 0.75)) : null), ($height ? ((int) round($height * 0.75)) : null), AppGenerator::FIT|AppGenerator::SHRINK_ONLY, true);

            // ulozeni + vystup
            $image = AppGenerator::fromBlank($width, $height, ColorProvider::hexRgb($app->getData()->getCanvasColor())->toArray());
            //$image->sharpen();

            // Přepnutí na true color (musí být před alokací průhledné barvy)
            $image->paletteToTrueColor();

            // Nastavení průhlednosti
            $trans = $image->colorAllocateAlpha(0, 0, 0, 127); // Plně průhledná barva
            $image->fill(0, 0, $trans);

            // Aktivace průhlednosti pro PNG
            $image->saveAlpha(true);

            // Umístění miniatury doprostřed
            $image->place($thumb, "50%", "50%", 70);

            // adresar
            $app->createDirectory($app->getMissingPng());

            // ulozit
            $image->save($app->getMissingPng(), $app->getData()->getQuality(), $imgExt);

        } else {

            $image = AppGenerator::fromFile($app->getMissingPng());
        }

        if(WebpProvider::hasSupport()) {

            $imgExt = AppGenerator::WEBP;
        }

        // pro vystup
        $data = $image->toString($imgExt, $app->getData()->getQuality());
        $iext = $imgExt;

        // hlavicky
        ImageProvider::sendHeader($iext);
        ImageProvider::sendContent($data);

    }


    /**
     * Output - hlavicky
     * @param int|null $mime
     * @param int $size
     * @return void
     */
    public static function sendHeader(int $mime = null, int $size = 0): void
    {

        // lifetime
        $lifetime = self::APP_TIME;

        // datumy
        $current = new Datetime();
        $expires = clone $current;

        $expires  = $expires->modify("+ $lifetime seconds")->format("D, d M Y H:i:s") . " GMT";
        $modified = $current->format("D, d M Y H:i:s"). " GMT";

        // hlavicky
        if(!empty($mime)) {

            header("Content-Type: ". ImageProvider::$_appMimes[$mime]);
        }

        // hlavicka - dalsi
        header("Accept-Ranges: none");
        header("Last-Modified: " . $modified);
        header('Cache-Control: max-age=' .  $lifetime . ', no-transform');
        header("Expires: " . $expires);
        header("Connection: close");


        if(!empty($size)) {

            header("Content-Length: " . $size);
        }

        if (isset($_SERVER["HTTP_IF_MODIFIED_SINCE"])) {

            $lastMod = strtotime($_SERVER["HTTP_IF_MODIFIED_SINCE"]);

        } else {

            $lastMod = 0;
        }

        if ($lastMod <= $_SERVER["REQUEST_TIME"] - $lifetime) {

            $lastMod = $_SERVER["REQUEST_TIME"];

            header("Last-Modified: " . gmdate('D, d M Y H:i:s \G\M\T', $lastMod), true, 200);

        } else {

            header("Last-Modified: " . gmdate('D, d M Y H:i:s \G\M\T', $lastMod), true, 304);
        }

    }

    /**
     * Not modified
     * @return void
     */
    public static function setNoModified(): void
    {

        header("Connection: close");
        header($_SERVER["SERVER_PROTOCOL"] . " 304 Not Modified");
    }

    /**
     * Output - data
     * @param string|null $content
     * @return void
     */
    public static function sendContent(string $content = null): void
    {

        print ($content ?? "");
        exit();
    }


    /**
     * @param FileProvider $app
     * @return int|null
     */
    protected static function _getType(FileProvider $app): ?int
    {

        return AppGenerator::detectTypeFromFile($app->getFileFs());
    }

    /**
     * @param DataProvider $data
     * @return int|mixed|null
     */
    protected static function _getWidth(DataProvider $data) {

        if($data->isMissingAllSize()) {

            return null; //self::$_allowedSize["minw"];
        }

        return $data->getWidth();
    }

    /**
     * @param DataProvider $data
     * @return int|mixed|null
     */
    protected static function _getHeight(DataProvider $data) {

        if($data->isMissingAllSize()) {

            return null;// self::$_allowedSize["minh"];
        }

        return $data->getHeight();
    }


    /**
     * Base64 encoded thumb
     * @return string
     */
    protected static function imageErrorString(): string
    {

        if(file_exists("./themes/frontend/error.png")) {

            return base64_encode(file_get_contents("./themes/frontend/error.png"));
        }

        return "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";
    }

}
