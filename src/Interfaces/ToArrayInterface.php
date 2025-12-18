<?php declare(strict_types=1);

namespace Lemonade\Image\Interfaces;

/**
 * ToArrayInterface
 *
 * Jednoduchý kontrakt pro převod objektu na pole.
 * Používá se především pro předání dat do nízkoúrovňových API
 * (např. GD, serializace, normalizace vstupů).
 *
 * @package     Lemonade Framework
 * @subpackage  Image\Interfaces
 * @category    Interfaces
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
 */
interface ToArrayInterface
{
    /**
     * Vrátí objekt ve formě asociativního pole.
     */
    public function toArray(): array;
}
