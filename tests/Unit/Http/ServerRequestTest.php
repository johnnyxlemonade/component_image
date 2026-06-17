<?php

declare(strict_types=1);

namespace Lemonade\Image\Tests\Unit\Http;

use Lemonade\Image\Http\ServerRequest;
use PHPUnit\Framework\TestCase;

final class ServerRequestTest extends TestCase
{
    /** @var array<array-key, mixed> */
    private array $serverBackup = [];

    protected function setUp(): void
    {
        $this->serverBackup = $_SERVER;
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->serverBackup;
    }

    public function testReturnsStringServerValue(): void
    {
        $_SERVER['HTTP_ACCEPT'] = 'image/png';

        self::assertSame(
            'image/png',
            ServerRequest::get(
                key: 'HTTP_ACCEPT',
            ),
        );
    }

    public function testReturnsDefaultWhenKeyIsMissing(): void
    {
        unset($_SERVER['HTTP_ACCEPT']);

        self::assertSame(
            '',
            ServerRequest::get(
                key: 'HTTP_ACCEPT',
            ),
        );
        self::assertSame(
            'fallback',
            ServerRequest::get(
                key: 'HTTP_ACCEPT',
                default: 'fallback',
            ),
        );
    }

    public function testReturnsDefaultWhenValueIsNotString(): void
    {
        $_SERVER['HTTP_ACCEPT'] = ['image/webp'];

        self::assertSame(
            '',
            ServerRequest::get(
                key: 'HTTP_ACCEPT',
            ),
        );
        self::assertSame(
            'fallback',
            ServerRequest::get(
                key: 'HTTP_ACCEPT',
                default: 'fallback',
            ),
        );
    }

    public function testHasReturnsTrueForNonEmptyStringValue(): void
    {
        $_SERVER['HTTP_IF_MODIFIED_SINCE'] = 'Wed, 17 Jun 2026 10:00:00 GMT';

        self::assertTrue(
            ServerRequest::has(
                key: 'HTTP_IF_MODIFIED_SINCE',
            ),
        );
    }

    public function testHasReturnsFalseWhenKeyIsMissing(): void
    {
        unset($_SERVER['HTTP_IF_MODIFIED_SINCE']);

        self::assertFalse(
            ServerRequest::has(
                key: 'HTTP_IF_MODIFIED_SINCE',
            ),
        );
    }

    public function testHasReturnsFalseForEmptyString(): void
    {
        $_SERVER['HTTP_IF_MODIFIED_SINCE'] = '';

        self::assertFalse(
            ServerRequest::has(
                key: 'HTTP_IF_MODIFIED_SINCE',
            ),
        );
    }

    public function testHasReturnsFalseForNonStringValue(): void
    {
        $_SERVER['HTTP_IF_MODIFIED_SINCE'] = 123;

        self::assertFalse(
            ServerRequest::has(
                key: 'HTTP_IF_MODIFIED_SINCE',
            ),
        );
    }
}
