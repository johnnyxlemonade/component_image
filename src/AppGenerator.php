<?php

declare(strict_types=1);

namespace Lemonade\Image;

use Closure;
use GdImage;
use Lemonade\Image\Exceptions\Gd\GdExtensionNotLoadedException;
use Lemonade\Image\Exceptions\Gd\GdImageColorException;
use Lemonade\Image\Exceptions\Gd\GdImageOutputException;
use Lemonade\Image\Exceptions\Image\ImageCropException;
use Lemonade\Image\Exceptions\Image\ImageRenderException;
use Lemonade\Image\Exceptions\Image\ImageSourceException;
use Lemonade\Image\Exceptions\Image\ImageTypeException;
use Lemonade\Image\Exceptions\InvalidArgumentException;
use Lemonade\Image\Utils\GdImageOperations;
use LogicException;
use Throwable;

use function abs;
use function array_flip;
use function bin2hex;
use function dirname;
use function error_get_last;
use function extension_loaded;
use function function_exists;
use function gd_info;
use function getimagesize;
use function getimagesizefromstring;
use function getmypid;
use function header;
use function imagecolorat;
use function imageconvolution;
use function imagecrop;
use function imageflip;
use function imageistruecolor;
use function imagepalettetotruecolor;
use function imagesetpixel;
use function imagesx;
use function imagesy;
use function in_array;
use function is_array;
use function is_dir;
use function is_file;
use function is_int;
use function is_numeric;
use function is_string;
use function max;
use function min;
use function mkdir;
use function ob_end_clean;
use function ob_get_clean;
use function ob_get_level;
use function ob_start;
use function pathinfo;
use function random_bytes;
use function rename;
use function round;
use function sprintf;
use function str_ends_with;
use function strtolower;
use function substr;
use function unlink;

use const PATHINFO_EXTENSION;

/**
 * Basic manipulation with images. Supported types are JPEG, PNG, GIF, WEBP.
 *
 * @property-read int $width
 * @property-read int $height
 * @property-read GdImage $imageResource
 */
final class AppGenerator
{
    public const SHRINK_ONLY = 0b0001;
    public const STRETCH = 0b0010;
    public const FIT = 0b0000;
    public const FILL = 0b0100;
    public const EXACT = 0b1000;

    public const JPEG = IMAGETYPE_JPEG;
    public const PNG = IMAGETYPE_PNG;
    public const GIF = IMAGETYPE_GIF;
    public const WEBP = 18;

    public const EMPTY_GIF = "GIF89a\x01\x00\x01\x00\x80\x00\x00\x00\x00\x00\x00\x00\x00!\xf9\x04\x01\x00\x00\x00\x00,\x00\x00\x00\x00\x01\x00\x01\x00\x00\x02\x02D\x01\x00;";

    /**
     * @var array<int, string>
     */
    public const FORMATS = [
        self::JPEG => 'jpeg',
        self::PNG => 'png',
        self::GIF => 'gif',
        self::WEBP => 'webp',
    ];

    private GdImage $image;

    /**
     * @return array{red: int, green: int, blue: int, alpha: int}
     */
    public static function rgb(int $red, int $green, int $blue, int $transparency = 0): array
    {
        return [
            'red' => max(0, min(255, $red)),
            'green' => max(0, min(255, $green)),
            'blue' => max(0, min(255, $blue)),
            'alpha' => max(0, min(127, $transparency)),
        ];
    }

    public static function fromFile(string $file, ?int $type = null): self
    {
        self::assertGdLoaded();

        $type ??= self::detectTypeFromFile($file);

        if ($type === null) {
            throw is_file($file)
                ? ImageTypeException::unknownFile($file)
                : ImageSourceException::fileNotFound($file);
        }

        return new self(GdImageOperations::createFromFile($file, $type));
    }

    public static function fromString(string $s, ?int $type = null): self
    {
        self::assertGdLoaded();

        $type ??= self::detectTypeFromString($s);

        if ($type === null) {
            throw ImageTypeException::unknownString();
        }

        return new self(GdImageOperations::createFromString($s));
    }

