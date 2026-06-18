<?php

declare(strict_types=1);

namespace Lemonade\Image\Options;

use function ctype_digit;
use function ctype_xdigit;
use function explode;
use function in_array;
use function mb_strlen;
use function mb_substr;
use function preg_match;

/**
 * Parses compact image option arguments into normalized image options.
 *
 * Converts request argument strings and presets into a validated immutable
 * options DTO used by the image processing workflow.
 *
 * @package     Lemonade
 * @subpackage  Image\Options
 * @category    Options
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
 */
final class ImageOptionsParser
{
    /**
     * Fixed size presets for UI/content thumbnails.
     * Values are defined in pixels and represent the 1× variant.
     */
    private const SIZE_PRESETS = [
        'xss' => [32, 32],
        'xs' => [48, 48],
        'sm' => [96, 96],
        'md' => [160, 160],
        'lg' => [320, 320],
        'xl' => [640, 640],
    ];

    /**
     * Prevents extreme preset scale values.
     */
    private const MAX_PRESET_SCALE = 3;

    private const DEFAULT_CANVAS = 'ffffff';
    private const DEFAULT_QUALITY = 72;

    private const MIN_QUALITY = 1;
    private const MAX_QUALITY = 100;

    private ?int $width = null;
    private ?int $height = null;
    private int $crop = 0;
    private string $canvas = self::DEFAULT_CANVAS;
    private int $quality = self::DEFAULT_QUALITY;
    private bool $missing = true;

    private int $minWidth;
    private int $minHeight;
    private int $maxWidth;
    private int $maxHeight;

    public function __construct(
        ?string $args = null,
        int $minWidth = 50,
        int $minHeight = 50,
        int $maxWidth = 2560,
        int $maxHeight = 2560,
    ) {
        $this->minWidth = $minWidth;
        $this->minHeight = $minHeight;
        $this->maxWidth = $maxWidth;
        $this->maxHeight = $maxHeight;

        $this->parse($args);
        $this->applyLimits();
    }

    /**
     * Parses the compact argument string.
     *
     * Order matters: the last valid value wins.
     *
     * Supported token groups:
     * 1) preset / presetN
     * 2) original
     * 3) key-value tokens: w,h,q,c,e,z
     */
    private function parse(?string $args): void
    {
        $args = (string) $args;

        if ($args === '') {
            return;
        }

        foreach (explode('-', $args) as $item) {
            if ($this->parsePreset($item)) {
                continue;
            }

            if ($this->parseOriginal($item)) {
                continue;
            }

            $this->parseKeyValue($item);
        }
    }

    /**
     * Parses a size preset, for example md or md2.
     *
     * A preset is syntactic sugar for width/height and is not distinguished
     * from explicit w/h values after parsing.
     *
     * The numeric suffix represents scale, for example md2 = 2× md.
     */
    private function parsePreset(string $item): bool
    {
        if (preg_match('~^([a-z]+)(\d+)?$~', $item, $matches) !== 1) {
            return false;
        }

        $preset = $matches[1];
        $scale = isset($matches[2]) ? (int) $matches[2] : 1;

        if (
            !isset(self::SIZE_PRESETS[$preset]) ||
            $scale < 1 ||
            $scale > self::MAX_PRESET_SCALE
        ) {
            return false;
        }

        [$width, $height] = self::SIZE_PRESETS[$preset];

        $this->width = $width * $scale;
        $this->height = $height * $scale;

        return true;
    }

    /**
     * Parses original mode.
     *
     * Original mode preserves the source dimensions and bypasses size fallback
     * and width/height limits.
     */
    private function parseOriginal(string $item): bool
    {
        if ($item !== 'original') {
            return false;
        }

        $this->crop = -1;
        $this->width = null;
        $this->height = null;

        return true;
    }

    /**
     * Parses a key-value token: w,h,q,c,e,z.
     *
     * Unknown or invalid tokens are ignored.
     */
    private function parseKeyValue(string $item): void
    {
        $key = mb_substr($item, 0, 1);
        $value = mb_substr($item, 1);

        match ($key) {
            'w' => $this->applyWidth($value),
            'h' => $this->applyHeight($value),
            'q' => $this->applyQuality($value),
            'c' => $this->applyCanvas($value),
            'e' => $this->applyMissing($value),
            'z' => $this->applyCrop($value),
            default => null,
        };
    }

    private function applyWidth(string $value): void
    {
        if (!ctype_digit($value)) {
            return;
        }

        $width = (int) $value;

        if ($width < 1) {
            return;
        }

        $this->width = $width;
    }

    private function applyHeight(string $value): void
    {
        if (!ctype_digit($value)) {
            return;
        }

        $height = (int) $value;

        if ($height < 1) {
            return;
        }

        $this->height = $height;
    }

    private function applyQuality(string $value): void
    {
        if (!ctype_digit($value)) {
            return;
        }

        $quality = (int) $value;

        if ($quality < self::MIN_QUALITY) {
            $quality = self::MIN_QUALITY;
        }

        if ($quality > self::MAX_QUALITY) {
            $quality = self::MAX_QUALITY;
        }

        $this->quality = $quality;
    }

    private function applyCanvas(string $value): void
    {
        if (!ctype_xdigit($value) || mb_strlen($value) !== 6) {
            return;
        }

        $this->canvas = $value;
    }

    private function applyMissing(string $value): void
    {
        if ($value !== '0' && $value !== '1') {
            return;
        }

        $this->missing = $value === '1';
    }

    private function applyCrop(string $value): void
    {
        if (!ctype_digit($value)) {
            return;
        }

        $crop = (int) $value;

        if (!in_array($crop, ImageResizeMode::legacyCropValues(), true)) {
            return;
        }

        $this->crop = $crop;
    }

    /**
     * Normalizes dimensions according to configured limits.
     *
     * Original mode intentionally bypasses limits and size fallback.
     */
    private function applyLimits(): void
    {
        if ($this->crop === -1) {
            return;
        }

        if ($this->width === null && $this->height === null) {
            $this->width = $this->minWidth;
            $this->height = $this->minHeight;

            return;
        }

        if ($this->width !== null) {
            if ($this->width < $this->minWidth) {
                $this->width = $this->minWidth;
            }

            if ($this->width > $this->maxWidth) {
                $this->width = $this->maxWidth;
            }
        }

        if ($this->height !== null) {
            if ($this->height < $this->minHeight) {
                $this->height = $this->minHeight;
            }

            if ($this->height > $this->maxHeight) {
                $this->height = $this->maxHeight;
            }
        }
    }

    public function toDTO(): ImageOptionsDTO
    {
        return new ImageOptionsDTO(
            width: $this->width,
            height: $this->height,
            resizeMode: ImageResizeMode::fromLegacyCrop($this->crop),
            canvasColor: $this->canvas,
            quality: $this->quality,
            missing: $this->missing,
        );
    }

}
