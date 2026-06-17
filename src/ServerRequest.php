<?php

declare(strict_types=1);

namespace Lemonade\Image;

use function is_string;

/**
 * Provides safe access to server request variables.
 */
final class ServerRequest
{
    private function __construct() {}

    public static function get(string $key, string $default = ''): string
    {
        $value = $_SERVER[$key] ?? $default;

        return is_string($value)
            ? $value
            : $default;
    }

    public static function has(string $key): bool
    {
        return isset($_SERVER[$key])
            && is_string($_SERVER[$key])
            && $_SERVER[$key] !== '';
    }
}
