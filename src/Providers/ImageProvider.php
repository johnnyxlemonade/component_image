<?php declare(strict_types=1);

namespace Lemonade\Image\Providers;

use Lemonade\Image\AppGenerator;
use Lemonade\Image\ImageOptionsDTO;
use DateTimeImmutable;
use RuntimeException;

/**
 * ImageProvider
 *
 * Centrální služba pro generování, zpracování a doručování obrázků
 * v rámci Lemonade Image Component. Zajišťuje kompletní životní cyklus:
 * - načtení zdrojového souboru
 * - aplikaci transformací (resize, crop, canvas)
 * - volbu správného výstupního formátu (PNG/WEBP/JPEG)
 * - zápis do cache
 * - odeslání do prohlížeče včetně všech HTTP hlaviček
 *
 * Součástí je také fallback režim pro chybové obrázky, který generuje
 * placeholder na základě zadaných parametrů (rozměry, barva pozadí).
 *
 * Klíčové vlastnosti:
 * - Plná kompatibilita s původní implementací Lemonade Image
 * - Automatická detekce podpory WebP (WebpProvider::hasSupport)
 * - Transparentní HTTP cache (Expires, Last-Modified, 304 Not Modified)
 * - Jednotná práce s ImageOptionsDTO (šířka, výška, crop, canvas, kvalita)
 * - Oddělené ukládání PNG/WEBP verzí do cache
 *
 * Třída funguje jako hlavní rozhraní pro FileProvider
 * a je volána skrze AppImage::run().
 *
 * @package     Lemonade Framework
 * @subpackage  Image\Providers
 * @category    Image
 * @author      Honza Mudrak
 * @license     MIT
 * @since       1.0.0
 * @see         AppImage, FileProvider, WebpProvider, ImageOptionsDTO
 */
final class ImageProvider
{
    /**
     * Cas mezi pregenerovanim (30 dnu)
     */
    const APP_TIME = 31536000;

    /**
     * MIME typy
     *
     * @var array<int, string>
     */
    public const MIME_TYPES = [
        AppGenerator::JPEG => 'image/jpg',
        AppGenerator::PNG  => 'image/png',
        AppGenerator::GIF  => 'image/gif',
        AppGenerator::WEBP => 'image/webp',
    ];

    /**
     * Main image processing entrypoint
     */
    public static function imageCreate(FileProvider $app): void
    {
        $opt = $app->getData()->getDTO();

        $src = self::loadSource($app);
        $img = self::processResize($src, $opt);

        $imgExt  = self::getType($app);
        $quality = $opt->getQuality();

        self::saveCache($app, $img, $quality, $imgExt);
        self::outputImage($img, $imgExt, $quality);
    }

    /**
     * Generates or loads fallback error image
     */
    public static function imageError(FileProvider $app): void
    {
        $opt = $app->getData()->getDTO();

        $image = self::buildErrorImage($opt, $app);
        $imgExt = WebpProvider::hasSupport()
            ? AppGenerator::WEBP
            : AppGenerator::PNG;

        self::saveErrorCache($app, $image, $opt->getQuality(), $imgExt);
        self::outputImage($image, $imgExt, $opt->getQuality());
    }

    /**
     * Sends HTTP headers for image response
     */
    public static function sendHeader(int $mime = null, int $size = 0): void
    {
        $lifetime = self::APP_TIME;

        $now       = new DateTimeImmutable();
        $expiresAt = $now
                ->modify("+{$lifetime} seconds")
                ->format("D, d M Y H:i:s") . " GMT";

        $mimeStr = $mime !== null && isset(self::MIME_TYPES[$mime])
            ? self::MIME_TYPES[$mime]
            : null;

        ServerHeaderProvider::setContentType($mimeStr);
        ServerHeaderProvider::setCacheHeaders($lifetime, $expiresAt);

        if ($size > 0) {
            ServerHeaderProvider::setContentLength($size);
        }

        // Conditional GET
        $ifMod      = ServerProvider::get("HTTP_IF_MODIFIED_SINCE");
        $clientTime = $ifMod !== '' ? strtotime($ifMod) : 0;
        $serverTime = (int) ServerProvider::get('REQUEST_TIME', (string) time());

        if ($clientTime > ($serverTime - $lifetime)) {
            ServerHeaderProvider::setLastModified($clientTime, 304);
            return;
        }

        ServerHeaderProvider::setLastModified($serverTime, 200);
    }

    /**
     * 304 No Modified shortcut
     */
    public static function setNoModified(): void
    {
        ServerHeaderProvider::setNotModified();
    }

    /**
     * Outputs image binary and terminates
     */
    public static function sendContent(string $content = null): never
    {
        echo $content ?? '';
        exit;
    }

    /**
     * Loads custom error.png or generates 1×1 PNG
     */
    private static function loadErrorThumb(): AppGenerator
    {
        $custom = './themes/frontend/error.png';

        if (file_exists($custom)) {
            return AppGenerator::fromFile($custom);
        }

        // 1×1 bílý PNG generovaný v paměti
        $img = imagecreatetruecolor(1, 1);

        // Povolit alfa kanál
        imagesavealpha($img, true);

        // Bílá barva bez průhlednosti
        $white = imagecolorallocatealpha($img, 255, 255, 255, 0);
        imagefill($img, 0, 0, $white);

        ob_start();
        imagepng($img);
        $png = ob_get_clean();
        imagedestroy($img);

        return AppGenerator::fromString($png);
    }

