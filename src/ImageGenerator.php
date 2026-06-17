<?php

declare(strict_types=1);

namespace Lemonade\Image;

use Lemonade\Image\Exceptions\Image\ImageCacheException;
use Lemonade\Image\Exceptions\Image\ImagePlaceholderException;
use Lemonade\Image\Exceptions\Image\ImageSourceException;
use Lemonade\Image\Exceptions\Image\ImageTypeException;
use Lemonade\Image\Providers\ColorProvider;
use Lemonade\Image\Providers\FileProvider;
use Lemonade\Image\Providers\WebpProvider;

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

final class ImageGenerator
{
    private const CANVAS_SCALE_NORMAL = 0.75;
    private const CANVAS_SCALE_BIGGER = 0.82;
    private const CANVAS_SCALE_MAX = 0.90;

    public function createVariant(FileProvider $provider): ImageResult
    {
        $options = $provider->getData()->getDTO();

        $source = $this->loadSource($provider);
        $image = $this->processResize($source, $options);

        $type = $this->getType($provider);
        $quality = $options->getQuality();

        $this->saveCache($provider, $image, $quality, $type);

        return new ImageResult(
            image: $image,
            type: $type,
            quality: $quality,
        );
    }

    public function createFallback(FileProvider $provider): ImageResult
    {
        $options = $provider->getData()->getDTO();

        $image = $this->buildErrorImage($options, $provider);
        $type = WebpProvider::hasSupport()
            ? AppGenerator::WEBP
            : AppGenerator::PNG;

        $quality = $options->getQuality();

        $this->saveErrorCache($provider, $image, $quality, $type);

        return new ImageResult(
            image: $image,
            type: $type,
            quality: $quality,
        );
    }

    private function loadSource(FileProvider $provider): AppGenerator
    {
        $file = $provider->getFileFs();

        if ($file === null) {
            throw ImageSourceException::missingSourcePath();
        }

        return AppGenerator::fromFile($file);
    }

    private function processResize(AppGenerator $source, ImageOptionsDTO $options): AppGenerator
    {
        return match ($options->getCrop()) {
            -1 => clone $source,
            1 => $this->resizeFitWithCanvas($source, $options, self::CANVAS_SCALE_NORMAL),
            2 => $this->resizeExact($source, $options),
            3 => $this->resizeFit($source, $options),
            4 => $this->resizeFitWithCanvas($source, $options, self::CANVAS_SCALE_BIGGER),
            5 => $this->resizeFitWithCanvas($source, $options, self::CANVAS_SCALE_MAX),
            default => $this->resizeShrink($source, $options),
        };
    }

    private function resizeFitWithCanvas(
        AppGenerator $source,
        ImageOptionsDTO $options,
        float $scale,
    ): AppGenerator {
        $width = $options->getWidth();
        $height = $options->getHeight();

        $canvasWidth = $width ?? $height ?? $source->getWidth();
        $canvasHeight = $height ?? $width ?? $source->getHeight();

        $thumb = clone $source;
        $thumb->resize(
            (int) round($canvasWidth * $scale),
            (int) round($canvasHeight * $scale),
            AppGenerator::FIT,
            true,
        );

        $image = AppGenerator::fromBlank(
            $canvasWidth,
            $canvasHeight,
            ColorProvider::hexRgb($options->getCanvasColor())->toArray(),
        );

        $image->saveAlpha(true);
        $image->place($thumb, '50%', '50%');

        return $image;
    }

    private function resizeExact(AppGenerator $source, ImageOptionsDTO $options): AppGenerator
    {
        $width = $options->getWidth();
        $height = $options->getHeight();

        $image = clone $source;
        $image->resize(
            $width ?? $height ?? $image->getWidth(),
            $height ?? $width ?? $image->getHeight(),
            AppGenerator::EXACT,
            true,
        );

        return $image;
    }

    private function resizeFit(AppGenerator $source, ImageOptionsDTO $options): AppGenerator
    {
        $image = clone $source;
        $image->resize(
            $options->getWidth(),
            $options->getHeight(),
            AppGenerator::FIT | AppGenerator::SHRINK_ONLY,
        );

        return $image;
    }

