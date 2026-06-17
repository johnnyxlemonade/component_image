<?php

declare(strict_types=1);

namespace Lemonade\Image\Tests\Unit\Generator;

use Lemonade\Image\Generator\ImageRequest;
use PHPUnit\Framework\TestCase;

final class ImageRequestTest extends TestCase
{
    public function testCarriesRawRequestInput(): void
    {
        $request = new ImageRequest(
            level: 6,
            storageTypeId: 'thumbnail',
            moduleId: 10,
            artId: 12345,
            baseName: 'example.png',
            args: 'w320-h240-z1-cffffff-q85',
        );

        self::assertSame(6, $request->level);
        self::assertSame('thumbnail', $request->storageTypeId);
        self::assertSame(10, $request->moduleId);
        self::assertSame(12345, $request->artId);
        self::assertSame('example.png', $request->baseName);
        self::assertSame('w320-h240-z1-cffffff-q85', $request->args);
    }

    public function testAllowsNullableRoutingValues(): void
    {
        $request = new ImageRequest(
            level: 3,
            storageTypeId: null,
            moduleId: null,
            artId: null,
            baseName: null,
            args: null,
        );

        self::assertSame(3, $request->level);
        self::assertNull($request->storageTypeId);
        self::assertNull($request->moduleId);
        self::assertNull($request->artId);
        self::assertNull($request->baseName);
        self::assertNull($request->args);
    }

    public function testAllowsStringIdentifiers(): void
    {
        $request = new ImageRequest(
            level: 6,
            storageTypeId: 'gallery',
            moduleId: '20',
            artId: '54321',
            baseName: 'gallery-image.png',
            args: 'md2',
        );

        self::assertSame('gallery', $request->storageTypeId);
        self::assertSame('20', $request->moduleId);
        self::assertSame('54321', $request->artId);
    }
}
