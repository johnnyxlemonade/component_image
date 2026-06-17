<?php

declare(strict_types=1);

namespace Lemonade\Image\Utils;

use function array_filter;
use function array_values;
use function implode;
use function preg_match;
use function rtrim;
use function str_replace;
use function str_starts_with;
use function substr;
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
                array: $parts,
                callback: static fn(string $part): bool => $part !== '',
            ),
        );

        if ($parts === []) {
            return '';
        }

        $parts[0] = str_replace(
            search: ['/', '\\'],
            replace: DIRECTORY_SEPARATOR,
            subject: $parts[0],
        );

        $prefix = self::resolvePrefix(
            firstPart: $parts[0],
        );

        if ($prefix !== '') {
            $parts[0] = self::removePrefix(
                part: $parts[0],
                prefix: $prefix,
            );
        }

        $normalized = [];

        foreach ($parts as $part) {
            $part = str_replace(
                search: ['/', '\\'],
                replace: DIRECTORY_SEPARATOR,
                subject: $part,
            );
            $part = trim(
                string: $part,
                characters: DIRECTORY_SEPARATOR,
            );

            if ($part === '') {
                continue;
            }

            $normalized[] = $part;
        }

        if ($normalized === []) {
            return rtrim(
                string: $prefix,
                characters: DIRECTORY_SEPARATOR,
            );
        }

        return $prefix . implode(
            separator: DIRECTORY_SEPARATOR,
            array: $normalized,
        );
    }

    public static function file(string $directory, string $filename): string
    {
        return self::join(
            $directory,
            $filename,
        );
    }

    private static function resolvePrefix(string $firstPart): string
    {
        if ($firstPart === '.') {
            return '.' . DIRECTORY_SEPARATOR;
        }

        if (str_starts_with(
            haystack: $firstPart,
            needle: '.' . DIRECTORY_SEPARATOR,
        )) {
            return '.' . DIRECTORY_SEPARATOR;
        }

        if ($firstPart === DIRECTORY_SEPARATOR) {
            return DIRECTORY_SEPARATOR;
        }

        if (preg_match('/^[A-Za-z]:$/', $firstPart) === 1) {
            return $firstPart . DIRECTORY_SEPARATOR;
        }

        if (preg_match('/^[A-Za-z]:[\\\\\/]/', $firstPart) === 1) {
            return substr(
                string: $firstPart,
                offset: 0,
                length: 3,
            );
        }

        if (str_starts_with(
            haystack: $firstPart,
            needle: DIRECTORY_SEPARATOR,
        )) {
            return DIRECTORY_SEPARATOR;
        }

        return '';
    }

    private static function removePrefix(string $part, string $prefix): string
    {
        if ($prefix === DIRECTORY_SEPARATOR) {
            return substr(
                string: $part,
                offset: 1,
            );
        }

        if ($prefix === '.' . DIRECTORY_SEPARATOR) {
            return substr(
                string: $part,
                offset: 2,
            );
        }

        if (preg_match('/^[A-Za-z]:[\\\\\/]/', $prefix) === 1) {
            return substr(
                string: $part,
                offset: 3,
            );
        }

        return $part;
    }
}