    private function resizeShrink(AppGenerator $source, ImageOptionsDTO $options): AppGenerator
    {
        $width = $options->getWidth();
        $height = $options->getHeight();

        $image = clone $source;
        $image->resize(
            $width ?? $height ?? $image->getWidth(),
            $height ?? $width ?? $image->getHeight(),
            AppGenerator::SHRINK_ONLY,
            true,
        );

        return $image;
    }

    private function buildErrorImage(ImageOptionsDTO $options, FileProvider $provider): AppGenerator
    {
        $width = $options->getWidth();
        $height = $options->getHeight();
        $canvas = $options->getCanvasColor();

        if ($width === null && $height === null) {
            $width = 600;
            $height = 600;
        } elseif ($width === null) {
            $width = $height;
        } elseif ($height === null) {
            $height = $width;
        }

        $missingPng = $provider->getMissingPng();

        if ($missingPng !== null && $provider->isFileExists($missingPng)) {
            return AppGenerator::fromFile($missingPng);
        }

        $thumb = $this->loadErrorThumb();
        $thumb->resize(
            (int) round($width * self::CANVAS_SCALE_NORMAL),
            (int) round($height * self::CANVAS_SCALE_NORMAL),
            AppGenerator::FIT | AppGenerator::SHRINK_ONLY,
            true,
        );

        $rgb = ColorProvider::hexRgb($canvas)->toArray();

        $image = AppGenerator::fromBlank(
            $width,
            $height,
            $rgb,
        );

        $image->paletteToTrueColor();

        $alpha = $image->colorAllocateAlpha(
            $rgb['red'],
            $rgb['green'],
            $rgb['blue'],
            0,
        );

        $image->fill(0, 0, $alpha);
        $image->saveAlpha(true);
        $image->place($thumb, '50%', '50%', 70);

        return $image;
    }

    private function loadErrorThumb(): AppGenerator
    {
        $custom = './themes/frontend/error.png';

        if (file_exists($custom)) {
            return AppGenerator::fromFile($custom);
        }

        $image = imagecreatetruecolor(1, 1);
        if ($image === false) {
            throw ImagePlaceholderException::createFailed();
        }

        imagesavealpha($image, true);

        $white = imagecolorallocatealpha($image, 255, 255, 255, 0);
        if ($white === false) {
            imagedestroy($image);

            throw ImagePlaceholderException::colorAllocationFailed();
        }

        imagefill($image, 0, 0, $white);

        ob_start();
        imagepng($image);
        $png = ob_get_clean();
        imagedestroy($image);

        if ($png === false) {
            throw ImagePlaceholderException::renderFailed();
        }

        return AppGenerator::fromString($png);
    }

    private function saveCache(
        FileProvider $provider,
        AppGenerator $image,
        int $quality,
        int $type,
    ): void {
        $cacheFile = $provider->getCacheFile();
        $cacheWebp = $provider->getCacheWebp();

        if ($cacheFile === null || $cacheWebp === null) {
            throw ImageCacheException::missingCachePath();
        }

        $provider->createDirectory($cacheFile);

        if (!WebpProvider::hasSupport()) {
            $image->save($cacheFile, $quality, $type, $provider->getFilesystem());

            return;
        }

        if ($type === AppGenerator::PNG) {
            $image->paletteToTrueColor();
        }

        $image->save($cacheWebp, $quality, AppGenerator::WEBP, $provider->getFilesystem());
    }

    private function saveErrorCache(
        FileProvider $provider,
        AppGenerator $image,
        int $quality,
        int $type,
    ): void {
        $png = $provider->getMissingPng();
        $webp = $provider->getMissingWebp();

        if ($png === null || $webp === null) {
            throw ImageCacheException::missingErrorCachePath();
        }

        $provider->createDirectory($png);

        $image->save($png, $quality, AppGenerator::PNG, $provider->getFilesystem());

        if ($type === AppGenerator::WEBP) {
            $image->paletteToTrueColor();
            $image->save($webp, $quality, AppGenerator::WEBP, $provider->getFilesystem());
        }
    }

    private function getType(FileProvider $provider): int
    {
        $file = $provider->getFileFs();

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
