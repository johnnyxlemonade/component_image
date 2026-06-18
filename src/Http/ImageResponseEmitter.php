<?php

declare(strict_types=1);

namespace Lemonade\Image\Http;

use DateTimeImmutable;
use Lemonade\Image\Exceptions\Image\ImageRenderException;
use Lemonade\Image\Generator\AppGenerator;

use function fclose;
use function feof;
use function filesize;
use function flush;
use function fopen;
use function fread;
use function is_resource;
use function ob_flush;
use function ob_get_level;
use function strlen;
use function time;

/**
 * Emits HTTP image responses.
 *
 * Emits prepared image HTTP response DTOs including cache headers,
 * content type and content length metadata.
 *
 * @package     Lemonade
 * @subpackage  Image\Http
 * @category    HTTP
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
 */
final class ImageResponseEmitter
{
    private const CACHE_LIFETIME = 31536000;

    /**
     * @var array<int, string>
     */
    private const MIME_TYPES = [
        AppGenerator::JPEG => 'image/jpeg',
        AppGenerator::PNG => 'image/png',
        AppGenerator::GIF => 'image/gif',
        AppGenerator::WEBP => 'image/webp',
    ];

    public function emit(ImageHttpResponse $response): never
    {
        if ($response->isNotModified()) {
            HttpResponseHeaders::setNotModified();

            exit;
        }

        if ($response->isBinary()) {
            $this->emitBinaryResponse(
                response: $response,
            );
        }

        if ($response->isFile()) {
            $this->emitFileResponse(
                response: $response,
            );
        }

        throw ImageRenderException::failed();
    }

    private function getRequestTime(): int
    {
        return (int) ServerRequest::get(
            key: 'REQUEST_TIME',
            default: (string) time(),
        );
    }

    private function emitBinaryResponse(ImageHttpResponse $response): never
    {
        $content = $response->getContent();

        if ($content === null) {
            throw ImageRenderException::failed();
        }

        $this->emitHeaders(
            type: $response->getType(),
            size: strlen($content),
            lastModified: $response->getLastModified(),
        );

        echo $content;
        exit;
    }

    private function emitFileResponse(ImageHttpResponse $response): never
    {
        $file = $response->getFile();

        if ($file === null) {
            throw ImageRenderException::failed();
        }

        $size = (int) @filesize($file);

        $this->emitHeaders(
            type: $response->getType(),
            size: $size,
            lastModified: $response->getLastModified(),
        );

        $handle = @fopen($file, 'rb');

        if (!is_resource($handle)) {
            throw ImageRenderException::failed();
        }

        while (!feof($handle)) {
            $chunk = fread($handle, 8192);

            if ($chunk === false) {
                fclose($handle);

                throw ImageRenderException::failed();
            }

            echo $chunk;

            if (ob_get_level() > 0) {
                ob_flush();
            }

            flush();
        }

        fclose($handle);
        exit;
    }

    private function emitHeaders(
        ?int $type = null,
        int $size = 0,
        ?int $lastModified = null,
    ): void {
        $lifetime = self::CACHE_LIFETIME;

        $now = new DateTimeImmutable();
        $expiresAt = $now
                ->modify("+{$lifetime} seconds")
                ->format('D, d M Y H:i:s') . ' GMT';

        $mime = $type !== null && isset(self::MIME_TYPES[$type])
            ? self::MIME_TYPES[$type]
            : null;

        HttpResponseHeaders::setContentType($mime);
        HttpResponseHeaders::setCacheHeaders($lifetime, $expiresAt);

        if ($size > 0) {
            HttpResponseHeaders::setContentLength($size);
        }

        HttpResponseHeaders::setLastModified(
            timestamp: $lastModified ?? $this->getRequestTime(),
            code: 200,
        );
    }
}
