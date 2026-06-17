<?php

declare(strict_types=1);

namespace Lemonade\Image\Tests\Unit\Options;

use Lemonade\Image\Options\ImageOptionsParser;
use PHPUnit\Framework\TestCase;

final class ImageOptionsParserTest extends TestCase
{
    public function testParsesExplicitOptions(): void
    {
        $options = (new ImageOptionsParser(
            args: 'w320-h240-z1-cffffff-q85',
        ))->toDTO();

        self::assertSame(320, $options->getWidth());
        self::assertSame(240, $options->getHeight());
        self::assertSame(1, $options->getCrop());
        self::assertSame('ffffff', $options->getCanvasColor());
        self::assertSame(85, $options->getQuality());
        self::assertTrue($options->isMissing());
    }

    public function testParsesPreset(): void
    {
        $options = (new ImageOptionsParser(
            args: 'md',
        ))->toDTO();

        self::assertSame(160, $options->getWidth());
        self::assertSame(160, $options->getHeight());
    }

    public function testParsesPresetWithScale(): void
    {
        $options = (new ImageOptionsParser(
            args: 'md2',
        ))->toDTO();

        self::assertSame(320, $options->getWidth());
        self::assertSame(320, $options->getHeight());
    }

    public function testIgnoresPresetScaleAboveMaximum(): void
    {
        $options = (new ImageOptionsParser(
            args: 'md4',
        ))->toDTO();

        self::assertSame(50, $options->getWidth());
        self::assertSame(50, $options->getHeight());
    }

    public function testOriginalKeepsDimensionsEmpty(): void
    {
        $options = (new ImageOptionsParser(
            args: 'original',
        ))->toDTO();

        self::assertNull($options->getWidth());
        self::assertNull($options->getHeight());
        self::assertSame(-1, $options->getCrop());
    }

    public function testAppliesMinimumDimensions(): void
    {
        $options = (new ImageOptionsParser(
            args: 'w1-h1',
        ))->toDTO();

        self::assertSame(50, $options->getWidth());
        self::assertSame(50, $options->getHeight());
    }

    public function testAppliesMaximumDimensions(): void
    {
        $options = (new ImageOptionsParser(
            args: 'w9999-h9999',
        ))->toDTO();

        self::assertSame(2560, $options->getWidth());
        self::assertSame(2560, $options->getHeight());
    }

    public function testParsesMissingFlagDisabled(): void
    {
        $options = (new ImageOptionsParser(
            args: 'w320-h240-e0',
        ))->toDTO();

        self::assertFalse($options->isMissing());
    }

    public function testInvalidValuesFallBackToDefaults(): void
    {
        $options = (new ImageOptionsParser(
            args: 'wabc-habc-qabc-cxyz-z9-e9',
        ))->toDTO();

        self::assertSame(50, $options->getWidth());
        self::assertSame(50, $options->getHeight());
        self::assertSame(72, $options->getQuality());
        self::assertSame('ffffff', $options->getCanvasColor());
        self::assertSame(0, $options->getCrop());
        self::assertTrue($options->isMissing());
    }

    public function testInvalidTokensDoNotOverridePreviousValidValues(): void
    {
        $options = (new ImageOptionsParser(
            args: 'w800-wabc-h600-habc-q80-qabc-c000000-cxyz-z3-z9-e0-e9',
        ))->toDTO();

        self::assertSame(800, $options->getWidth());
        self::assertSame(600, $options->getHeight());
        self::assertSame(80, $options->getQuality());
        self::assertSame('000000', $options->getCanvasColor());
        self::assertSame(3, $options->getCrop());
        self::assertFalse($options->isMissing());
    }

    public function testClampsQualityToSupportedRange(): void
    {
        $low = (new ImageOptionsParser(
            args: 'q0',
        ))->toDTO();

        $high = (new ImageOptionsParser(
            args: 'q999',
        ))->toDTO();

        self::assertSame(1, $low->getQuality());
        self::assertSame(100, $high->getQuality());
    }
}
