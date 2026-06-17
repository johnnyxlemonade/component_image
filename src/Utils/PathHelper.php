<?php

declare(strict_types=1);

namespace Lemonade\Image\Utils;

use function array_filter;
use function array_shift;
use function array_values;
use function implode;
use function preg_match;
use function rtrim;
use function str_replace;
use function trim;

use const DIRECTORY_SEPARATOR;

/**
 * Builds normalized filesystem paths.
 *
 * Joins path segments while preserving relative, absolute and platform-specific
 * path prefixes.
 *
 * @package     Lemonade
 * @subpackage  Image\Utils
 * @category    Utility
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
 */
final class PathHelper
{
    private function __construct() {}

    public static function join(string ...$parts): string
    {
        $parts = array_values(
            array_filter(
                $parts,
                static fn(string $part): bool => $part !== '',
            ),
        );

        if ($parts === []) {
            return '';
        }

        $prefix = self::resolvePrefix(
            firstPart: $parts[0],
        );

        if ($prefix !== '') {
            array_shift($parts);
        }

        $normalized = [];

        foreach ($parts as $part) {
            $part = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $part);
            $part = trim($part, DIRECTORY_SEPARATOR);

            if ($part === '') {
                continue;
            }

            $normalized[] = $part;
        }

        if ($normalized === []) {
            return rtrim($prefix, DIRECTORY_SEPARATOR);
        }

        return $prefix . implode(DIRECTORY_SEPARATOR, $normalized);
    }

    public static function file(string $directory, string $filename): string
    {
        return self::join($directory, $filename);
    }

    private static function resolvePrefix(string $firstPart): string
    {
        $firstPart = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $firstPart);

        if ($firstPart === '.') {
            return '.' . DIRECTORY_SEPARATOR;
        }

        if ($firstPart === DIRECTORY_SEPARATOR) {
            return DIRECTORY_SEPARATOR;
        }

        if (preg_match('/^[A-Za-z]:[\\\\\/]?$/', $firstPart) === 1) {
            return rtrim($firstPart, '\\/') . DIRECTORY_SEPARATOR;
        }

        if (str_starts_with($firstPart, DIRECTORY_SEPARATOR)) {
            return DIRECTORY_SEPARATOR;
        }

        return '';
    }
}
