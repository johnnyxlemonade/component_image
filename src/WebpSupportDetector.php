<?php

declare(strict_types=1);

namespace Lemonade\Image;

use Lemonade\Image\Providers\ServerProvider;

use function function_exists;
use function str_contains;

final class WebpSupportDetector
{
    public static function hasSupport(): bool
    {
        return self::hasServerSupport()
            && self::hasBrowserSupport();
    }

    private static function hasServerSupport(): bool
    {
        return function_exists('imagewebp');
    }

    private static function hasBrowserSupport(): bool
    {
        return ServerProvider::has('HTTP_ACCEPT')
            && str_contains(
                ServerProvider::get('HTTP_ACCEPT'),
                'image/webp',
            );
    }
}
