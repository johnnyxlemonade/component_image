<?php

declare(strict_types=1);

namespace Lemonade\Image\Options;

enum ImageResizeMode: int
{
    case Original = -1;
    case Shrink = 0;
    case FitWithCanvas = 1;
    case Exact = 2;
    case Fit = 3;
    case FitWithBiggerCanvas = 4;
    case FitWithMaxCanvas = 5;

    public static function fromLegacyCrop(int $crop): self
    {
        return self::tryFrom($crop) ?? self::Shrink;
    }

    /**
     * @return list<int>
     */
    public static function legacyCropValues(): array
    {
        return [
            self::Shrink->value,
            self::FitWithCanvas->value,
            self::Exact->value,
            self::Fit->value,
            self::FitWithBiggerCanvas->value,
            self::FitWithMaxCanvas->value,
        ];
    }
}
