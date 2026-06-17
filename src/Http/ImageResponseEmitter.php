<?php

declare(strict_types=1);

namespace Lemonade\Image\Http;

use DateTimeImmutable;
use Lemonade\Image\Exceptions\Image\ImageRenderException;

use Lemonade\Image\Generator\AppGenerator;
use Lemonade\Image\ImageResult;

use function strlen;
use function time;

/**
 * Emits HTTP image responses.
 *
 * Converts generated image results into binary HTTP responses including
 * cache headers, content type and content length metadata.
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
        AppGenerator::JPEG => 'image/jpg',
        AppGenerator::PNG => 'image/png',
        AppGenerator::GIF => 'image/gif',
        AppGenerator::WEBP => 'image/webp',
    ];

    public function sendResult(ImageResult $result): never
    {
        $this->sendImage(
            image: $result->getImage(),
            type: $result->getType(),
            quality: $result->getQuality(),
        );
    }

    public function sendImage(
        AppGenerator $image,
        int $type,
        int $quality,
    ): never {
        $content = $image->toString($type, $quality);

        if ($content === '') {
            throw ImageRenderException::failed();
        }

        $this->sendBinary(
            content: $content,
            type: $type,
        );
    }

    public function sendBinary(
        string $content,
        ?int $type = null,
    ): never {
        $this->sendHeader(
            type: $type,
            size: strlen($content),
        );

        echo $content;
        exit;
    }

    public function sendHeader(?int $type = null, int $size = 0): void
    {
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

        $serverTime = (int) ServerRequest::get(
            key: 'REQUEST_TIME',
            default: (string) time(),
        );

        HttpResponseHeaders::setLastModified(
            timestamp: $serverTime,
            code: 200,
        );
    }

    public function sendNotModified(): void
    {
        HttpResponseHeaders::setNotModified();
    }
}
