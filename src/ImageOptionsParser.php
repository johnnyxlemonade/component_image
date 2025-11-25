<?php declare(strict_types=1);

namespace Lemonade\Image;

use function explode;
use function mb_substr;
use function mb_strlen;
use function in_array;
use function ctype_digit;
use function ctype_xdigit;
use function json_encode;
use function md5;

/**
 * ImageOptionsParser
 *
 * Parser a normalizační vrstva pro parametry obrázků přicházející z URL.
 * Odpovídá za dekódování řetězce ve formátu:
 *
 *     w600-h400-z1-cfff-e1
 *
 * a převod na plně validovaná a omezena data (min/max limity).
 *
 * Klíčové vlastnosti:
 * - Rozpoznání šířky/výšky (`w`,`h`) a fallback na minimální rozměry
 * - Validace kvality, barvy plátna, crop módu a „missing“ příznaku
 * - Ochrana proti extrémním hodnotám pomocí `minWidth/minHeight/maxWidth/maxHeight`
 * - Produkuje immutable objekt `ImageOptionsDTO`, který je jediným zdrojem pravdy
 *   pro generátor obrázků (`ImageProvider`)
 *
 * Parser neprovádí žádné výpočty rozměrů ani resize — pouze připravuje
 * konzistentní parametry pro interní generátor.
 *
 * @package     Lemonade Framework
 * @subpackage  Image
 * @category    Parser
 * @author      Honza Mudrák
 * @license     MIT
 * @since       1.0.0
 * @see         ImageOptionsDTO
 * @see         ImageProvider
 * @see         AppImage
 */
final class ImageOptionsParser
{
    private ?int $width = null;
    private ?int $height = null;
    private int $crop = 0;
    private string $canvas = 'ffffff';
    private int $quality = 72;
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
        $this->minWidth  = $minWidth;
        $this->minHeight = $minHeight;
        $this->maxWidth  = $maxWidth;
        $this->maxHeight = $maxHeight;

        $this->parse($args);
        $this->applyLimits();
    }

    /**
     * Rozparsuje řetězec parametrů (w,h,q,c,e,z) do interních vlastností.
     */
    private function parse(?string $args): void
    {
        if ((string) $args === '') {
            return;
        }

        foreach (explode('-', $args) as $item) {
            $key = mb_substr($item, 0, 1);
            $val = mb_substr($item, 1);

            match ($key) {
                'w' => $this->width = (ctype_digit($val) && (int)$val > 0) ? (int)$val : null,
                'h' => $this->height = (ctype_digit($val) && (int)$val > 0) ? (int)$val : null,
                'q' => $this->quality = ctype_digit($val) ? (int) $val : 72,
                'c' => $this->canvas = (ctype_xdigit($val) && mb_strlen($val) === 6) ? $val : 'ffffff',
                'e' => $this->missing = in_array((int) $val, [0, 1], true) ? $val === '1' : true,
                'z' => $this->crop = in_array((int) $val, [0, 1, 2, 3], true) ? (int) $val : 0,
                default => null,
            };
        }
    }

    /**
     * Normalizuje rozměry podle limitů
     */
    private function applyLimits(): void
    {
        // Pokud není zadáno nic → fallback min width/height
        if ($this->width === null && $this->height === null) {
            $this->width  = $this->minWidth;
            $this->height = $this->minHeight;
            return;
        }

        // Normální limitování
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
            crop: $this->crop,
            canvasColor: $this->canvas,
            quality: $this->quality,
            missing: $this->missing,
        );
    }

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function setWidth(int $width): void
    {
        $this->width = $width;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    public function setHeight(int $height): void
    {
        $this->height = $height;
    }

    public function getCanvas(): string
    {
        return $this->canvas;
    }

    public function getQuality(): int
    {
        return $this->quality;
    }

    public function getCrop(): int
    {
        return $this->crop;
    }

    public function isMissing(): bool
    {
        return $this->missing;
    }

    public function isMissingAllSize(): bool
    {
        return $this->width === null && $this->height === null;
    }

    public function getHash(): string
    {
        return md5(json_encode([
            'w' => $this->width,
            'h' => $this->height,
            'c' => $this->canvas,
            'e' => $this->missing,
            'z' => $this->crop,
            'q' => $this->quality,
        ]));
    }
}
