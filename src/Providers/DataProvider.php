<?php

declare(strict_types=1);

namespace Lemonade\Image\Providers;

use Lemonade\Image\ImageOptionsDTO;
use Lemonade\Image\ImageOptionsParser;

/**
 * Holds parsed image options and exposes them as a DTO.
 *
 * @package     Lemonade
 * @subpackage  Image\Providers
 * @category    Provider
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
 *
 * @see ImageOptionsDTO
 * @see ImageOptionsParser
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
