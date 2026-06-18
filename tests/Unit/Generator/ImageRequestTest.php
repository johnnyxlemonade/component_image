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

        self::assertSame(6, $request->getLevel());
        self::assertSame('thumbnail', $request->getStorageTypeId());
        self::assertSame(10, $request->getModuleId());
        self::assertSame(12345, $request->getArtId());
        self::assertSame('example.png', $request->getBaseName());
        self::assertSame('w320-h240-z1-cffffff-q85', $request->getArgs());
    }

    public function testCreatesRequestUsingNamedFactory(): void
    {
        $request = ImageRequest::create(
            level: 6,
            storageTypeId: 'gallery',
            moduleId: 12,
            artId: 345,
            baseName: 'example.jpg',
            args: 'w800-h600-z3-q85',
        );

        self::assertSame(6, $request->getLevel());
        self::assertSame('gallery', $request->getStorageTypeId());
        self::assertSame(12, $request->getModuleId());
        self::assertSame(345, $request->getArtId());
        self::assertSame('example.jpg', $request->getBaseName());
        self::assertSame('w800-h600-z3-q85', $request->getArgs());
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

        self::assertSame(3, $request->getLevel());
        self::assertNull($request->getStorageTypeId());
        self::assertNull($request->getModuleId());
        self::assertNull($request->getArtId());
        self::assertNull($request->getBaseName());
        self::assertNull($request->getArgs());
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

        self::assertSame('gallery', $request->getStorageTypeId());
        self::assertSame('20', $request->getModuleId());
        self::assertSame('54321', $request->getArtId());
    }
}
