<?php

declare(strict_types=1);

namespace Lemonade\Image\Providers;

use Lemonade\Image\Interfaces\ToArrayInterface;

/**
 * ColorProvider
 *
 * Value objekt reprezentující RGB barvu.
 * Slouží jako bezpečný mezikrok mezi vstupními hodnotami
 * (hex, čísla) a GD funkcemi.
 *
 * - zajišťuje rozsah 0–255
 * - neposkytuje žádnou logiku renderování
 * - slouží pouze jako datový kontejner
 *
 * @package     Lemonade Framework
 * @subpackage  Image\Providers
 * @category    Providers
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
 */
final class ColorProvider implements ToArrayInterface
{
    public function __construct(
        private readonly int $red,
        private readonly int $green,
        private readonly int $blue,
    ) {}

    /**
     * Vrátí RGB hodnoty ve formátu pro GD (0–255).
     *
     * @return array{red: int, green: int, blue: int}
     */
    public function toArray(): array
    {
        return [
            'red'   => self::clamp($this->red),
            'green' => self::clamp($this->green),
            'blue'  => self::clamp($this->blue),
        ];
    }

    /**
     * Vytvoří barvu z hex zápisu (#RRGGBB nebo RRGGBB).
     */
    public static function hexRgb(string $hex): self
    {
        $hex = ltrim(trim($hex), '#');

        if (preg_match('/^[0-9a-fA-F]{6}$/', $hex) !== 1) {
            return new self(0, 0, 0);
        }

        return new self(
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        );
    }

    /**
     * Omezí hodnotu do rozsahu 0–255.
     */
    private static function clamp(int $value): int
    {
        return max(0, min(255, $value));
    }
}
