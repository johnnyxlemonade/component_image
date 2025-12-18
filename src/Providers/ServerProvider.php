<?php declare(strict_types=1);

namespace Lemonade\Image\Providers;

/**
 * ServerProvider
 *
 * Bezpečná vrstva pro přístup k proměnným $_SERVER.
 * Garantuje:
 * - návrat vždy stringu
 * - typová čistota
 * - centrální místo pro validaci vstupů ze serveru
 *
 * @package     Lemonade Framework
 * @subpackage  Image\Providers
 * @category    Environment
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
 */
final class ServerProvider
{
    /**
     * Vrátí hodnotu ze $_SERVER jako string (neexistující → default).
     */
    public static function get(string $key, string $default = ''): string
    {
        $value = $_SERVER[$key] ?? $default;
        return is_string($value) ? $value : $default;
    }

    /**
     * Zjistí, zda existuje neprázdná stringová hodnota v $_SERVER.
     */
    public static function has(string $key): bool
    {
        return isset($_SERVER[$key])
            && is_string($_SERVER[$key])
            && $_SERVER[$key] !== '';
    }
}