    /**
     * @param array{red: int, green: int, blue: int, alpha?: int}|null $color
     */
    public static function fromBlank(int $width, int $height, ?array $color = null): self
    {
        self::assertGdLoaded();

        if ($width < 1 || $height < 1) {
            throw new InvalidArgumentException('Image width and height must be greater than zero.');
        }

        $image = GdImageOperations::createTrueColor($width, $height);

        if ($color !== null) {
            $color += ['alpha' => 0];

            $resolvedColor = GdImageOperations::resolveAlpha(
                $image,
                $color['red'],
                $color['green'],
                $color['blue'],
                $color['alpha']
            );

            GdImageOperations::alphaBlending($image, false);
            GdImageOperations::filledRectangle($image, 0, 0, $width - 1, $height - 1, $resolvedColor);
            GdImageOperations::alphaBlending($image, true);
        }

        return new self($image);
    }

    public static function detectTypeFromFile(string $file): ?int
    {
        $info = @getimagesize($file);

        if (!is_array($info)) {
            return null;
        }

        $type = $info[2];

        return isset(self::FORMATS[$type])
            ? $type
            : null;
    }

    public static function detectTypeFromString(string $s): ?int
    {
        $info = @getimagesizefromstring($s);

        if (!is_array($info)) {
            return null;
        }

        $type = $info[2];

        return isset(self::FORMATS[$type])
            ? $type
            : null;
    }

    public static function typeToExtension(int $type): string
    {
        if (!isset(self::FORMATS[$type])) {
            throw ImageTypeException::unsupported($type);
        }

        return self::FORMATS[$type];
    }

    public static function typeToMimeType(int $type): string
    {
        return 'image/' . self::typeToExtension($type);
    }

    public function __construct(GdImage $image)
    {
        $this->setImageResource($image);
        GdImageOperations::saveAlpha($this->image, true);
    }

    public function getWidth(): int
    {
        return imagesx($this->image);
    }

    public function getHeight(): int
    {
        return imagesy($this->image);
    }

    private function setImageResource(GdImage $image): self
    {
        $this->image = $image;

        return $this;
    }

    public function getImageResource(): GdImage
    {
        return $this->image;
    }

    public function __get(string $name): mixed
    {
        return match ($name) {
            'width' => $this->getWidth(),
            'height' => $this->getHeight(),
            'imageResource' => $this->getImageResource(),
            default => throw new LogicException(sprintf(
                'Undefined property %s::$%s.',
                self::class,
                $name
            )),
        };
    }

    public function __isset(string $name): bool
    {
        return in_array($name, ['width', 'height', 'imageResource'], true);
    }

    /**
     * @param int|string|null $width
     * @param int|string|null $height
     */
    public function resize(
        int|string|null $width = null,
        int|string|null $height = null,
        int $mode = self::FIT,
        bool $shrinkOnly = false
    ): self {
        if ($mode === self::EXACT) {
            return $this
                ->resize($width, $height, self::FILL)
                ->crop('50%', '50%', $width, $height);
        }

        [$newWidth, $newHeight] = self::calculateSize(
            $this->getWidth(),
            $this->getHeight(),
            $width,
            $height,
            $mode,
            $shrinkOnly
        );

        if ($newWidth !== $this->getWidth() || $newHeight !== $this->getHeight()) {
            $newImage = self::fromBlank(
                $newWidth,
                $newHeight,
                self::rgb(0, 0, 0, 127)
            )->getImageResource();

            GdImageOperations::copyResampled(
                $newImage,
                $this->image,
                0,
                0,
                0,
                0,
                $newWidth,
                $newHeight,
                $this->getWidth(),
                $this->getHeight()
            );

            $this->image = $newImage;
        }

        $flipMode = self::resolveFlipMode($width, $height);

        if ($flipMode !== null) {
            imageflip($this->image, $flipMode);
        }

        return $this;
    }

