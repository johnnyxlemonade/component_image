<?php

declare(strict_types=1);

namespace Lemonade\Image\Providers;

/**
 * Sends HTTP headers used by image responses and cache handling.
 *
 * @package     Lemonade
 * @subpackage  Image\Providers
 * @category    Provider
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
 */
final class ServerHeaderProvider
{
    /**
     * Nastaví základní cache hlavičky pro binární výstup.
     */
    public static function setCacheHeaders(int $lifetime, string $expires): void
    {
        header("Accept-Ranges: none");
        header("X-Component: Lemonade Image");
        header("Cache-Control: max-age={$lifetime}, no-transform");
        header("Expires: {$expires}");
        header("Connection: close");
    }

    /**
     * Nastaví Content-Type, pokud je validní.
     */
    public static function setContentType(?string $mime): void
    {
        if ($mime !== null && $mime !== '') {
            header("Content-Type: {$mime}");
        }
    }

    /**
     * Nastaví Content-Length pokud je > 0.
     */
    public static function setContentLength(int $size): void
    {
        if ($size > 0) {
            header("Content-Length: {$size}");
        }
    }

    /**
     * Odeslání 304 Not Modified.
     * Obsahuje výchozí X-Component hlavičku.
     */
    public static function setNotModified(): void
    {
        $protocol = ServerProvider::get('SERVER_PROTOCOL', 'HTTP/1.1');

        header("Connection: close");
        header("X-Component: Lemonade Image");
        header("{$protocol} 304 Not Modified");
    }

    /**
     * Nastaví Last-Modified včetně správného HTTP kódu.
     */
    public static function setLastModified(int $timestamp, int $code): void
    {
        header(
            "Last-Modified: " . gmdate("D, d M Y H:i:s", $timestamp) . " GMT",
            true,
            $code
        );
    }
}
