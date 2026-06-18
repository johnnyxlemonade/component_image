<?php

declare(strict_types=1);

namespace Lemonade\Image\Http;

/**
 * Carries an HTTP image response before it is emitted.
 *
 * Represents either binary content, a file response or a 304 Not Modified
 * response without performing any output side effects.
 *
 * @package     Lemonade
 * @subpackage  Image\Http
 * @category    DTO
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
 */
final class ImageHttpResponse
{
    private function __construct(
        private readonly int $statusCode,
        private readonly ?string $content,
        private readonly ?string $file,
        private readonly ?int $type,
        private readonly ?int $lastModified,
    ) {}

    public static function binary(
        string $content,
        ?int $type = null,
        ?int $lastModified = null,
    ): self {
        return new self(
            statusCode: 200,
            content: $content,
            file: null,
            type: $type,
            lastModified: $lastModified,
        );
    }

    public static function file(
        string $file,
        ?int $type = null,
        ?int $lastModified = null,
    ): self {
        return new self(
            statusCode: 200,
            content: null,
            file: $file,
            type: $type,
            lastModified: $lastModified,
        );
    }

    public static function notModified(): self
    {
        return new self(
            statusCode: 304,
            content: null,
            file: null,
            type: null,
            lastModified: null,
        );
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function getFile(): ?string
    {
        return $this->file;
    }

    public function getType(): ?int
    {
        return $this->type;
    }

    public function getLastModified(): ?int
    {
        return $this->lastModified;
    }

    public function isBinary(): bool
    {
        return $this->content !== null;
    }

    public function isFile(): bool
    {
        return $this->file !== null;
    }

    public function isNotModified(): bool
    {
        return $this->statusCode === 304;
    }
}
