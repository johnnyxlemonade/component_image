<?php

declare(strict_types=1);

namespace Lemonade\Image\Utils;

use GdImage;
use Lemonade\Image\Exceptions\Gd\GdImageAlphaException;
use Lemonade\Image\Exceptions\Gd\GdImageColorException;
use Lemonade\Image\Exceptions\Gd\GdImageCopyException;
use Lemonade\Image\Exceptions\Gd\GdImageCreateException;
use Lemonade\Image\Exceptions\Gd\GdImageLoadException;

use function error_get_last;
use function imagealphablending;
use function imagecolorallocatealpha;
use function imagecolorresolvealpha;
use function imagecopy;
use function imagecopyresampled;
use function imagecreatefromgif;
use function imagecreatefromjpeg;
use function imagecreatefrompng;
use function imagecreatefromstring;
use function imagecreatefromwebp;
use function imagecreatetruecolor;
use function imagefill;
use function imagefilledrectangle;
use function imagegif;
use function imagejpeg;
use function imagepng;
use function imagesavealpha;
use function imagewebp;
use function max;
use function min;

/**
 * Wraps low-level GD operations with strict return handling.
 *
 * Normalizes GD function results and converts failures into typed component
 * exceptions.
 *
 * @package     Lemonade
 * @subpackage  Image\Utils
 * @category    Utility
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
 */
final class GdImageOperations
{
    public static function createTrueColor(int $width, int $height): GdImage
    {
        $width = self::positiveInt($width);
        $height = self::positiveInt($height);

        $image = imagecreatetruecolor($width, $height);

        if (!$image instanceof GdImage) {
            throw GdImageCreateException::forTrueColorImage(
                $width,
                $height,
                self::getLastError()
            );
        }

        return $image;
    }

    /**
     * @param int $red 0..255
     * @param int $green 0..255
     * @param int $blue 0..255
     * @param int $alpha 0..127
     */
    public static function resolveAlpha(
        GdImage $image,
        int $red,
        int $green,
        int $blue,
        int $alpha = 0
    ): int {
        return imagecolorresolvealpha(
            $image,
            self::color($red),
            self::color($green),
            self::color($blue),
            self::alpha($alpha)
        );
    }

    /**
     * @param int $red 0..255
     * @param int $green 0..255
     * @param int $blue 0..255
     * @param int $alpha 0..127
     */
    public static function allocateAlpha(
        GdImage $image,
        int $red,
        int $green,
        int $blue,
        int $alpha = 0
    ): int {
        $color = imagecolorallocatealpha(
            $image,
            self::color($red),
            self::color($green),
            self::color($blue),
            self::alpha($alpha)
        );

        if ($color === false) {
            return self::resolveAlpha($image, $red, $green, $blue, $alpha);
        }

        return $color;
    }

    public static function fill(GdImage $image, int $x, int $y, int $color): void
    {
        if (!imagefill($image, $x, $y, $color)) {
            throw GdImageColorException::fill(self::getLastError());
        }
    }

    public static function filledRectangle(
        GdImage $image,
        int $x1,
        int $y1,
        int $x2,
        int $y2,
        int $color
    ): void {
        if (!imagefilledrectangle($image, $x1, $y1, $x2, $y2, $color)) {
            throw GdImageColorException::filledRectangle(self::getLastError());
        }
    }

    public static function copy(
        GdImage $destination,
        GdImage $source,
        int $dstX,
        int $dstY,
        int $srcX,
        int $srcY,
        int $srcWidth,
        int $srcHeight
    ): void {
        if (!imagecopy($destination, $source, $dstX, $dstY, $srcX, $srcY, $srcWidth, $srcHeight)) {
            throw GdImageCopyException::copy(self::getLastError());
        }
    }

    public static function copyResampled(
        GdImage $destination,
        GdImage $source,
        int $dstX,
        int $dstY,
        int $srcX,
        int $srcY,
        int $dstWidth,
        int $dstHeight,
        int $srcWidth,
        int $srcHeight
    ): void {
        if (!imagecopyresampled(
            $destination,
            $source,
            $dstX,
            $dstY,
            $srcX,
            $srcY,
            self::positiveInt($dstWidth),
            self::positiveInt($dstHeight),
            self::positiveInt($srcWidth),
            self::positiveInt($srcHeight)
        )) {
            throw GdImageCopyException::resample(self::getLastError());
        }
    }

    public static function saveAlpha(GdImage $image, bool $enabled): void
    {
        if (!imagesavealpha($image, $enabled)) {
            throw GdImageAlphaException::saveAlpha(self::getLastError());
        }
    }

    public static function alphaBlending(GdImage $image, bool $enabled): void
    {
        if (!imagealphablending($image, $enabled)) {
            throw GdImageAlphaException::alphaBlending(self::getLastError());
        }
    }

    public static function createFromFile(string $file, int $type): GdImage
    {
        $image = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($file),
            IMAGETYPE_PNG => @imagecreatefrompng($file),
            IMAGETYPE_GIF => @imagecreatefromgif($file),
            IMAGETYPE_WEBP => @imagecreatefromwebp($file),
            default => false,
        };

        if (!$image instanceof GdImage) {
            throw GdImageLoadException::fromFile($file, self::getLastError());
        }

        return $image;
    }

    public static function createFromString(string $content): GdImage
    {
        $image = @imagecreatefromstring($content);

        if (!$image instanceof GdImage) {
            throw GdImageCreateException::fromString(self::getLastError());
        }

        return $image;
    }

    public static function outputJpeg(GdImage $image, ?string $file, int $quality): bool
    {
        return @imagejpeg($image, $file, max(0, min(100, $quality)));
    }

    public static function outputPng(GdImage $image, ?string $file, int $quality): bool
    {
        return @imagepng($image, $file, max(0, min(9, $quality)));
    }

    public static function outputGif(GdImage $image, ?string $file): bool
    {
        return @imagegif($image, $file);
    }

    public static function outputWebp(GdImage $image, ?string $file, int $quality): bool
    {
        return @imagewebp($image, $file, max(0, min(100, $quality)));
    }

    /**
     * @return int<0, 255>
     */
    private static function color(int $value): int
    {
        return max(0, min(255, $value));
    }

    /**
     * @return int<0, 127>
     */
    private static function alpha(int $value): int
    {
        return max(0, min(127, $value));
    }

    /**
     * @return int<1, max>
     */
    private static function positiveInt(int $value): int
    {
        return max(1, $value);
    }

    private static function getLastError(): string
    {
        $error = error_get_last();

        if ($error === null) {
            return '';
        }

        return $error['message'];
    }
}
