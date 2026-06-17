<?php

declare(strict_types=1);

namespace Lemonade\Image\Tests\Unit\Detection;

use Lemonade\Image\Detection\WebpSupportDetector;
use PHPUnit\Framework\TestCase;

final class WebpSupportDetectorTest extends TestCase
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

    public function testReturnsFalseWhenAcceptHeaderIsMissing(): void
    {
        unset($_SERVER['HTTP_ACCEPT']);

        self::assertFalse(WebpSupportDetector::hasSupport());
    }

    public function testReturnsFalseWhenAcceptHeaderDoesNotContainWebp(): void
    {
        $_SERVER['HTTP_ACCEPT'] = 'image/png,image/jpeg,*/*';

        self::assertFalse(WebpSupportDetector::hasSupport());
    }

    public function testReturnsFalseWhenAcceptHeaderIsNotString(): void
    {
        $_SERVER['HTTP_ACCEPT'] = 123;

        self::assertFalse(WebpSupportDetector::hasSupport());
    }

    public function testReturnsExpectedValueWhenAcceptHeaderContainsWebp(): void
    {
        $_SERVER['HTTP_ACCEPT'] = 'image/avif,image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8';

        self::assertSame(
            function_exists('imagewebp'),
            WebpSupportDetector::hasSupport(),
        );
    }
}
