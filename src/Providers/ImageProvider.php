<?php

declare(strict_types=1);

namespace Lemonade\Image\Providers;

use DateTimeImmutable;
use Lemonade\Image\AppGenerator;
use Lemonade\Image\Exceptions\Image\ImageCacheException;
use Lemonade\Image\Exceptions\Image\ImagePlaceholderException;
use Lemonade\Image\Exceptions\Image\ImageRenderException;
use Lemonade\Image\Exceptions\Image\ImageSourceException;
use Lemonade\Image\Exceptions\Image\ImageTypeException;
use Lemonade\Image\ImageOptionsDTO;

use function file_exists;
use function imagecolorallocatealpha;
use function imagecreatetruecolor;
use function imagedestroy;
use function imagefill;
use function imagepng;
use function imagesavealpha;
use function ob_get_clean;
use function ob_start;
use function round;
use function strlen;
use function strtotime;
use function time;

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
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
 * @see         AppImage
 * @see         FileProvider
 * @see         WebpProvider
 * @see         ImageOptionsDTO
 */
final class ImageProvider
{
    private const APP_TIME = 31536000;
    private const CANVAS_SCALE_NORMAL = 0.75;
    private const CANVAS_SCALE_BIGGER = 0.82;
    private const CANVAS_SCALE_MAX = 0.90;

    /**
     * MIME typy.
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
     * Main image processing entrypoint.
     */
    public static function imageCreate(FileProvider $app): void
    {
        $opt = $app->getData()->getDTO();

        $src = self::loadSource($app);
        $img = self::processResize($src, $opt);

        $imgExt = self::getType($app);
        $quality = $opt->getQuality();

        self::saveCache($app, $img, $quality, $imgExt);
        self::outputImage($img, $imgExt, $quality);
    }

    /**
     * Generates or loads fallback error image.
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
     * Sends HTTP headers for image response.
     */
    public static function sendHeader(?int $mime = null, int $size = 0): void
    {
        $lifetime = self::APP_TIME;

        $now = new DateTimeImmutable();
        $expiresAt = $now
                ->modify("+{$lifetime} seconds")
                ->format('D, d M Y H:i:s') . ' GMT';

        $mimeStr = $mime !== null && isset(self::MIME_TYPES[$mime])
            ? self::MIME_TYPES[$mime]
            : null;

        ServerHeaderProvider::setContentType($mimeStr);
        ServerHeaderProvider::setCacheHeaders($lifetime, $expiresAt);

        if ($size > 0) {
            ServerHeaderProvider::setContentLength($size);
        }

        // Conditional GET
        $ifMod = ServerProvider::get('HTTP_IF_MODIFIED_SINCE');
        $parsedClientTime = $ifMod !== '' ? strtotime($ifMod) : false;
        $clientTime = $parsedClientTime === false ? 0 : $parsedClientTime;

        $serverTime = (int) ServerProvider::get('REQUEST_TIME', (string) time());

        if ($clientTime > ($serverTime - $lifetime)) {
            ServerHeaderProvider::setLastModified($clientTime, 304);

            return;
        }

        ServerHeaderProvider::setLastModified($serverTime, 200);
    }

    /**
     * 304 Not Modified shortcut.
     */
    public static function setNoModified(): void
    {
        ServerHeaderProvider::setNotModified();
    }

    /**
     * Outputs image binary and terminates.
     */
    public static function sendContent(?string $content = null): never
    {
        echo $content ?? '';
        exit;
    }

    /**
     * Loads custom error.png or generates 1×1 PNG.
     */
    private static function loadErrorThumb(): AppGenerator
    {
        $custom = './themes/frontend/error.png';

        if (file_exists($custom)) {
            return AppGenerator::fromFile($custom);
        }

        // 1×1 bílý PNG generovaný v paměti
        $img = imagecreatetruecolor(1, 1);
        if ($img === false) {
            throw ImagePlaceholderException::createFailed();
        }

        // Povolit alfa kanál
        imagesavealpha($img, true);

        // Bílá barva bez průhlednosti
        $white = imagecolorallocatealpha($img, 255, 255, 255, 0);
        if ($white === false) {
            imagedestroy($img);

            throw ImagePlaceholderException::colorAllocationFailed();
        }

        imagefill($img, 0, 0, $white);

        ob_start();
        imagepng($img);
        $png = ob_get_clean();
        imagedestroy($img);

        if ($png === false) {
            throw ImagePlaceholderException::renderFailed();
        }

        return AppGenerator::fromString($png);
    }