    /**
     * @param int|string|null $newWidth
     * @param int|string|null $newHeight
     *
     * @return array{0: int, 1: int}
     */
    public static function calculateSize(
        int $srcWidth,
        int $srcHeight,
        int|string|null $newWidth,
        int|string|null $newHeight,
        int $mode = self::FIT,
        bool $shrinkOnly = false
    ): array {
        $shrinkOnly = $shrinkOnly || (($mode & self::SHRINK_ONLY) !== 0);

        [$targetWidth, $widthIsPercent] = self::resolveResizeDimension($newWidth, $srcWidth);
        [$targetHeight, $heightIsPercent] = self::resolveResizeDimension($newHeight, $srcHeight);

        if ($widthIsPercent && $heightIsPercent) {
            $mode |= self::STRETCH;
        }

        if (($mode & self::STRETCH) !== 0) {
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

        if (($mode & self::FILL) !== 0) {
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
     * @param int|string $left
     * @param int|string $top
     * @param int|string|null $width
     * @param int|string|null $height
     */
    public function crop(
        int|string $left,
        int|string $top,
        int|string|null $width,
        int|string|null $height
    ): self {
        [$x, $y, $cutWidth, $cutHeight] = self::calculateCutout(
            $this->getWidth(),
            $this->getHeight(),
            $left,
            $top,
            $width,
            $height
        );

        $gdInfo = gd_info();

        if (($gdInfo['GD Version'] ?? null) === 'bundled (2.1.0 compatible)') {
            $cropped = imagecrop($this->image, [
                'x' => $x,
                'y' => $y,
                'width' => $cutWidth,
                'height' => $cutHeight,
            ]);

            if (!$cropped instanceof GdImage) {
                throw ImageCropException::failed(self::getLastError());
            }

            $this->image = $cropped;
            GdImageOperations::saveAlpha($this->image, true);

            return $this;
        }

        $newImage = self::fromBlank($cutWidth, $cutHeight, self::rgb(0, 0, 0, 127))->getImageResource();

        GdImageOperations::copy(
            $newImage,
            $this->image,
            0,
            0,
            $x,
            $y,
            $cutWidth,
            $cutHeight
        );

        $this->image = $newImage;

        return $this;
    }

    /**
     * @param int|string|null $newWidth
     * @param int|string|null $newHeight
     *
     * @return array{0: int, 1: int, 2: int, 3: int}
     */
    public static function calculateCutout(
        int $srcWidth,
        int $srcHeight,
        int|string $left,
        int|string $top,
        int|string|null $newWidth,
        int|string|null $newHeight
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

    public function sharpen(): self
    {
        imageconvolution($this->image, [
            [-1, -1, -1],
            [-1, 24, -1],
            [-1, -1, -1],
        ], 16, 0);

        return $this;
    }

    /**
     * @param int|string $left
     * @param int|string $top
     */
    public function place(self $image, int|string $left = 0, int|string $top = 0, int $opacity = 100): self
    {
        $opacity = max(0, min(100, $opacity));

        if ($opacity === 0) {
            return $this;
        }

        $width = $image->getWidth();
        $height = $image->getHeight();

        $x = self::resolveOffset($left, $this->getWidth() - $width);
        $y = self::resolveOffset($top, $this->getHeight() - $height);

        $output = $image->image;
        $input = $image->image;

        if ($opacity < 100) {
            $table = [];

            for ($i = 0; $i < 128; $i++) {
                $table[$i] = (int) round(127 - (127 - $i) * $opacity / 100);
            }

            $output = GdImageOperations::createTrueColor($width, $height);

            GdImageOperations::alphaBlending($output, false);

            if (!$image->isTrueColor()) {
                $input = $output;

                $transparent = GdImageOperations::allocateAlpha($output, 0, 0, 0, 127);
                GdImageOperations::filledRectangle($output, 0, 0, $width, $height, $transparent);
                GdImageOperations::copy($output, $image->image, 0, 0, 0, 0, $width, $height);
            }

            for ($px = 0; $px < $width; $px++) {
                for ($py = 0; $py < $height; $py++) {
                    $color = imagecolorat($input, $px, $py);

                    if ($color === false) {
                        throw GdImageColorException::resolve(self::getLastError());
                    }

                    $color = ($color & 0xFFFFFF) + ($table[$color >> 24] << 24);

                    if (!imagesetpixel($output, $px, $py, $color)) {
                        throw GdImageColorException::fill(self::getLastError());
                    }
                }
            }

            GdImageOperations::alphaBlending($output, true);
        }

        GdImageOperations::copy($this->image, $output, $x, $y, 0, 0, $width, $height);

        return $this;
    }

    public function save(string $file, ?int $quality = null, ?int $type = null): void
    {
        if ($type === null) {
            $extensions = array_flip(self::FORMATS) + ['jpg' => self::JPEG];
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

            if (!isset($extensions[$ext])) {
                throw ImageTypeException::unsupportedExtension($ext);
            }

            $type = $extensions[$ext];
        }

        $this->output($type, $quality, $file);
    }

    public function toString(int $type = self::JPEG, ?int $quality = null): string
    {
        return self::capture(function () use ($type, $quality): void {
            $this->output($type, $quality);
        });
    }

    public function __toString(): string
    {
        try {
            return $this->toString();
        } catch (Throwable) {
            return '';
        }
    }

    public function send(int $type = self::JPEG, ?int $quality = null): void
    {
        header('Content-Type: ' . self::typeToMimeType($type));

        $this->output($type, $quality);
    }

    public function paletteToTrueColor(): void
    {
        if (!imagepalettetotruecolor($this->image)) {
            throw ImageRenderException::paletteToTrueColorFailed(self::getLastError());
        }
    }

    public function saveAlpha(bool $save): void
    {
        GdImageOperations::saveAlpha($this->image, $save);
    }

    public function alphaBlending(bool $enabled): void
    {
        GdImageOperations::alphaBlending($this->image, $enabled);
    }

    public function colorAllocateAlpha(int $red, int $green, int $blue, int $alpha): int
    {
        return GdImageOperations::allocateAlpha($this->image, $red, $green, $blue, $alpha);
    }

    public function fill(int $x, int $y, int $color): void
    {
        GdImageOperations::fill($this->image, $x, $y, $color);
    }

    public function isTrueColor(): bool
    {
        return imageistruecolor($this->image);
    }

    private function output(int $type, ?int $quality = null, ?string $file = null): void
    {
        $tmpFile = null;
        $isCache = $file !== null;

        if ($isCache) {
            $targetFile = $file;

            if (is_file($targetFile)) {
                return;
            }

            $dir = dirname($targetFile);

            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }

            $tmpFile = $targetFile . '.tmp.' . getmypid() . '.' . bin2hex(random_bytes(4));
            $file = $tmpFile;
        } else {
            $targetFile = null;
        }

        $success = match ($type) {
            self::JPEG => GdImageOperations::outputJpeg($this->image, $file, $quality ?? 85),
            self::PNG => GdImageOperations::outputPng($this->image, $file, $quality ?? 9),
            self::GIF => GdImageOperations::outputGif($this->image, $file),
            self::WEBP => GdImageOperations::outputWebp($this->image, $file, $quality ?? 80),
            default => throw ImageTypeException::unsupported($type),
        };

        if (!$success) {
            if ($tmpFile !== null && is_file($tmpFile)) {
                @unlink($tmpFile);
            }

            if ($isCache) {
                if (function_exists('log_message')) {
                    $lastError = self::getLastError();

                    log_message(
                        'error',
                        sprintf(
                            'Image cache write failed (%s): %s',
                            $targetFile,
                            $lastError !== '' ? $lastError : 'unknown GD error'
                        )
                    );
                }

                return;
            }

            $lastError = self::getLastError();

            throw GdImageOutputException::output(
                $lastError !== '' ? $lastError : 'Image output failed.'
            );
        }

        if ($tmpFile !== null) {
            if (is_file($targetFile)) {
                @unlink($tmpFile);

                return;
            }

            if (!@rename($tmpFile, $targetFile)) {
                @unlink($targetFile);

                if (!@rename($tmpFile, $targetFile)) {
                    @unlink($tmpFile);

                    if (function_exists('log_message')) {
                        log_message(
                            'error',
                            sprintf('Failed to move image cache file: %s', $targetFile)
                        );
                    }
                }
            }
        }
    }

    /**
     * Dočasný GD bridge kvůli zpětné kompatibilitě.
     *
     * @param array<int, mixed> $args
     */
    public function __call(string $name, array $args): mixed
    {
        $function = 'image' . $name;

        if (!function_exists($function)) {
            throw new LogicException(sprintf('Call to undefined method: %s::%s()', self::class, $name));
        }

        foreach ($args as $key => $value) {
            if ($value instanceof self) {
                $args[$key] = $value->getImageResource();

                continue;
            }

            if (self::isRgbArray($value)) {
                $args[$key] = GdImageOperations::allocateAlpha(
                    $this->image,
                    $value['red'],
                    $value['green'],
                    $value['blue'],
                    $value['alpha'] ?? 0
                );
            }
        }

        $result = $function($this->image, ...$args);

        return $result instanceof GdImage
            ? $this->setImageResource($result)
            : $result;
    }

    public function __clone()
    {
        $width = $this->getWidth();
        $height = $this->getHeight();

        $new = GdImageOperations::createTrueColor($width, $height);

        GdImageOperations::alphaBlending($new, false);
        GdImageOperations::saveAlpha($new, true);
        GdImageOperations::copy($new, $this->image, 0, 0, 0, 0, $width, $height);

        $this->setImageResource($new);
    }

    /**
     * Prevents serialization.
     *
     * @return list<string>
     */
    public function __sleep(): array
    {
        throw new LogicException('You cannot serialize or unserialize ' . self::class . ' instances.');
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

    private static function resolveOffset(int|string $offset, int $availableSpace): int
    {
        if (is_string($offset) && str_ends_with($offset, '%')) {
            $percent = self::numericStringToFloat(substr($offset, 0, -1));

            return (int) round($availableSpace / 100 * $percent);
        }

        return self::numericDimensionToInt($offset);
    }

    private static function resolveFlipMode(int|string|null $width, int|string|null $height): ?int
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
                $dimension
            ));
        }

        return (int) $dimension;
    }

