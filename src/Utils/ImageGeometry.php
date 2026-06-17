<?php

declare(strict_types=1);

namespace Lemonade\Image\Utils;

use Lemonade\Image\Exceptions\InvalidArgumentException;

use Lemonade\Image\Generator\AppGenerator;

use function abs;
use function is_int;
use function is_numeric;
use function is_string;
use function max;
use function min;
use function round;
use function sprintf;
use function str_ends_with;
use function substr;

/**
 * Provides image geometry calculations for resize, crop and flip operations.
 *
 * Keeps dimension parsing and transformation math separated from GD image
 * manipulation.
 *
 * @package     Lemonade
 * @subpackage  Image\Utils
 * @category    Utility
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
 */
final class ImageGeometry
{
    /**
     * @return array{0: int, 1: int}
     */
    public static function calculateSize(
        int $srcWidth,
        int $srcHeight,
        int|string|null $newWidth,
        int|string|null $newHeight,
        int $mode = AppGenerator::FIT,
        bool $shrinkOnly = false,
    ): array {
        $shrinkOnly = $shrinkOnly || (($mode & AppGenerator::SHRINK_ONLY) !== 0);

        [$targetWidth, $widthIsPercent] = self::resolveResizeDimension($newWidth, $srcWidth);
        [$targetHeight, $heightIsPercent] = self::resolveResizeDimension($newHeight, $srcHeight);

        if ($widthIsPercent && $heightIsPercent) {
            $mode |= AppGenerator::STRETCH;
        }

        if (($mode & AppGenerator::STRETCH) !== 0) {
            if ($targetWidth === null || $targetHeight === null) {
                throw new InvalidArgumentException('For stretching must be both width and height specified.');
            }

            if ($shrinkOnly) {
                $targetWidth = (int) round($srcWidth * min(1, $targetWidth / $srcWidth));
                $targetHeight = (int) round($srcHeight * min(1, $targetHeight / $srcHeight));
            }

            return [max($targetWidth, 1), max($targetHeight, 1)];
        }

        if ($targetWidth === null && $targetHeight === null) {
            throw new InvalidArgumentException('At least width or height must be specified.');
        }

        $scale = [];

        if ($targetWidth !== null && $targetWidth > 0) {
            $scale[] = $targetWidth / $srcWidth;
        }

        if ($targetHeight !== null && $targetHeight > 0) {
            $scale[] = $targetHeight / $srcHeight;
        }

        if ($scale === []) {
            throw new InvalidArgumentException('At least one valid target dimension must be specified.');
        }

        if (($mode & AppGenerator::FILL) !== 0) {
            $scale = [max($scale)];
        }

        if ($shrinkOnly) {
            $scale[] = 1;
        }

        $finalScale = min($scale);

        return [
            max((int) round($srcWidth * $finalScale), 1),
            max((int) round($srcHeight * $finalScale), 1),
        ];
    }

    /**
     * @return array{0: int, 1: int, 2: int, 3: int}
     */
    public static function calculateCutout(
        int $srcWidth,
        int $srcHeight,
        int|string $left,
        int|string $top,
        int|string|null $newWidth,
        int|string|null $newHeight,
    ): array {
        $cutWidth = self::resolveLength($newWidth, $srcWidth);
        $cutHeight = self::resolveLength($newHeight, $srcHeight);

        $x = self::resolveOffset($left, $srcWidth - $cutWidth);
        $y = self::resolveOffset($top, $srcHeight - $cutHeight);

        if ($x < 0) {
            $cutWidth += $x;
            $x = 0;
        }

        if ($y < 0) {
            $cutHeight += $y;
            $y = 0;
        }

        $cutWidth = min($cutWidth, $srcWidth - $x);
        $cutHeight = min($cutHeight, $srcHeight - $y);

        return [
            max($x, 0),
            max($y, 0),
            max($cutWidth, 1),
            max($cutHeight, 1),
        ];
    }

    public static function resolveOffset(int|string $offset, int $availableSpace): int
    {
        if (is_string($offset) && str_ends_with($offset, '%')) {
            $percent = self::numericStringToFloat(substr($offset, 0, -1));

            return (int) round($availableSpace / 100 * $percent);
        }

        return self::numericDimensionToInt($offset);
    }

    public static function resolveFlipMode(int|string|null $width, int|string|null $height): ?int
    {
        $flipX = self::isNegativeDimension($width);
        $flipY = self::isNegativeDimension($height);

        if ($flipX && $flipY) {
            return IMG_FLIP_BOTH;
        }

        if ($flipX) {
            return IMG_FLIP_HORIZONTAL;
        }

        if ($flipY) {
            return IMG_FLIP_VERTICAL;
        }

        return null;
    }

    /**
     * @return array{0: int|null, 1: bool}
     */
    private static function resolveResizeDimension(int|string|null $dimension, int $sourceSize): array
    {
        if ($dimension === null) {
            return [null, false];
        }

        if (is_string($dimension) && str_ends_with($dimension, '%')) {
            $percent = self::numericStringToFloat(substr($dimension, 0, -1));

            return [
                (int) round($sourceSize / 100 * abs($percent)),
                true,
            ];
        }

        return [
            abs(self::numericDimensionToInt($dimension)),
            false,
        ];
    }

    private static function resolveLength(int|string|null $dimension, int $sourceSize): int
    {
        if ($dimension === null) {
            return $sourceSize;
        }

        if (is_string($dimension) && str_ends_with($dimension, '%')) {
            $percent = self::numericStringToFloat(substr($dimension, 0, -1));

            return max((int) round($sourceSize / 100 * $percent), 1);
        }

        return max(self::numericDimensionToInt($dimension), 1);
    }

    private static function isNegativeDimension(int|string|null $dimension): bool
    {
        if ($dimension === null) {
            return false;
        }

        if (is_int($dimension)) {
            return $dimension < 0;
        }

        $value = str_ends_with($dimension, '%')
            ? substr($dimension, 0, -1)
            : $dimension;

        if (!is_numeric($value)) {
            return false;
        }

        return (float) $value < 0;
    }

    private static function numericDimensionToInt(int|string $dimension): int
    {
        if (is_int($dimension)) {
            return $dimension;
        }

        if (!is_numeric($dimension)) {
            throw new InvalidArgumentException(sprintf(
                'Expected numeric dimension, "%s" given.',
                $dimension,
            ));
        }

        return (int) $dimension;
    }

    private static function numericStringToFloat(string $value): float
    {
        if (!is_numeric($value)) {
            throw new InvalidArgumentException(sprintf(
                'Expected numeric percentage, "%s" given.',
                $value,
            ));
        }

        return (float) $value;
    }
}
