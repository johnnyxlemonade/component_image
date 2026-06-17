<?php

declare(strict_types=1);

namespace Lemonade\Image;

use DateTimeImmutable;
use Lemonade\Image\Exceptions\Image\ImageRenderException;

use function strlen;
use function strtotime;
use function time;

/**
 * Emits HTTP image responses.
 *
 * This class is responsible only for headers and response body output.
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

        $ifModifiedSince = ServerRequest::get('HTTP_IF_MODIFIED_SINCE');
        $parsedClientTime = $ifModifiedSince !== ''
            ? strtotime($ifModifiedSince)
            : false;

        $clientTime = $parsedClientTime === false
            ? 0
            : $parsedClientTime;

        $serverTime = (int) ServerRequest::get(
            'REQUEST_TIME',
            (string) time(),
        );

        if ($clientTime > ($serverTime - $lifetime)) {
            HttpResponseHeaders::setLastModified($clientTime, 304);

            return;
        }

        HttpResponseHeaders::setLastModified($serverTime, 200);
    }

    public function sendNotModified(): void
    {
        HttpResponseHeaders::setNotModified();
    }
}
