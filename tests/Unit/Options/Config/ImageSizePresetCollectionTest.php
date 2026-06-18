<?php

declare(strict_types=1);

namespace Lemonade\Image\Tests\Unit\Options\Config;

use Lemonade\Image\Options\Config\ImageSizePreset;
use Lemonade\Image\Options\Config\ImageSizePresetCollection;
use PHPUnit\Framework\TestCase;

final class ImageSizePresetCollectionTest extends TestCase
{
    public function testReturnsPresetByCode(): void
    {
        $collection = new ImageSizePresetCollection([
            ImageSizePreset::create(
                code: 'card',
                width: 600,
                height: 400,
            ),
        ]);

        $preset = $collection->get('card');

        self::assertNotNull($preset);
        self::assertSame('card', $preset->getCode());
        self::assertSame(600, $preset->getWidth());
        self::assertSame(400, $preset->getHeight());
    }

    public function testReturnsNullForUnknownPreset(): void
    {
        $collection = new ImageSizePresetCollection([
            ImageSizePreset::create(
                code: 'card',
                width: 600,
                height: 400,
            ),
        ]);

        self::assertNull($collection->get('hero'));
    }

    public function testDefaultCollectionContainsKnownPresets(): void
    {
        $collection = ImageSizePresetCollection::createDefault();

        $preset = $collection->get('md');

        self::assertNotNull($preset);
        self::assertSame('md', $preset->getCode());
        self::assertSame(160, $preset->getWidth());
        self::assertSame(160, $preset->getHeight());
    }
}