    /**
     * Builds placeholder image with canvas color + centered icon
     */
    private static function buildErrorImage(ImageOptionsDTO $opt, FileProvider $app): AppGenerator
    {
        $width  = $opt->getWidth();
        $height = $opt->getHeight();
        $canvas = $opt->getCanvasColor();

        // fallback dimensions
        if ($width === null && $height === null) {
            $width = $height = 600;
        } elseif ($width === null) {
            $width = $height;
        } elseif ($height === null) {
            $height = $width;
        }

        // reuse cached version
        if ($app->isFileExists($app->getMissingPng())) {
            return AppGenerator::fromFile($app->getMissingPng());
        }

        // thumbImage
        $thumb = self::loadErrorThumb();
        $thumb->resize(
            (int) round($width * 0.75),
            (int) round($height * 0.75),
            AppGenerator::FIT | AppGenerator::SHRINK_ONLY,
            true
        );

        // mainImage
        $rgb = ColorProvider::hexRgb($canvas)->toArray();

        $image = AppGenerator::fromBlank(
            $width,
            $height,
            $rgb
        );

        // Transparentní vrstva + alfa kanál
        $image->paletteToTrueColor();
        $alpha = $image->colorAllocateAlpha(
            $rgb['red'],
            $rgb['green'],
            $rgb['blue'],
            0
        );
        $image->fill(0, 0, $alpha);
        $image->saveAlpha(true);
        $image->place($thumb, "50%", "50%", 70);

        return $image;
    }

    /**
     * Saves PNG/WebP versions of fallback image
     */
    private static function saveErrorCache(
        FileProvider $app,
        AppGenerator $image,
        int $quality,
        int $ext
    ): void {

        $png = $app->getMissingPng();
        $webp = $app->getMissingWebp();

        $app->createDirectory($png);

        $image->save($png, $quality, AppGenerator::PNG);

        if ($ext === AppGenerator::WEBP) {
            $image->paletteToTrueColor();
            $image->save($webp, $quality, AppGenerator::WEBP);
        }
    }

    /**
     * Loads source file
     */
    private static function loadSource(FileProvider $app): AppGenerator
    {
        return AppGenerator::fromFile($app->getFileFs());
    }

    /**
     * Selects resize mode
     */
    private static function processResize(AppGenerator $src, ImageOptionsDTO $opt): AppGenerator
    {
        return match ($opt->getCrop()) {
            -1 => clone $src, // ORIGINAL
            1 => self::resizeCropWithCanvas($src, $opt),
            2 => self::resizeExact($src, $opt),
            3 => self::resizeFit($src, $opt),
            default => self::resizeShrink($src, $opt),
        };
    }

    /**
     * Resize mode: crop + canvas fill
     */
    private static function resizeCropWithCanvas(AppGenerator $src, ImageOptionsDTO $opt): AppGenerator
    {
        $w = $opt->getWidth();
        $h = $opt->getHeight();

        $thumb = clone $src;
        $thumb->resize(
            ($w ? (int) round($w * 0.75) : null),
            ($h ? (int) round($h * 0.75) : null),
            AppGenerator::FIT,
            true
        );

        $image = AppGenerator::fromBlank(
            ($w ?? $h ?? $thumb->getWidth()),
            ($h ?? $w ?? $thumb->getHeight()),
            ColorProvider::hexRgb($opt->getCanvasColor())->toArray()
        );

        $image->saveAlpha(true);
        $image->place($thumb, "50%", "50%");

        return $image;
    }

    /**
     * Resize mode: EXACT fill
     */
    private static function resizeExact(AppGenerator $src, ImageOptionsDTO $opt): AppGenerator
    {
        $w = $opt->getWidth();
        $h = $opt->getHeight();

        $img = clone $src;
        $img->resize(
            ($w ?? $h ?? $img->getWidth()),
            ($h ?? $w ?? $img->getHeight()),
            AppGenerator::EXACT,
            true
        );

        return $img;
    }

    /**
     * Resize mode: FIT
     */
    private static function resizeFit(AppGenerator $src, ImageOptionsDTO $opt): AppGenerator
    {
        $img = clone $src;
        $img->resize(
            $opt->getWidth(),
            $opt->getHeight(),
            AppGenerator::FIT | AppGenerator::SHRINK_ONLY
        );
        return $img;
    }

    /**
     * Resize mode: SHRINK_ONLY (default)
     */
    private static function resizeShrink(AppGenerator $src, ImageOptionsDTO $opt): AppGenerator
    {
        $w = $opt->getWidth();
        $h = $opt->getHeight();

        $img = clone $src;
        $img->resize(
            ($w ?? $h ?? $img->getWidth()),
            ($h ?? $w ?? $img->getHeight()),
            AppGenerator::SHRINK_ONLY,
            true
        );

        return $img;
    }

    /**
     * Saves processed image into cache directory
     */
    private static function saveCache(
        FileProvider $app,
        AppGenerator $image,
        int $quality,
        int $imgExt
    ): void
    {
        $app->createDirectory($app->getCacheFile());

        if (!WebpProvider::hasSupport()) {
            $image->save($app->getCacheFile(), $quality, $imgExt);
            return;
        }

        // podpora WEBP
        if ($imgExt === AppGenerator::PNG) {
            $image->paletteToTrueColor();
        }

        $image->save($app->getCacheWebp(), $quality, AppGenerator::WEBP);
    }

    /**
     * Outputs final image to browser
     */
    private static function outputImage(
        AppGenerator $image,
        int $imgExt,
        int $quality
    ): never
    {
        $data = $image->toString($imgExt, $quality);

        if ($data === '' || $data === null) {
            throw new RuntimeException('Image rendering failed');
        }

        self::sendHeader($imgExt, strlen($data));
        self::sendContent($data);
    }

    /**
     * Detects image type based on source file
     */
    private static function getType(FileProvider $app): ?int
    {
        return AppGenerator::detectTypeFromFile($app->getFileFs());
    }

}
