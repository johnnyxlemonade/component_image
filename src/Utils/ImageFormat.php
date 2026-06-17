<?php

declare(strict_types=1);

namespace Lemonade\Image\Utils;

use Lemonade\Image\Exceptions\Image\ImageTypeException;

use Lemonade\Image\Generator\AppGenerator;

use function getimagesize;
use function getimagesizefromstring;
use function is_array;

/**
 * Resolves image formats, extensions and MIME types.
 *
 * Provides type detection and format mapping used by image loading and output
 * generation.
 *
 * @package     Lemonade
 * @subpackage  Image\Utils
 * @category    Utility
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
 */
final class ImageFormat
{
    /**
     * @var array<int, string>
     */
    public const FORMATS = [
        AppGenerator::JPEG => 'jpeg',
        AppGenerator::PNG => 'png',
        AppGenerator::GIF => 'gif',
        AppGenerator::WEBP => 'webp',
    ];

    public static function detectTypeFromFile(string $file): ?int
    {
        $info = @getimagesize($file);

        if (!is_array($info)) {
            return null;
        }

        $type = $info[2];

        return isset(self::FORMATS[$type])
            ? $type
            : null;
    }

    public static function detectTypeFromString(string $content): ?int
    {
        $info = @getimagesizefromstring($content);

        if (!is_array($info)) {
            return null;
        }

        $type = $info[2];

        return isset(self::FORMATS[$type])
            ? $type
            : null;
    }

    public static function typeToExtension(int $type): string
    {
        if (!isset(self::FORMATS[$type])) {
            throw ImageTypeException::unsupported($type);
        }

        return self::FORMATS[$type];
    }

    public static function typeToMimeType(int $type): string
    {
        return 'image/' . self::typeToExtension($type);
    }

    /**
     * @return array<int, string>
     */
    public static function getFormats(): array
    {
        return self::FORMATS;
    }
}
