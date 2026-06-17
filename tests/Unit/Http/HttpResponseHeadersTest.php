<?php

declare(strict_types=1);

namespace Lemonade\Image\Tests\Unit\Http;

use Lemonade\Image\Http\HttpResponseHeaders;
use PHPUnit\Framework\TestCase;

use function xdebug_get_headers;

final class HttpResponseHeadersTest extends TestCase
{
    /**
     * @requires function xdebug_get_headers
     * @runInSeparateProcess
     */
    public function testSetsCacheHeaders(): void
    {
        HttpResponseHeaders::setCacheHeaders(
            lifetime: 3600,
            expires: 'Wed, 17 Jun 2026 10:00:00 GMT',
        );

        self::assertContains(
            'Accept-Ranges: none',
            xdebug_get_headers(),
        );
        self::assertContains(
            'X-Component: Lemonade Image',
            xdebug_get_headers(),
        );
        self::assertContains(
            'Cache-Control: max-age=3600, no-transform',
            xdebug_get_headers(),
        );
        self::assertContains(
            'Expires: Wed, 17 Jun 2026 10:00:00 GMT',
            xdebug_get_headers(),
        );
        self::assertContains(
            'Connection: close',
            xdebug_get_headers(),
        );
    }

    /**
     * @requires function xdebug_get_headers
     * @runInSeparateProcess
     */
    public function testSetsContentTypeWhenMimeIsProvided(): void
    {
        HttpResponseHeaders::setContentType(
            mime: 'image/png',
        );

        self::assertContains(
            'Content-Type: image/png',
            xdebug_get_headers(),
        );
    }

    /**
     * @requires function xdebug_get_headers
     * @runInSeparateProcess
     */
    public function testDoesNotSetContentTypeWhenMimeIsEmpty(): void
    {
        HttpResponseHeaders::setContentType(
            mime: '',
        );

        self::assertNotContains(
            'Content-Type: ',
            xdebug_get_headers(),
        );
    }

    /**
     * @requires function xdebug_get_headers
     * @runInSeparateProcess
     */
    public function testSetsContentLengthWhenSizeIsPositive(): void
    {
        HttpResponseHeaders::setContentLength(
            size: 123,
        );

        self::assertContains(
            'Content-Length: 123',
            xdebug_get_headers(),
        );
    }

    /**
     * @requires function xdebug_get_headers
     * @runInSeparateProcess
     */
    public function testDoesNotSetContentLengthWhenSizeIsZero(): void
    {
        HttpResponseHeaders::setContentLength(
            size: 0,
        );

        self::assertSame(
            [],
            xdebug_get_headers(),
        );
    }

    /**
     * @requires function xdebug_get_headers
     * @runInSeparateProcess
     */
    public function testSetsNotModifiedHeaders(): void
    {
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';

        HttpResponseHeaders::setNotModified();

        self::assertContains(
            'Connection: close',
            xdebug_get_headers(),
        );
        self::assertContains(
            'X-Component: Lemonade Image',
            xdebug_get_headers(),
        );
    }

    /**
     * @requires function xdebug_get_headers
     * @runInSeparateProcess
     */
    public function testSetsLastModifiedHeader(): void
    {
        HttpResponseHeaders::setLastModified(
            timestamp: 1781690400,
            code: 200,
        );

        self::assertContains(
            'Last-Modified: Wed, 17 Jun 2026 10:00:00 GMT',
            xdebug_get_headers(),
        );
    }
}
