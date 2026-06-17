<?php

declare(strict_types=1);

namespace Lemonade\Image\Providers;

/**
 * WebpProvider
 *
 * Detekuje podporu WebP na straně klienta podle hlaviček prohlížeče.
 *
 * @package     Lemonade Framework
 * @subpackage  Image\Providers
 * @category    Image
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
 */
final class WebpProvider
{
    /**
     * Zjistí podporu WebP na straně klienta.
     *
     * Podmínky:
     * - HTTP_ACCEPT obsahuje "image/webp"
     * - nebo User-Agent obsahuje " Chrome/"
     */
    public static function hasSupport(): bool
    {
        $accept = ServerProvider::get('HTTP_ACCEPT');
        $agent = ServerProvider::get('HTTP_USER_AGENT');

        return str_contains($accept, 'image/webp')
            || str_contains($agent, ' Chrome/');
    }
}
