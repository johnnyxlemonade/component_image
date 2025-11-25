<?php declare(strict_types=1);

namespace Lemonade\Image;

use function json_encode;
use function md5;

/**
 * ImageOptionsDTO
 *
 * Immutable objekt s finalní sadou parametrů pro generování obrázků.
 * Data v této třídě jsou již validovaná a normalizovaná parserem
 * (`ImageOptionsParser`) a používají se jako jediný zdroj pravdy pro:
 * - `ImageProvider` (výpočty resize/crop/canvas)
 * - `AppImage` řídicí logiku
 * - cache hashing (`getHash()`)
 *
 * Klíčové vlastnosti:
 * - immutable (pouze readonly + with* klonovací metody)
 * - ukládá šířku/výšku (null = nezadáno)
 * - crop mód (0–3)
 * - barvu pozadí (hex)
 * - kvalitu (1–100)
 * - příznak chybějícího souboru
 *
 * DTO neprovádí žádnou validaci ani logiku – pouze nese data.
 *
 * @package     Lemonade Framework
 * @subpackage  Image
 * @category    DTO
 * @author      Honza Mudrák
 * @license     MIT
 * @since       1.0.0
 * @see         ImageOptionsParser
 * @see         ImageProvider
 * @see         AppImage
 */
final class ImageOptionsDTO
{
    private readonly ?int $width;
    private readonly ?int $height;
    private readonly int $crop;
    private readonly string $canvasColor;
    private readonly int $quality;
    private readonly bool $missing;

    public function __construct(
        ?int $width,
        ?int $height,
        int $crop,
        string $canvasColor,
        int $quality,
        bool $missing,
    ) {
        $this->width = $width;
        $this->height = $height;
        $this->crop = $crop;
        $this->canvasColor = $canvasColor;
        $this->quality = $quality;
        $this->missing = $missing;
    }

    public function withWidth(?int $width): self
    {
        return new self(
            $width,
            $this->height,
            $this->crop,
            $this->canvasColor,
            $this->quality,
            $this->missing
        );
    }

    public function withHeight(?int $height): self
    {
        return new self(
            $this->width,
            $height,
            $this->crop,
            $this->canvasColor,
            $this->quality,
            $this->missing
        );
    }

    public function withCrop(int $crop): self
    {
        return new self(
            width: $this->width,
            height: $this->height,
            crop: $crop,
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
            crop: $this->crop,
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
            crop: $this->crop,
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
            crop: $this->crop,
            canvasColor: $this->canvasColor,
            quality: $this->quality,
            missing: $missing,
        );
    }

    public function getWidth(): ?int { return $this->width; }
    public function getHeight(): ?int { return $this->height; }
    public function getCrop(): int { return $this->crop; }
    public function getCanvasColor(): string { return $this->canvasColor; }
    public function getQuality(): int { return $this->quality; }
    public function isMissing(): bool { return $this->missing; }
    public function getHash(): string { return $this->toHash(); }

    public function isMissingAllSize(): bool
    {
        return $this->width === null && $this->height === null;
    }

    public function toHash(): string
    {
        return md5(json_encode([
            'w' => $this->width,
            'h' => $this->height,
            'c' => $this->canvasColor,
            'e' => $this->missing,
            'z' => $this->crop,
            'q' => $this->quality,
        ]));
    }
}
