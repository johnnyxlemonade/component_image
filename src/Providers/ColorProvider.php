<?php declare(strict_types=1);

namespace Lemonade\Image\Providers;

use Lemonade\Image\Interfaces\ToArrayInterface;
use Lemonade\Image\Traits\StaticTrait;

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
    use StaticTrait;

    public function __construct(
        private int $red,
        private int $green,
        private int $blue,
    ) {}

    /**
     * Vrátí RGB hodnoty ve formátu pro GD (0–255).
     *
     * @return array<string, int>
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
        [$r, $g, $b] = sscanf(ltrim($hex, '#'), '%02x%02x%02x');
        return new self($r, $g, $b);
    }

    /**
     * Omezí hodnotu do rozsahu 0–255.
     */
    private static function clamp(int $value): int
    {
        return max(0, min(255, $value));
    }
}
