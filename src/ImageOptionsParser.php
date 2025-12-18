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
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
 * @see         ImageOptionsDTO
 * @see         ImageProvider
 * @see         AppImage
 */
final class ImageOptionsParser
{
    /**
     * Pevně definované velikostní presety (UI / obsah).
     * Hodnoty jsou v px a reprezentují 1× variantu.
     */
    private const SIZE_PRESETS = [
        'xss' => [32, 32],
        'xs'  => [48, 48],
        'sm'  => [96, 96],
        'md'  => [160, 160],
        'lg'  => [320, 320],
        'xl'  => [640, 640],
    ];
    private const MAX_PRESET_SCALE = 3; // ochrana proti extrémním @N (DoS)

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
     * Hlavní parser parametrů.
     * Pořadí je významné – poslední validní hodnota vyhrává.
     *
     * Priorita:
     * 1) preset / preset@N
     * 2) original
     * 3) key-value (w,h,q,c,e,z)
     */
    private function parse(?string $args): void
    {
        if ((string) $args === '') {
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
     * Zpracuje size preset (např. md, md2).
     * Preset je syntaktický cukr nad width/height a po parsování
     * se již nijak nerozlišuje od explicitního w/h.
     *
     * Číselný suffix reprezentuje scale (např. md2 = 2× md).
     * Scale je omezen kvůli ochraně proti extrémním hodnotám (DoS).
     */
    private function parsePreset(string $item): bool
    {
        // md, md2, md3
        if (!preg_match('~^([a-z]+)(\d+)?$~', $item, $m)) {
            return false;
        }

        $preset = $m[1];
        $scale  = isset($m[2]) ? (int) $m[2] : 1;

        if (
            !isset(self::SIZE_PRESETS[$preset]) ||
            $scale < 1 ||
            $scale > self::MAX_PRESET_SCALE
        ) {
            return false;
        }

        [$w, $h] = self::SIZE_PRESETS[$preset];

        $this->width  = $w * $scale;
        $this->height = $h * $scale;

        return true;
    }

    /**
     * ORIGINAL mód – zachová původní rozměry zdroje.
     * Resetuje width/height a potlačuje fallback v applyLimits().
     */
    private function parseOriginal(string $item): bool
    {
        if ($item !== 'original') {
            return false;
        }

        $this->crop   = -1;
        $this->width  = null;
        $this->height = null;

        return true;
    }

    /**
     * Zpracuje klasický key-value token (w,h,q,c,e,z).
     * Neznámé tokeny jsou tiše ignorovány.
     */
    private function parseKeyValue(string $item): void
    {
        // key-value token (w,h,q,c,e,z)
        $key = mb_substr($item, 0, 1);
        $val = mb_substr($item, 1);

        match ($key) {
            'w' => $this->width = (ctype_digit($val) && (int)$val > 0) ? (int)$val : null,
            'h' => $this->height = (ctype_digit($val) && (int)$val > 0) ? (int)$val : null,
            'q' => $this->quality = ctype_digit($val) ? (int) $val : 72,
            'c' => $this->canvas = (ctype_xdigit($val) && mb_strlen($val) === 6) ? $val : 'ffffff',
            'e' => $this->missing = in_array((int) $val, [0, 1], true) ? $val === '1' : true,
            'z' => $this->crop = in_array((int) $val, [0, 1, 2, 3], true) ? (int) $val : 0,
            default => null, // neznámý nebo nepodporovaný token
        };
    }

    /**
     * Normalizuje rozměry podle limitů.
     * ORIGINAL mód limity i fallback záměrně obchází.
     */
    private function applyLimits(): void
    {
        // ORIGINAL
        if ($this->crop === -1) {
            return;
        }

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
