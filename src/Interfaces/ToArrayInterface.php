<?php

declare(strict_types=1);

namespace Lemonade\Image\Interfaces;

/**
 * Defines array serialization for lightweight value objects and DTOs.
 *
 * @package     Lemonade
 * @subpackage  Image\Interfaces
 * @category    Contract
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
 */
interface ToArrayInterface
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
