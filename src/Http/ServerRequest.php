<?php

declare(strict_types=1);

namespace Lemonade\Image\Http;

use function is_string;

/**
 * Provides safe access to server request variables.
 *
 * Reads values from the server environment while normalizing missing or
 * non-string values to explicit string defaults.
 *
 * @package     Lemonade
 * @subpackage  Image\Http
 * @category    HTTP
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
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
