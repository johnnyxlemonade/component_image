<?php

declare(strict_types=1);

namespace Lemonade\Image\Value;

use Lemonade\Image\Interfaces\ToArrayInterface;

use function hexdec;
use function ltrim;
use function max;
use function min;
use function preg_match;
use function substr;
use function trim;

/**
 * Represents an RGB color value and provides normalized GD-compatible output.
 */
final class RgbColor implements ToArrayInterface
{
    public function __construct(
        private readonly int $red,
        private readonly int $green,
        private readonly int $blue,
    ) {}

    /**
     * @return array{red: int, green: int, blue: int}
     */
    public function toArray(): array
    {
        return [
            'red' => self::clamp($this->red),
            'green' => self::clamp($this->green),
            'blue' => self::clamp($this->blue),
        ];
    }

    public static function fromHex(string $hex): self
    {
        $hex = ltrim(trim($hex), '#');

        if (preg_match('/^[0-9a-fA-F]{6}$/', $hex) !== 1) {
            return new self(0, 0, 0);
        }

        return new self(
            red: (int) hexdec(substr($hex, 0, 2)),
            green: (int) hexdec(substr($hex, 2, 2)),
            blue: (int) hexdec(substr($hex, 4, 2)),
        );
    }

    private static function clamp(int $value): int
    {
        return max(0, min(255, $value));
    }
}