    /**
     * Builds placeholder image with canvas color + centered icon.
     */
    private static function buildErrorImage(ImageOptionsDTO $opt, FileProvider $app): AppGenerator
    {
        $width = $opt->getWidth();
        $height = $opt->getHeight();
        $canvas = $opt->getCanvasColor();

        // fallback dimensions
        if ($width === null && $height === null) {
            $width = 600;
            $height = 600;
        } elseif ($width === null) {
            $width = $height;
        } elseif ($height === null) {
            $height = $width;
        }

        $missingPng = $app->getMissingPng();

        // reuse cached version
        if ($missingPng !== null && $app->isFileExists($missingPng)) {
            return AppGenerator::fromFile($missingPng);
        }

        // thumbImage
        $thumb = self::loadErrorThumb();
        $thumb->resize(
            (int) round($width * self::CANVAS_SCALE_NORMAL),
            (int) round($height * self::CANVAS_SCALE_NORMAL),
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
        $image->place($thumb, '50%', '50%', 70);

        return $image;
    }

    /**
     * Saves PNG/WebP versions of fallback image.
     */
    private static function saveErrorCache(
        FileProvider $app,
        AppGenerator $image,
        int $quality,
        int $ext
    ): void {
        $png = $app->getMissingPng();
        $webp = $app->getMissingWebp();

        if ($png === null || $webp === null) {
            throw ImageCacheException::missingErrorCachePath();
        }

        $app->createDirectory($png);

        $image->save($png, $quality, AppGenerator::PNG);

        if ($ext === AppGenerator::WEBP) {
            $image->paletteToTrueColor();
            $image->save($webp, $quality, AppGenerator::WEBP);
        }
    }

    /**
     * Loads source file.
     */
    private static function loadSource(FileProvider $app): AppGenerator
    {
        $file = $app->getFileFs();

        if ($file === null) {
            throw ImageSourceException::missingSourcePath();
        }

        return AppGenerator::fromFile($file);
    }

    /**
     * Selects resize mode.
     */
    private static function processResize(AppGenerator $src, ImageOptionsDTO $opt): AppGenerator
    {
        return match ($opt->getCrop()) {
            -1 => clone $src,
            1 => self::resizeFitWithCanvas($src, $opt, self::CANVAS_SCALE_NORMAL),
            2 => self::resizeExact($src, $opt),
            3 => self::resizeFit($src, $opt),
            4 => self::resizeFitWithCanvas($src, $opt, self::CANVAS_SCALE_BIGGER),
            5 => self::resizeFitWithCanvas($src, $opt, self::CANVAS_SCALE_MAX),
            default => self::resizeShrink($src, $opt),
        };
    }

    /**
     * Proportional fit into canvas.
     */
    private static function resizeFitWithCanvas(
        AppGenerator $src,
        ImageOptionsDTO $opt,
        float $scale
    ): AppGenerator {
        $w = $opt->getWidth();
        $h = $opt->getHeight();

        $canvasWidth = $w ?? $h ?? $src->getWidth();
        $canvasHeight = $h ?? $w ?? $src->getHeight();

        $thumb = clone $src;
        $thumb->resize(
            (int) round($canvasWidth * $scale),
            (int) round($canvasHeight * $scale),
            AppGenerator::FIT,
            true
        );

        $image = AppGenerator::fromBlank(
            $canvasWidth,
            $canvasHeight,
            ColorProvider::hexRgb($opt->getCanvasColor())->toArray()
        );

        $image->saveAlpha(true);
        $image->place($thumb, '50%', '50%');

        return $image;
    }

    /**
     * Resize mode: EXACT fill.
     */
    private static function resizeExact(AppGenerator $src, ImageOptionsDTO $opt): AppGenerator
    {
        $w = $opt->getWidth();
        $h = $opt->getHeight();

        $img = clone $src;
        $img->resize(
            $w ?? $h ?? $img->getWidth(),
            $h ?? $w ?? $img->getHeight(),
            AppGenerator::EXACT,
            true
        );

        return $img;
    }

    /**
     * Resize mode: FIT.
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
     * Resize mode: SHRINK_ONLY (default).
     */
    private static function resizeShrink(AppGenerator $src, ImageOptionsDTO $opt): AppGenerator
    {
        $w = $opt->getWidth();
        $h = $opt->getHeight();

        $img = clone $src;
        $img->resize(
            $w ?? $h ?? $img->getWidth(),
            $h ?? $w ?? $img->getHeight(),
            AppGenerator::SHRINK_ONLY,
            true
        );

        return $img;
    }

    /**
     * Saves processed image into cache directory.
     */
    private static function saveCache(
        FileProvider $app,
        AppGenerator $image,
        int $quality,
        int $imgExt
    ): void {
        $cacheFile = $app->getCacheFile();
        $cacheWebp = $app->getCacheWebp();

        if ($cacheFile === null || $cacheWebp === null) {
            throw ImageCacheException::missingCachePath();
        }

        $app->createDirectory($cacheFile);

        if (!WebpProvider::hasSupport()) {
            $image->save($cacheFile, $quality, $imgExt);

            return;
        }

        // podpora WEBP
        if ($imgExt === AppGenerator::PNG) {
            $image->paletteToTrueColor();
        }

        $image->save($cacheWebp, $quality, AppGenerator::WEBP);
    }

    /**
     * Outputs final image to browser.
     */
    private static function outputImage(
        AppGenerator $image,
        int $imgExt,
        int $quality
    ): never {
        $data = $image->toString($imgExt, $quality);

        if ($data === '') {
            throw ImageRenderException::failed();
        }

        self::sendHeader($imgExt, strlen($data));
        self::sendContent($data);
    }

    /**
     * Detects image type based on source file.
     */
    private static function getType(FileProvider $app): int
    {
        $file = $app->getFileFs();

        if ($file === null) {
            throw ImageSourceException::missingSourcePath();
        }

        $type = AppGenerator::detectTypeFromFile($file);

        if ($type === null) {
            throw ImageTypeException::detectionFailed();
        }

        return $type;
    }
}
