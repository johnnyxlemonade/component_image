<?php

declare(strict_types=1);

namespace Lemonade\Image;

use GdImage;
use Lemonade\Image\Exceptions\Gd\GdExtensionNotLoadedException;
use Lemonade\Image\Exceptions\Gd\GdImageColorException;
use Lemonade\Image\Exceptions\Image\ImageCropException;
use Lemonade\Image\Exceptions\Image\ImageRenderException;
use Lemonade\Image\Exceptions\Image\ImageSourceException;
use Lemonade\Image\Exceptions\Image\ImageTypeException;
use Lemonade\Image\Exceptions\InvalidArgumentException;
use Lemonade\Image\Utils\FileSystem;
use Lemonade\Image\Utils\GdImageOperations;
use Lemonade\Image\Utils\ImageFormat;
use Lemonade\Image\Utils\ImageGeometry;
use Lemonade\Image\Utils\ImageRenderer;
use LogicException;
use Throwable;

use function error_get_last;
use function extension_loaded;
use function gd_info;
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
use function is_file;
use function max;
use function min;
use function round;
use function sprintf;

/**
 * Provides GD-based image manipulation and output generation.
 *
 * Supports loading, resizing, cropping, placing, rendering and saving images
 * in JPEG, PNG, GIF and WEBP formats.
 *
 * @package     Lemonade
 * @subpackage  Image
 * @category    Image
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
 *
 * @see GdImageOperations
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
                $color['alpha'],
            );

            GdImageOperations::alphaBlending($image, false);
            GdImageOperations::filledRectangle($image, 0, 0, $width - 1, $height - 1, $resolvedColor);
            GdImageOperations::alphaBlending($image, true);
        }

        return new self($image);
    }

    public static function detectTypeFromFile(string $file): ?int
    {
        return ImageFormat::detectTypeFromFile($file);
    }

    public static function detectTypeFromString(string $s): ?int
    {
        return ImageFormat::detectTypeFromString($s);
    }

    public static function typeToExtension(int $type): string
    {
        return ImageFormat::typeToExtension($type);
    }

    public static function typeToMimeType(int $type): string
    {
        return ImageFormat::typeToMimeType($type);
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
                $name,
            )),
        };
    }

    public function __isset(string $name): bool
    {
        return in_array($name, ['width', 'height', 'imageResource'], true);
    }

    public function resize(
        int|string|null $width = null,
        int|string|null $height = null,
        int $mode = self::FIT,
        bool $shrinkOnly = false,
    ): self {
        if ($mode === self::EXACT) {
            return $this
                ->resize($width, $height, self::FILL)
                ->crop('50%', '50%', $width, $height);
        }

        [$newWidth, $newHeight] = ImageGeometry::calculateSize(
            $this->getWidth(),
            $this->getHeight(),
            $width,
            $height,
            $mode,
            $shrinkOnly,
        );

        if ($newWidth !== $this->getWidth() || $newHeight !== $this->getHeight()) {
            $newImage = self::fromBlank(
                $newWidth,
                $newHeight,
                self::rgb(0, 0, 0, 127),
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
                $this->getHeight(),
            );

            $this->image = $newImage;
        }

        $flipMode = ImageGeometry::resolveFlipMode($width, $height);

        if ($flipMode !== null) {
            imageflip($this->image, $flipMode);
        }

        return $this;
    }

    /**
     * @return array{0: int, 1: int}
     */
    public static function calculateSize(
        int $srcWidth,
        int $srcHeight,
        int|string|null $newWidth,
        int|string|null $newHeight,
        int $mode = self::FIT,
        bool $shrinkOnly = false,
    ): array {
        return ImageGeometry::calculateSize(
            $srcWidth,
            $srcHeight,
            $newWidth,
            $newHeight,
            $mode,
            $shrinkOnly,
        );
    }

    public function crop(
        int|string $left,
        int|string $top,
        int|string|null $width,
        int|string|null $height,
    ): self {
        [$x, $y, $cutWidth, $cutHeight] = ImageGeometry::calculateCutout(
            $this->getWidth(),
            $this->getHeight(),
            $left,
            $top,
            $width,
            $height,
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
            $cutHeight,
        );

        $this->image = $newImage;

        return $this;
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
        return ImageGeometry::calculateCutout(
            $srcWidth,
            $srcHeight,
            $left,
            $top,
            $newWidth,
            $newHeight,
        );
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

    public function place(self $image, int|string $left = 0, int|string $top = 0, int $opacity = 100): self
    {
        $opacity = max(0, min(100, $opacity));

        if ($opacity === 0) {
            return $this;
        }

        $width = $image->getWidth();
        $height = $image->getHeight();

        $x = ImageGeometry::resolveOffset($left, $this->getWidth() - $width);
        $y = ImageGeometry::resolveOffset($top, $this->getHeight() - $height);

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

    public function save(string $file, ?int $quality = null, ?int $type = null, ?FileSystem $filesystem = null): void
    {
        ImageRenderer::save($this->image, $file, $quality, $type, $filesystem);
    }

    public function toString(int $type = self::JPEG, ?int $quality = null): string
    {
        return ImageRenderer::toString($this->image, $type, $quality);
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
        ImageRenderer::send($this->image, $type, $quality);
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
