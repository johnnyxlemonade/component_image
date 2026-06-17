<?php

declare(strict_types=1);

namespace Lemonade\Image\Http;

use function gmdate;
use function header;

/**
 * Sends HTTP headers used by image responses and cache handling.
 *
 * Centralizes response header output for content metadata, cache lifetime,
 * last-modified handling and 304 Not Modified responses.
 *
 * @package     Lemonade
 * @subpackage  Image\Http
 * @category    HTTP
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
 */
final class HttpResponseHeaders
{
    private function __construct() {}

    public static function setCacheHeaders(int $lifetime, string $expires): void
    {
        header('Accept-Ranges: none');
        header('X-Component: Lemonade Image');
        header("Cache-Control: max-age={$lifetime}, no-transform");
        header("Expires: {$expires}");
        header('Connection: close');
    }

    public static function setContentType(?string $mime): void
    {
        if ($mime === null || $mime === '') {
            return;
        }

        header("Content-Type: {$mime}");
    }

    public static function setContentLength(int $size): void
    {
        if ($size <= 0) {
            return;
        }

        header("Content-Length: {$size}");
    }

    public static function setNotModified(): void
    {
        $protocol = ServerRequest::get(
            key: 'SERVER_PROTOCOL',
            default: 'HTTP/1.1',
        );

        header('Connection: close');
        header('X-Component: Lemonade Image');
        header("{$protocol} 304 Not Modified");
    }

    public static function setLastModified(int $timestamp, int $code): void
    {
        header(
            header: 'Last-Modified: ' . gmdate(
                format: 'D, d M Y H:i:s',
                timestamp: $timestamp,
            ) . ' GMT',
            replace: true,
            response_code: $code,
        );
    }
}
