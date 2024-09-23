<?php declare(strict_types=1);


namespace Lemonade\Image\Providers;

use Lemonade\Image\Interfaces\ToArrayInterface;
use Lemonade\Image\Traits\StaticTrait;


final class ColorProvider implements ToArrayInterface
{

    use StaticTrait;

    /**
     *
     * @var int
     */
    private $red;

    /**
     *
     * @var int
     */
    private $green;

    /**
     *
     * @var int
     */
    private $blue;

    /**
     *
     * @param int $red
     * @param int $green
     * @param int $blue
     */
    protected function __construct(int $red, int $green, int $blue)
    {

        $this->red = $red;
        $this->green = $green;
        $this->blue = $blue;
    }

    /**
     * @return int
     */
    protected function getRed(): int
    {

        return $this->red;
    }

    /**
     * @return int
     */
    protected function getGreen(): int
    {

        return $this->green;
    }

    /**
     * @return int
     */
    protected function getBlue(): int
    {

        return $this->blue;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {

        return [
            "red" => max(0, min(255, $this->getRed())),
            "green" => max(0, min(255, $this->getGreen())),
            "blue" => max(0, min(255, $this->getBlue())),
        ];

    }

    /**
     * Hex na RGB
     * @param string $hex
     * @param int $trans
     * @return static
     */
    public static function hexRgb(string $hex, int $trans = 0): ColorProvider
    {

        list($r, $g, $b) = sscanf($hex, "%02x%02x%02x");

        return new ColorProvider($r, $g, $b);
    }


}