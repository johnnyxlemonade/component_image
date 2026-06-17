<?php

declare(strict_types=1);

namespace Lemonade\Image\Tests\Unit\Utils;

use Lemonade\Image\Utils\PathHelper;
use PHPUnit\Framework\TestCase;

final class PathHelperTest extends TestCase
{
    public function testJoinsRelativePathParts(): void
    {
        self::assertSame(
            'storage/10/1',
            self::normalizePath(
                PathHelper::join(
                    'storage',
                    '10',
                    '1',
                ),
            ),
        );
    }

    public function testJoinsCurrentDirectoryPathParts(): void
    {
        self::assertSame(
            './storage/0/cache',
            self::normalizePath(
                PathHelper::join(
                    '.',
                    'storage',
                    '0',
                    'cache',
                ),
            ),
        );
    }

    public function testJoinsUnixLikeAbsolutePathParts(): void
    {
        self::assertSame(
            '/tmp/lemonade/files',
            self::normalizePath(
                PathHelper::join(
                    '/tmp/lemonade',
                    'files',
                ),
            ),
        );
    }

    public function testJoinsWindowsDrivePathParts(): void
    {
        self::assertSame(
            'C:/tmp/lemonade/files',
            self::normalizePath(
                PathHelper::join(
                    'C:\\tmp\\lemonade',
                    'files',
                ),
            ),
        );
    }

    public function testTrimsDuplicateSeparators(): void
    {
        self::assertSame(
            './storage/10/1/example.png',
            self::normalizePath(
                PathHelper::join(
                    './storage/',
                    '/10/',
                    '\\1\\',
                    'example.png',
                ),
            ),
        );
    }

    public function testBuildsFilePath(): void
    {
        self::assertSame(
            './storage/example.png',
            self::normalizePath(
                PathHelper::file(
                    directory: './storage',
                    filename: 'example.png',
                ),
            ),
        );
    }

    public function testReturnsEmptyStringForEmptyParts(): void
    {
        self::assertSame(
            '',
            PathHelper::join(
                '',
                '',
            ),
        );
    }

    private static function normalizePath(string $path): string
    {
        return str_replace(
            search: '\\',
            replace: '/',
            subject: $path,
        );
    }
}
