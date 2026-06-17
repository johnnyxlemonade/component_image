<?php

declare(strict_types=1);

namespace Lemonade\Image\Providers;

/**
 * Detects WEBP support and controls WEBP response behavior.
 *
 * @package     Lemonade
 * @subpackage  Image\Providers
 * @category    Provider
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
 */
final class WebpProvider
{
    public static function hasSupport(): bool
    {
        $accept = ServerProvider::get('HTTP_ACCEPT');
        $agent = ServerProvider::get('HTTP_USER_AGENT');

        return str_contains($accept, 'image/webp')
            || str_contains($agent, ' Chrome/');
    }
}
