<?php

declare(strict_types=1);

namespace Lemonade\Image\Utils;

use function array_filter;
use function array_shift;
use function array_values;
use function implode;
use function rtrim;
use function trim;

use const DIRECTORY_SEPARATOR;

/**
 * Small helper for building normalized filesystem paths.
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

        $prefix = '';

        if ($parts[0] === '.') {
            $prefix = '.' . DIRECTORY_SEPARATOR;
            array_shift($parts);
        }

        $normalized = [];

        foreach ($parts as $part) {
            $part = trim($part, DIRECTORY_SEPARATOR);

            if ($part === '') {
                continue;
            }

            $normalized[] = $part;
        }

        if ($normalized === []) {
            return $prefix === ''
                ? ''
                : rtrim($prefix, DIRECTORY_SEPARATOR);
        }

        return $prefix . implode(DIRECTORY_SEPARATOR, $normalized);
    }

    public static function file(string $directory, string $filename): string
    {
        return self::join($directory, $filename);
    }
}
