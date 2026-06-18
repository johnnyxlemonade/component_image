<?php

declare(strict_types=1);

namespace Lemonade\Image\Options;

use function json_encode;
use function md5;

/**
 * Carries normalized image processing options.
 *
 * Provides immutable option values for sizing, cropping, quality, canvas
 * color and fallback behavior used by image generation services.
 *
 * @package     Lemonade
 * @subpackage  Image\Options
 * @category    DTO
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
 */
final class ImageOptionsDTO
{
    private readonly ?int $width;
    private readonly ?int $height;
    private readonly ImageResizeMode $resizeMode;
    private readonly string $canvasColor;
    private readonly int $quality;
    private readonly bool $missing;

    public function __construct(
        ?int $width,
        ?int $height,
        ImageResizeMode $resizeMode,
        string $canvasColor,
        int $quality,
        bool $missing,
    ) {
        $this->width = $width;
        $this->height = $height;
        $this->resizeMode = $resizeMode;
        $this->canvasColor = $canvasColor;
        $this->quality = $quality;
        $this->missing = $missing;
    }

    public function withWidth(?int $width): self
    {
        return new self(
            width: $width,
            height: $this->height,
            resizeMode: $this->resizeMode,
            canvasColor: $this->canvasColor,
            quality: $this->quality,
            missing: $this->missing,
        );
    }

    public function withHeight(?int $height): self
    {
        return new self(
            width: $this->width,
            height: $height,
            resizeMode: $this->resizeMode,
            canvasColor: $this->canvasColor,
            quality: $this->quality,
            missing: $this->missing,
        );
    }

    public function withResizeMode(ImageResizeMode $resizeMode): self
    {
        return new self(
            width: $this->width,
            height: $this->height,
            resizeMode: $resizeMode,
            canvasColor: $this->canvasColor,
            quality: $this->quality,
            missing: $this->missing,
        );
    }

    public function withCanvasColor(string $color): self
    {
        return new self(
            width: $this->width,
            height: $this->height,
            resizeMode: $this->resizeMode,
            canvasColor: $color,
            quality: $this->quality,
            missing: $this->missing,
        );
    }

    public function withQuality(int $quality): self
    {
        return new self(
            width: $this->width,
            height: $this->height,
            resizeMode: $this->resizeMode,
            canvasColor: $this->canvasColor,
            quality: $quality,
            missing: $this->missing,
        );
    }

    public function withMissing(bool $missing): self
    {
        return new self(
            width: $this->width,
            height: $this->height,
            resizeMode: $this->resizeMode,
            canvasColor: $this->canvasColor,
            quality: $this->quality,
            missing: $missing,
        );
    }

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    public function getResizeMode(): ImageResizeMode
    {
        return $this->resizeMode;
    }

    public function getCanvasColor(): string
    {
        return $this->canvasColor;
    }

    public function getQuality(): int
    {
        return $this->quality;
    }

    public function isMissing(): bool
    {
        return $this->missing;
    }

    public function getHash(): string
    {
        return $this->toHash();
    }

    public function isMissingAllSize(): bool
    {
        return $this->width === null && $this->height === null;
    }

    public function toHash(): string
    {
        $json = json_encode([
            'w' => $this->width,
            'h' => $this->height,
            'c' => $this->canvasColor,
            'e' => $this->missing,
            'z' => $this->resizeMode->value,
            'q' => $this->quality,
        ]);

        if ($json !== false) {
            return md5($json);
        }

        return md5($this->buildFallbackHashPayload());
    }

    private function buildFallbackHashPayload(): string
    {
        return sprintf(
            'w:%s|h:%s|c:%s|e:%s|z:%d|q:%d',
            $this->width === null ? 'null' : (string) $this->width,
            $this->height === null ? 'null' : (string) $this->height,
            $this->canvasColor,
            $this->missing ? '1' : '0',
            $this->resizeMode->value,
            $this->quality,
        );
    }

}
