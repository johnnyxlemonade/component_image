<?php declare(strict_types=1);

namespace Lemonade\Image\Providers;

final class DataProvider
{

    /**
     * Soubor parametry
     * @var array
     */
    private $fileOpts = [];

    /**
     * Barva
     * @var string
     */
    private $fileColor = "ffffff";

    /**
     * @param string|null $args
     */
    public function __construct(string $args = null)
    {

        $this->_processData(args: $args);
    }


    /**
     * Hash
     * @return string
     */
    public function getHash(): string
    {

        return md5(json_encode($this->fileOpts));
    }

    /**
     * Sirka
     * @return int|null
     */
    public function getWidth(): ?int
    {

        return ($this->fileOpts["app_width"] ?? null);
    }

    /**
     * @param int $width
     * @return void
     */
    public function setWidth(int $width): void
    {

        $this->fileOpts["app_width"] = $width;
    }

    /**
     * Vyska
     * @return int|null
     */
    public function getHeight(): ?int
    {

        return ($this->fileOpts["app_height"] ?? null);
    }

    /**
     * @param int $height
     * @return void
     */
    public function setHeight(int $height): void
    {
        $this->fileOpts["app_height"] = $height;
    }

    /**
     * Typ orezu
     * @return int|null
     */
    public function getCrop(): ?int
    {

        return ($this->fileOpts["app_crop"] ?? 0);
    }

    /**
     * Canvas
     * @return string
     */
    public function getCanvasColor(): string
    {

        return ($this->fileOpts["app_canvas"] ?? "ffffff");
    }

    /**
     * Kvalita
     * @return int
     */
    public function getQuality(): int
    {

        return ($this->fileOpts["app_quality"] ?? 72);
    }

    /**
     * Chybny obrazek
     * @return bool
     */
    public function getMissing(): bool
    {

        return ($this->fileOpts["app_missing"] ?? true);
    }

    /**
     * Nebyla uvedena vyska a sirka
     * @return bool
     */
    public function isMissingAllSize(): bool
    {

        if (!isset($this->fileOpts["app_width"]) && !isset($this->fileOpts["app_height"])) {

            return true;

        }

        return false;
    }

    /**
     * Nastavi filtry
     * @param string|null $args
     * @return void
     */
    protected function _processData(string $args = null): void
    {

        if (!empty($data = explode("-", $args))) {
            foreach ($data as $item) {

                $key = mb_substr($item, 0, 1);
                $val = mb_substr($item, 1);

                // canvas
                if ($key == "c" && mb_strlen($val) === 6 && ctype_xdigit(strval($val))) {

                    $this->fileOpts["app_canvas"] = (string)$val;
                }

                // numbers
                if (in_array($key, ["w", "h", "q", "e", "z"]) && ctype_digit(strval($val))) {

                    // sirka
                    if ($key === "w") {

                        $this->fileOpts["app_width"] = (int) $val;
                    }

                    // vyska
                    if ($key === "h") {

                        $this->fileOpts["app_height"] = (int) $val;
                    }

                    // kvalita
                    if ($key === "q") {

                        $this->fileOpts["app_quality"] = (int) $val;
                    }

                    // chybovy obrazek
                    if ($key === "e" && in_array((int) $val, [0, 1], true)) {

                        $this->fileOpts["app_missing"] = $val === "1";
                    }

                    // typOriznuti
                    if ($key === "z" && in_array((int) $val, [0, 1, 2, 3], true)) {

                        $this->fileOpts["app_crop"] = (int) $val;
                    }

                }
            }
        }

    }
}