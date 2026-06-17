<?php

declare(strict_types=1);

namespace Lemonade\Image\Detection;

use Lemonade\Image\Http\ServerRequest;

use function function_exists;
use function str_contains;

/**
 * Detects WebP support for image responses.
 *
 * Combines server-side GD capability and browser request headers to decide
 * whether generated and cached images can be emitted as WebP.
 *
 * @package     Lemonade
 * @subpackage  Image\Detection
 * @category    Detector
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
 */
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
        return ServerRequest::has('HTTP_ACCEPT')
            && str_contains(
                ServerRequest::get('HTTP_ACCEPT'),
                'image/webp',
            );
    }
}