    private static function numericStringToFloat(string $value): float
    {
        if (!is_numeric($value)) {
            throw new InvalidArgumentException(sprintf(
                'Expected numeric percentage, "%s" given.',
                $value
            ));
        }

        return (float) $value;
    }

    /**
     * @phpstan-assert-if-true array{red: int, green: int, blue: int, alpha?: int} $value
     */
    private static function isRgbArray(mixed $value): bool
    {
        return is_array($value)
            && isset($value['red'], $value['green'], $value['blue'])
            && is_int($value['red'])
            && is_int($value['green'])
            && is_int($value['blue'])
            && (!isset($value['alpha']) || is_int($value['alpha']));
    }

    private static function capture(Closure $callback): string
    {
        ob_start();

        try {
            $callback();

            $content = ob_get_clean();

            if ($content === false) {
                throw ImageRenderException::outputBufferFailed();
            }

            return $content;
        } catch (Throwable $e) {
            if (ob_get_level() > 0) {
                ob_end_clean();
            }

            throw $e;
        }
    }

    private static function assertGdLoaded(): void
    {
        if (!extension_loaded('gd')) {
            throw GdExtensionNotLoadedException::create();
        }
    }

    private static function getLastError(): string
    {
        $error = error_get_last();

        return $error === null ? '' : $error['message'];
    }
}
