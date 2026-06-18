<?php

declare(strict_types=1);

namespace Lemonade\Image\Options\Config;

/**
 * Provides lookup access to named image size presets.
 *
 * @package     Lemonade
 * @subpackage  Image\Options\Config
 * @category    Options
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
 */
final class ImageSizePresetCollection
{
    /** @var array<string, ImageSizePreset> */
    private readonly array $items;

    /**
     * @param list<ImageSizePreset> $items
     */
    public function __construct(array $items)
    {
        $indexed = [];

        foreach ($items as $item) {
            $indexed[$item->getCode()] = $item;
        }

        $this->items = $indexed;
    }

    public static function createDefault(): self
    {
        return new self([
            ImageSizePreset::create(code: 'xss', width: 32, height: 32),
            ImageSizePreset::create(code: 'xs', width: 48, height: 48),
            ImageSizePreset::create(code: 'sm', width: 96, height: 96),
            ImageSizePreset::create(code: 'md', width: 160, height: 160),
            ImageSizePreset::create(code: 'lg', width: 320, height: 320),
            ImageSizePreset::create(code: 'xl', width: 640, height: 640),
        ]);
    }

    public function get(string $code): ?ImageSizePreset
    {
        return $this->items[$code] ?? null;
    }
}
