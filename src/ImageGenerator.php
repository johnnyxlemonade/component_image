<?php

declare(strict_types=1);

namespace Lemonade\Image;

use Lemonade\Image\Exceptions\Image\ImagePlaceholderException;
use Lemonade\Image\Exceptions\Image\ImageTypeException;
use Lemonade\Image\Providers\ColorProvider;
use Lemonade\Image\Providers\WebpProvider;

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

    public function __construct(
        private readonly ImageFileInspector $fileInspector,
    ) {}

    public function createVariant(ImageFileContext $file): ImageResult
    {
        $options = $file->getOptions();

        $source = $this->loadSource(
            file: $file,
        );

        $image = $this->processResize(
            source: $source,
            options: $options,
        );

        return new ImageResult(
            image: $image,
            type: $this->getType(
                file: $file,
            ),
            quality: $options->getQuality(),
        );
    }

    public function createFallback(ImageFileContext $file): ImageResult
    {
        $options = $file->getOptions();

        $type = WebpProvider::hasSupport()
            ? AppGenerator::WEBP
            : AppGenerator::PNG;

        return new ImageResult(
            image: $this->buildErrorImage(
                options: $options,
                file: $file,
            ),
            type: $type,
            quality: $options->getQuality(),
        );
    }

    private function loadSource(ImageFileContext $file): AppGenerator
    {
        return AppGenerator::fromFile(
            file: $file->getSourceFile(),
        );
    }

    private function processResize(AppGenerator $source, ImageOptionsDTO $options): AppGenerator
    {
        return match ($options->getCrop()) {
            -1 => clone $source,
            1 => $this->resizeFitWithCanvas(
                source: $source,
                options: $options,
                scale: self::CANVAS_SCALE_NORMAL,
            ),
            2 => $this->resizeExact(
                source: $source,
                options: $options,
            ),
            3 => $this->resizeFit(
                source: $source,
                options: $options,
            ),
            4 => $this->resizeFitWithCanvas(
                source: $source,
                options: $options,
                scale: self::CANVAS_SCALE_BIGGER,
            ),
            5 => $this->resizeFitWithCanvas(
                source: $source,
                options: $options,
                scale: self::CANVAS_SCALE_MAX,
            ),
            default => $this->resizeShrink(
                source: $source,
                options: $options,
            ),
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
            width: (int) round($canvasWidth * $scale),
            height: (int) round($canvasHeight * $scale),
            mode: AppGenerator::FIT,
            shrinkOnly: true,
        );

        $image = AppGenerator::fromBlank(
            width: $canvasWidth,
            height: $canvasHeight,
            color: ColorProvider::hexRgb($options->getCanvasColor())->toArray(),
        );

        $image->saveAlpha(
            save: true,
        );

        $image->place(
            image: $thumb,
            left: '50%',
            top: '50%',
        );

        return $image;
    }

    private function resizeExact(AppGenerator $source, ImageOptionsDTO $options): AppGenerator
    {
        $width = $options->getWidth();
        $height = $options->getHeight();

        $image = clone $source;
        $image->resize(
            width: $width ?? $height ?? $image->getWidth(),
            height: $height ?? $width ?? $image->getHeight(),
            mode: AppGenerator::EXACT,
            shrinkOnly: true,
        );

        return $image;
    }

    private function resizeFit(AppGenerator $source, ImageOptionsDTO $options): AppGenerator
    {
        $image = clone $source;
        $image->resize(
            width: $options->getWidth(),
            height: $options->getHeight(),
            mode: AppGenerator::FIT | AppGenerator::SHRINK_ONLY,
        );

        return $image;
    }

    private function resizeShrink(AppGenerator $source, ImageOptionsDTO $options): AppGenerator
    {
        $width = $options->getWidth();
        $height = $options->getHeight();

        $image = clone $source;
        $image->resize(
            width: $width ?? $height ?? $image->getWidth(),
            height: $height ?? $width ?? $image->getHeight(),
            mode: AppGenerator::SHRINK_ONLY,
            shrinkOnly: true,
        );

        return $image;
    }

    private function buildErrorImage(ImageOptionsDTO $options, ImageFileContext $file): AppGenerator
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

        $missingPng = $file->getMissingPng();

        if ($this->fileInspector->exists(
            file: $missingPng,
        )) {
            return AppGenerator::fromFile(
                file: $missingPng,
            );
        }

        $thumb = $this->loadErrorThumb();

        $thumb->resize(
            width: (int) round($width * self::CANVAS_SCALE_NORMAL),
            height: (int) round($height * self::CANVAS_SCALE_NORMAL),
            mode: AppGenerator::FIT | AppGenerator::SHRINK_ONLY,
            shrinkOnly: true,
        );

        $rgb = ColorProvider::hexRgb($canvas)->toArray();

        $image = AppGenerator::fromBlank(
            width: $width,
            height: $height,
            color: $rgb,
        );

        $image->paletteToTrueColor();

        $alpha = $image->colorAllocateAlpha(
            red: $rgb['red'],
            green: $rgb['green'],
            blue: $rgb['blue'],
            alpha: 0,
        );

        $image->fill(
            x: 0,
            y: 0,
            color: $alpha,
        );

        $image->saveAlpha(
            save: true,
        );

        $image->place(
            image: $thumb,
            left: '50%',
            top: '50%',
            opacity: 70,
        );

        return $image;
    }

    private function loadErrorThumb(): AppGenerator
    {
        $custom = './themes/frontend/error.png';

        if ($this->fileInspector->exists(
            file: $custom,
        )) {
            return AppGenerator::fromFile(
                file: $custom,
            );
        }

        $image = imagecreatetruecolor(
            width: 1,
            height: 1,
        );

        if ($image === false) {
            throw ImagePlaceholderException::createFailed();
        }

        imagesavealpha(
            image: $image,
            enable: true,
        );

        $white = imagecolorallocatealpha(
            image: $image,
            red: 255,
            green: 255,
            blue: 255,
            alpha: 0,
        );

        if ($white === false) {
            imagedestroy(
                image: $image,
            );

            throw ImagePlaceholderException::colorAllocationFailed();
        }

        imagefill(
            image: $image,
            x: 0,
            y: 0,
            color: $white,
        );

        ob_start();
        imagepng(
            image: $image,
        );

        $png = ob_get_clean();

        imagedestroy(
            image: $image,
        );

        if ($png === false) {
            throw ImagePlaceholderException::renderFailed();
        }

        return AppGenerator::fromString(
            s: $png,
        );
    }

    private function getType(ImageFileContext $file): int
    {
        $type = AppGenerator::detectTypeFromFile(
            file: $file->getSourceFile(),
        );

        if ($type === null) {
            throw ImageTypeException::detectionFailed();
        }

        return $type;
    }

}
