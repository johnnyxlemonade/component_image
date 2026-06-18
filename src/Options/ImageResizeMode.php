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

    public function isAllowedInUrl(): bool
    {
        return match ($this) {
            self::Original => false,
            default => true,
        };
    }
}
