<?php

declare(strict_types=1);

namespace Lemonade\Image\Options;

use Lemonade\Image\Options\Config\ImageOptionsParserConfig;

use function ctype_digit;
use function ctype_xdigit;
use function explode;
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
    private readonly ImageOptionsParserConfig $config;

    private ?int $width = null;
    private ?int $height = null;
    private ImageResizeMode $resizeMode = ImageResizeMode::Shrink;
    private string $canvasColor;
    private int $quality;
    private bool $missing = true;

    public function __construct(
        ?string $args = null,
        ?ImageOptionsParserConfig $config = null,
    ) {
        $this->config = $config ?? ImageOptionsParserConfig::createDefault();
        $this->canvasColor = $this->config->getCanvas()->getDefaultColor();
        $this->quality = $this->config->getQuality()->getDefaultQuality();

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

        $presetCode = $matches[1];
        $scale = isset($matches[2]) ? (int) $matches[2] : 1;
        $presetConfig = $this->config->getSizePresets();
        $preset = $presetConfig->getPresets()->get($presetCode);

        if (
            $preset === null ||
            $scale < 1 ||
            $scale > $presetConfig->getMaxScale()
        ) {
            return false;
        }

        $this->width = $preset->getWidth() * $scale;
        $this->height = $preset->getHeight() * $scale;

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

        $this->resizeMode = ImageResizeMode::Original;
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
            'c' => $this->applyCanvasColor($value),
            'e' => $this->applyMissing($value),
            'z' => $this->applyResizeMode($value),
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

        $qualityConfig = $this->config->getQuality();
        $quality = (int) $value;

        if ($quality < $qualityConfig->getMinQuality()) {
            $quality = $qualityConfig->getMinQuality();
        }

        if ($quality > $qualityConfig->getMaxQuality()) {
            $quality = $qualityConfig->getMaxQuality();
        }

        $this->quality = $quality;
    }

    private function applyCanvasColor(string $value): void
    {
        if (!ctype_xdigit($value) || mb_strlen($value) !== 6) {
            return;
        }

        $this->canvasColor = $value;
    }

    private function applyMissing(string $value): void
    {
        if ($value !== '0' && $value !== '1') {
            return;
        }

        $this->missing = $value === '1';
    }

    private function applyResizeMode(string $value): void
    {
        if (!ctype_digit($value)) {
            return;
        }

        $resizeMode = ImageResizeMode::tryFrom((int) $value);

        if ($resizeMode === null || !$resizeMode->isAllowedInUrl()) {
            return;
        }

        $this->resizeMode = $resizeMode;
    }

    /**
     * Normalizes dimensions according to configured limits.
     *
     * Original mode intentionally bypasses limits and size fallback.
     */
    private function applyLimits(): void
    {
        if ($this->resizeMode === ImageResizeMode::Original) {
            return;
        }

        $dimensions = $this->config->getDimensions();

        if ($this->width === null && $this->height === null) {
            $this->width = $dimensions->getMinWidth();
            $this->height = $dimensions->getMinHeight();

            return;
        }

        if ($this->width !== null) {
            if ($this->width < $dimensions->getMinWidth()) {
                $this->width = $dimensions->getMinWidth();
            }

            if ($this->width > $dimensions->getMaxWidth()) {
                $this->width = $dimensions->getMaxWidth();
            }
        }

        if ($this->height !== null) {
            if ($this->height < $dimensions->getMinHeight()) {
                $this->height = $dimensions->getMinHeight();
            }

            if ($this->height > $dimensions->getMaxHeight()) {
                $this->height = $dimensions->getMaxHeight();
            }
        }
    }

    public function toDTO(): ImageOptionsDTO
    {
        return new ImageOptionsDTO(
            width: $this->width,
            height: $this->height,
            resizeMode: $this->resizeMode,
            canvasColor: $this->canvasColor,
            quality: $this->quality,
            missing: $this->missing,
        );
    }
}
