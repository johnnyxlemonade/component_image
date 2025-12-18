<?php declare(strict_types=1);

namespace Lemonade\Image\Providers;

use Lemonade\Image\ImageOptionsParser;
use Lemonade\Image\ImageOptionsDTO;

/**
 * DataProvider
 *
 * Lehká přístupová vrstva nad parametry obrázku předanými v URL.
 * Interně využívá `ImageOptionsParser` pro dekódování argumentů
 * a uchovává immutable `ImageOptionsDTO`, který slouží jako jediný
 * zdroj pravdy pro generování obrázků.
 *
 * Klíčové vlastnosti:
 * - Zajišťuje bezpečný přístup ke všem normalizovaným hodnotám
 * - Poskytuje setter metody, které nevytvářejí mutaci, ale novou
 *   instanci DTO (immutable princip)
 * - Slouží jako datová vrstva pro `FileProvider` a `ImageProvider`
 * - Garantuje konzistenci všech hodnot již po parsování
 *
 * `DataProvider` neprovádí žádné výpočty ani transformace obrázků —
 * jeho úloha je pouze předat správně normalizovaná data generátoru.
 *
 * @package     Lemonade Framework
 * @subpackage  Image
 * @category    Provider
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
 * @see         ImageOptionsParser
 * @see         ImageOptionsDTO
 * @see         FileProvider
 */
final class DataProvider
{
    private ImageOptionsParser $parser;
    private ImageOptionsDTO $dto;

    public function __construct(?string $args = null)
    {
        $this->parser = new ImageOptionsParser(args: $args);
        $this->dto = $this->parser->toDTO();
    }

    public function getWidth(): ?int
    {
        return $this->dto->getWidth();
    }

    public function setWidth(int $width): void
    {
        $this->dto = $this->dto->withWidth($width);
    }

    public function getHeight(): ?int
    {
        return $this->dto->getHeight();
    }

    public function setHeight(int $height): void
    {
        $this->dto = $this->dto->withHeight($height);
    }

    public function getCrop(): int
    {
        return $this->dto->getCrop();
    }

    public function getCanvasColor(): string
    {
        return $this->dto->getCanvasColor();
    }

    public function getQuality(): int
    {
        return $this->dto->getQuality();
    }

    public function getMissing(): bool
    {
        return $this->dto->isMissing();
    }

    public function isMissingAllSize(): bool
    {
        return $this->dto->isMissingAllSize();
    }

    public function getHash(): string
    {
        return $this->dto->getHash();
    }

    public function getDTO(): ImageOptionsDTO
    {
        return $this->dto;
    }

}
