<?php

declare(strict_types=1);

namespace Lemonade\Image\Providers;

/**
 * Provides safe access to server request variables.
 *
 * @package     Lemonade
 * @subpackage  Image\Providers
 * @category    Provider
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
 */
final class ServerProvider
{
    public static function get(string $key, string $default = ''): string
    {
        $value = $_SERVER[$key] ?? $default;
        return is_string($value) ? $value : $default;
    }

    public static function has(string $key): bool
    {
        return isset($_SERVER[$key])
            && is_string($_SERVER[$key])
            && $_SERVER[$key] !== '';
    }
}
