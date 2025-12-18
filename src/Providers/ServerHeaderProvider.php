<?php declare(strict_types=1);

namespace Lemonade\Image\Providers;

/**
 * ServerHeaderProvider
 *
 * Pomocná třída pro centralizované a bezpečné nastavování HTTP hlaviček
 * v rámci Lemonade Image Component. Odděluje veškerou logiku kolem hlaviček
 * od samotného procesu generování obrázků (ImageProvider).
 *
 * Hlavní odpovědnosti:
 * - nastavení cache hlaviček (Expires, Cache-Control, Connection)
 * - nastavení Content-Type a Content-Length
 * - jednotné odesílání Last-Modified
 * - podpora 304 Not Modified pomocí vlastních metod
 * - přidává interní diagnostický header: "X-Component: Lemonade Image"
 *
 * Třída nepracuje přímo s výstupem obrázků – pouze formuje výstupní hlavičky.
 *
 * @package     Lemonade Framework
 * @subpackage  Image\Providers
 * @category    Server
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
 * @see         ImageProvider, ServerProvider
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
