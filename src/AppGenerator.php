<?php declare(strict_types=1);


namespace Lemonade\Image;

use Lemonade\Image\Traits\ObjectTrait;
use Lemonade\Image\Traits\MixedTrait;
use Lemonade\Image\Utils\Callback;


/**
 * Basic manipulation with images. Supported types are JPEG, PNG, GIF, WEBP
 *
 * @method AppGenerator affine(array $affine, array $clip = null)
 * @method array affineMatrixConcat(array $m1, array $m2)
 * @method array affineMatrixGet(int $type, mixed $options = null)
 * @method void alphaBlending(bool $on)
 * @method void antialias(bool $on)
 * @method void arc($x, $y, $w, $h, $start, $end, $color)
 * @method void char(int $font, $x, $y, string $char, $color)
 * @method void charUp(int $font, $x, $y, string $char, $color)
 * @method int colorAllocate($red, $green, $blue)
 * @method int colorAllocateAlpha($red, $green, $blue, $alpha)
 * @method int colorAt($x, $y)
 * @method int colorClosest($red, $green, $blue)
 * @method int colorClosestAlpha($red, $green, $blue, $alpha)
 * @method int colorClosestHWB($red, $green, $blue)
 * @method void colorDeallocate($color)
 * @method int colorExact($red, $green, $blue)
 * @method int colorExactAlpha($red, $green, $blue, $alpha)
 * @method void colorMatch(Image $image2)
 * @method int colorResolve($red, $green, $blue)
 * @method int colorResolveAlpha($red, $green, $blue, $alpha)
 * @method void colorSet($index, $red, $green, $blue)
 * @method array colorsForIndex($index)
 * @method int colorsTotal()
 * @method int colorTransparent($color = null)
 * @method void convolution(array $matrix, float $div, float $offset)
 * @method void copy(Image $src, $dstX, $dstY, $srcX, $srcY, $srcW, $srcH)
 * @method void copyMerge(Image $src, $dstX, $dstY, $srcX, $srcY, $srcW, $srcH, $opacity)
 * @method void copyMergeGray(Image $src, $dstX, $dstY, $srcX, $srcY, $srcW, $srcH, $opacity)
 * @method void copyResampled(Image $src, $dstX, $dstY, $srcX, $srcY, $dstW, $dstH, $srcW, $srcH)
 * @method void copyResized(Image $src, $dstX, $dstY, $srcX, $srcY, $dstW, $dstH, $srcW, $srcH)
 * @method AppGenerator cropAuto(int $mode = -1, float $threshold = .5, int $color = -1)
 * @method void ellipse($cx, $cy, $w, $h, $color)
 * @method void fill($x, $y, $color)
 * @method void filledArc($cx, $cy, $w, $h, $s, $e, $color, $style)
 * @method void filledEllipse($cx, $cy, $w, $h, $color)
 * @method void filledPolygon(array $points, $numPoints, $color)
 * @method void filledRectangle($x1, $y1, $x2, $y2, $color)
 * @method void fillToBorder($x, $y, $border, $color)
 * @method void filter($filtertype)
 * @method void flip(int $mode)
 * @method array ftText($size, $angle, $x, $y, $col, string $fontFile, string $text, array $extrainfo = null)
 * @method void gammaCorrect(float $inputgamma, float $outputgamma)
 * @method array getClip()
 * @method int interlace($interlace = null)
 * @method bool isTrueColor()
 * @method void layerEffect($effect)
 * @method void line($x1, $y1, $x2, $y2, $color)
 * @method void openPolygon(array $points, int $num_points, int $color)
 * @method void paletteCopy(Image $source)
 * @method void paletteToTrueColor()
 * @method void polygon(array $points, $numPoints, $color)
 * @method array psText(string $text, $font, $size, $color, $backgroundColor, $x, $y, $space = null, $tightness = null, float $angle = null, $antialiasSteps = null)
 * @method void rectangle($x1, $y1, $x2, $y2, $col)
 * @method mixed resolution(int $res_x = null, int $res_y = null)
 * @method AppGenerator rotate(float $angle, $backgroundColor)
 * @method void saveAlpha(bool $saveflag)
 * @method AppGenerator scale(int $newWidth, int $newHeight = -1, int $mode = IMG_BILINEAR_FIXED)
 * @method void setBrush(Image $brush)
 * @method void setClip(int $x1, int $y1, int $x2, int $y2)
 * @method void setInterpolation(int $method = IMG_BILINEAR_FIXED)
 * @method void setPixel($x, $y, $color)
 * @method void setStyle(array $style)
 * @method void setThickness($thickness)
 * @method void setTile(Image $tile)
 * @method void string($font, $x, $y, string $s, $col)
 * @method void stringUp($font, $x, $y, string $s, $col)
 * @method void trueColorToPalette(bool $dither, $ncolors)
 * @method array ttfText($size, $angle, $x, $y, $color, string $fontfile, string $text)
 * @property-read int $width
 * @property-read int $height
 * @property-read resource|\GdImage $imageResource
 */
final class AppGenerator {
    
    use ObjectTrait;
    use MixedTrait;
    
    /**
     * Only shrinks images
     * @var string
     */
    const SHRINK_ONLY = 0b0001;
    
    /**
     * will ignore aspect ratio
     * @var unknown
     */
    const STRETCH = 0b0010;
    
    /**
     * fits in given area so its dimensions are less than or equal to the required dimensions
     * @var int
     */
    const FIT = 0b0000;
    
    /**
     * fills given area so its dimensions are greater than or equal to the required dimensions
     * @var string
     */
    const FILL = 0b0100;
    
    /**
     * fills given area exactly
     * @var string
     */
    const EXACT = 0b1000;
    
    /**
     * JPEG
     * @var int
     */
    const JPEG = IMAGETYPE_JPEG;
    
    /**
     * PNG
     * @var int
     */
    const PNG = IMAGETYPE_PNG;
    
    /**
     * Gif
     * @var int
     */
    const GIF = IMAGETYPE_GIF;
    
    /**
     * WEBP (7.1)
     * @var int
     */
    const WEBP = 18;
    
    /**
     * Prazdny GIF
     * @var string
     */
    const EMPTY_GIF = "GIF89a\x01\x00\x01\x00\x80\x00\x00\x00\x00\x00\x00\x00\x00!\xf9\x04\x01\x00\x00\x00\x00,\x00\x00\x00\x00\x01\x00\x01\x00\x00\x02\x02D\x01\x00;";
    
    /**
     * Formaty
     * @var array
     */
    const FORMATS = [
        self::JPEG => "jpeg",
        self::PNG => "png",
        self::GIF => "gif",
        self::WEBP => "webp"
    ];
    
    /**
     *
     * @var resource|\GdImage 
     */
    private $image;
    
    
    /**
     * Returns RGB color (0..255) and transparency (0..127).
     */
    public static function rgb(int $red, int $green, int $blue, int $transparency = 0): array {
        
        return [
            'red' => max(0, min(255, $red)),
            'green' => max(0, min(255, $green)),
            'blue' => max(0, min(255, $blue)),
            'alpha' => max(0, min(127, $transparency)),
        ];
    }
    
    
    /**
     * Reads an image from a file and returns its type in $type.
     * @throws \LogicException if gd extension is not loaded
     * @throws \Exception if file not found or file type is not known
     */
    public static function fromFile(string $file, int $type = null) {
        
        if (!extension_loaded('gd')) {
            throw new \LogicException('PHP extension GD is not loaded.');
        }
        
        $type = self::detectTypeFromFile($file);
        
        if (!$type) {
            throw new \Exception(is_file($file) ? "Unknown type of file '$file'." : "File '$file' not found.");
        }
        
        return self::invokeSafe('imagecreatefrom' . self::FORMATS[$type], $file, "Unable to open file '$file'.", __METHOD__);
    }
    
    
    /**
     * Reads an image from a string and returns its type in $type.
     * @throws \LogicException if gd extension is not loaded
     * @throws \Exception
     */
    public static function fromString(string $s, int $type = null) {
        
        if (!extension_loaded('gd')) {
            
            throw new \LogicException('PHP extension GD is not loaded.');
        }
        
        $type = self::detectTypeFromString($s);
        
        if (!$type) {
            throw new \Exception('Unknown type of image.');
        }
        
        return self::invokeSafe('imagecreatefromstring', $s, 'Unable to open image from string.', __METHOD__);
    }
    
    
    /**
     *
     * @param string $func
     * @param string $arg
     * @param string $message
     * @param string $callee
     * @throws \Exception
     * @return \Lemonade\Image\AppGenerator
     */
    private static function invokeSafe(string $func, string $arg, string $message, string $callee) {
        
        $errors = [];
        
        $res = Callback::invokeSafe($func, [$arg], function (string $message) use (&$errors) {
            
            $errors[] = $message;
        });
        
            if (!$res) {
                
                throw new \Exception($message . ' Errors: ' . implode(', ', $errors));
                
            } elseif ($errors) {
                
                trigger_error($callee . '(): ' . implode(', ', $errors), E_USER_WARNING);
            }
            
            return new static($res);
    }
    
    
    /**
     * Creates a new true color image of the given dimensions. The default color is black.
     *
     * @param int $width
     * @param int $height
     * @param array $color
     * @throws \LogicException
     * @throws \InvalidArgumentException
     * @return \Lemonade\Image\AppGenerator
     */
    public static function fromBlank(int $width, int $height, array $color = null) {
        
        if (!extension_loaded('gd')) {
            
            throw new \LogicException('PHP extension GD is not loaded.');
        }
        
        if ($width < 1 || $height < 1) {
            
            throw new \InvalidArgumentException('Image width and height must be greater than zero.');
        }
        
        $image = imagecreatetruecolor($width, $height);
        
        if ($color) {
            
            $color += ['alpha' => 0];
            $color = imagecolorresolvealpha($image, $color['red'], $color['green'], $color['blue'], $color['alpha']);
            imagealphablending($image, false);
            imagefilledrectangle($image, 0, 0, $width - 1, $height - 1, $color);
            imagealphablending($image, true);
        }
        
        return new static($image);
    }
    
    
    /**
     * Returns the type of image from file.
     * @param string $file
     * @return int
     */
    public static function detectTypeFromFile(string $file) {
        
        $type = @getimagesize($file)[2]; // @ - files smaller than 12 bytes causes read error
        
        return isset(self::FORMATS[$type]) ? $type : null;
    }
    
    
    /**
     * Returns the type of image from string.
     * @return int
     */
    public static function detectTypeFromString(string $s) {
        
        $type = @getimagesizefromstring($s)[2]; // @ - strings smaller than 12 bytes causes read error
        return isset(self::FORMATS[$type]) ? $type : null;
    }
    
    
    /**
     * Returns the file extension for the given Image constanst
     *
     * @param int $type
     * @throws \InvalidArgumentException
     * @return string
     */
    public static function typeToExtension(int $type): string {
        
        if (!isset(self::FORMATS[$type])) {
            throw new \InvalidArgumentException("Unsupported image type '$type'.");
        }
        
        return self::FORMATS[$type];
    }
    
    /**
     * Returns the mime type for the given Image constant
     *
     * @param int $type
     * @return string
     */
    public static function typeToMimeType(int $type): string {
        
        return "image/" . self::typeToExtension($type);
    }
    
    
    /**
     * Wraps GD image.
     * @param  resource|\GdImage  $image
     */
    public function __construct($image) {
        
        $this->setImageResource($image);
        
        imagesavealpha($image, true);
    }
    
    
    /**
     * Returns image width.
     */
    public function getWidth(): int {
        
        return imagesx($this->image);
    }
    
    
    /**
     * Returns image height.
     */
    public function getHeight(): int {
        
        return imagesy($this->image);
    }
    
    
    /**
     *
     * @param resource|\GdImage  $image
     * @throws \Exception
     * @return \Lemonade\Image\AppGenerator
     */
    protected function setImageResource($image) {
        
        if (!$image instanceof \GdImage && !(is_resource($image) && get_resource_type($image) === 'gd')) {
             throw new \Exception("Image is not valid.");
		}
        
        $this->image = $image;
        return $this;
    }
    
    
    /**
     * Returns image GD resource.
     * @return resource|\GdImage
     */
    public function getImageResource() {
        
        return $this->image;
    }
    
    /**
     * Scales an image. Width and height accept pixels or percent.
     *
     * @param int|string|null $width
     * @param int|string|null $height
     * @param int $mode
     * @param bool $shrinkOnly
     */
    public function resize($width = null,  $height = null, int $mode = self::FIT, bool $shrinkOnly = false) {
        
        if ($mode === self::EXACT) {
            
            return $this->resize($width, $height, self::FILL)->crop("50%", "50%", $width, $height);
        }
        
        list($newWidth, $newHeight) = static::calculateSize($this->getWidth(), $this->getHeight(), $width, $height, $mode, $shrinkOnly);
        
        if ($newWidth !== $this->getWidth() || $newHeight !== $this->getHeight()) { // resize
            
            $newImage = static::fromBlank($newWidth, $newHeight, self::rgb(0, 0, 0, 127))->getImageResource();
            imagecopyresampled($newImage, $this->image, 0, 0, 0, 0, $newWidth, $newHeight, $this->getWidth(), $this->getHeight());
            
            $this->image = $newImage;
        }
        
        if ($width < 0 || $height < 0) {
            imageflip($this->image, $width < 0 ? ($height < 0 ? IMG_FLIP_BOTH : IMG_FLIP_HORIZONTAL) : IMG_FLIP_VERTICAL);
        }
        
        return $this;
    }
    
    
    /**
     * Calculates dimensions of resized image. Width and height accept pixels or percent.
     *
     * @param int $srcWidth
     * @param int $srcHeight
     * @param int $newWidth
     * @param int $newHeight
     * @param int $mode
     * @param bool $shrinkOnly
     */
    public static function calculateSize($srcWidth, $srcHeight, $newWidth, $newHeight, $mode = self::FIT, bool $shrinkOnly = false): array {
        
        $shrinkOnly = $shrinkOnly || ($mode & self::SHRINK_ONLY); // back compatibility
        if ($newWidth === null) {
        } elseif (self::isPercent($newWidth)) {
            $newWidth = (int) round($srcWidth / 100 * abs($newWidth));
            $percents = true;
        } else {
            $newWidth = abs($newWidth);
        }
        
        if ($newHeight === null) {
        } elseif (self::isPercent($newHeight)) {
            $newHeight = (int) round($srcHeight / 100 * abs($newHeight));
            $mode |= empty($percents) ? 0 : self::STRETCH;
        } else {
            $newHeight = abs($newHeight);
        }
        
        if ($mode & self::STRETCH) { // non-proportional
            if (!$newWidth || !$newHeight) {
                throw new \InvalidArgumentException('For stretching must be both width and height specified.');
            }
            
            if ($shrinkOnly) {
                $newWidth = (int) round($srcWidth * min(1, $newWidth / $srcWidth));
                $newHeight = (int) round($srcHeight * min(1, $newHeight / $srcHeight));
            }
        } else {  // proportional
            if (!$newWidth && !$newHeight) {
                throw new \InvalidArgumentException('At least width or height must be specified.');
            }
            
            $scale = [];
            if ($newWidth > 0) { // fit width
                $scale[] = $newWidth / $srcWidth;
            }
            
            if ($newHeight > 0) { // fit height
                $scale[] = $newHeight / $srcHeight;
            }
            
            if ($mode & self::FILL) {
                $scale = [max($scale)];
            }
            
            if ($shrinkOnly) {
                $scale[] = 1;
            }
            
            $scale = min($scale);
            $newWidth = (int) round($srcWidth * $scale);
            $newHeight = (int) round($srcHeight * $scale);
        }
        
        return [max($newWidth, 1), max($newHeight, 1)];
    }
    
    
    /**
     * Crops image.
     *
     * @param int|string $left   x-offset in pixels or percent
     * @param int|string $top    y-offset in pixels or percent
     * @param int|string $width  width in pixels or percent
     * @param int|strnig height in pixels or percent
     * @return static
     */
    public function crop( $left, $top, $width, $height) {
        
        list($r['x'], $r['y'], $r['width'], $r['height']) = static::calculateCutout($this->getWidth(), $this->getHeight(), $left, $top, $width, $height);
        
        if (gd_info()['GD Version'] === 'bundled (2.1.0 compatible)') {
            
            $this->image = imagecrop($this->image, $r);
            imagesavealpha($this->image, true);
            
        } else {
            
            $newImage = static::fromBlank($r['width'], $r['height'], self::RGB(0, 0, 0, 127))->getImageResource();
            imagecopy($newImage, $this->image, 0, 0, $r['x'], $r['y'], $r['width'], $r['height']);
            $this->image = $newImage;
        }
        
        
        return $this;
    }
    
    
    /**
     * Calculates dimensions of cutout in image. Arguments accepts pixels or percent.
     *
     * @param int $srcWidth
     * @param int $srcHeight
     * @param int|string $left
     * @param int|string $top
     * @param int|string $newWidth
     * @param int|string $newHeight
     */
    public static function calculateCutout(int $srcWidth, int $srcHeight, $left, $top, $newWidth, $newHeight): array {
        
        if (self::isPercent($newWidth)) {
            $newWidth = (int) round($srcWidth / 100 * $newWidth);
        }
        
        if (self::isPercent($newHeight)) {
            $newHeight = (int) round($srcHeight / 100 * $newHeight);
        }
        
        if (self::isPercent($left)) {
            $left = (int) round(($srcWidth - $newWidth) / 100 * $left);
        }
        
        if (self::isPercent($top)) {
            $top = (int) round(($srcHeight - $newHeight) / 100 * $top);
        }
        
        if ($left < 0) {
            $newWidth += $left;
            $left = 0;
        }
        
        if ($top < 0) {
            $newHeight += $top;
            $top = 0;
        }
        
        $newWidth = min($newWidth, $srcWidth - $left);
        $newHeight = min($newHeight, $srcHeight - $top);
        return [$left, $top, $newWidth, $newHeight];
    }
    
    
    /**
     * Sharpens image a little bit.
     */
    public function sharpen() {
        
        imageconvolution($this->image, [ // my magic numbers ;)
            [-1, -1, -1],
            [-1, 24, -1],
            [-1, -1, -1],
        ], 16, 0);
        return $this;
    }
    
    
    /**
     * Puts another image into this image.
     * @param  Image
     * @param  int|string  x-coordinate in pixels or percent
     * @param  int|string  y-coordinate in pixels or percent
     * @param  int  opacity 0..100
     * @return static
     */
    public function place(self $image, $left = 0, $top = 0, int $opacity = 100) {
        
        $opacity = max(0, min(100, $opacity));
        if ($opacity === 0) {
            return $this;
        }
        
        $width = $image->getWidth();
        $height = $image->getHeight();
        
        if (self::isPercent($left)) {
            $left = (int) round(($this->getWidth() - $width) / 100 * $left);
        }
        
        if (self::isPercent($top)) {
            $top = (int) round(($this->getHeight() - $height) / 100 * $top);
        }
        
        $output = $input = $image->image;
        if ($opacity < 100) {
            $tbl = [];
            for ($i = 0; $i < 128; $i++) {
                $tbl[$i] = round(127 - (127 - $i) * $opacity / 100);
            }
            
            $output = imagecreatetruecolor($width, $height);
            imagealphablending($output, false);
            
            if (!$image->isTrueColor()) {
                
                $input = $output;
                imagefilledrectangle($output, 0, 0, $width, $height, imagecolorallocatealpha($output, 0, 0, 0, 127));
                imagecopy($output, $image->image, 0, 0, 0, 0, $width, $height);
            }
            
            for ($x = 0; $x < $width; $x++) {
                for ($y = 0; $y < $height; $y++) {
                    $c = \imagecolorat($input, $x, $y);
                    $c = ($c & 0xFFFFFF) + ($tbl[$c >> 24] << 24);
                    \imagesetpixel($output, $x, $y, $c);
                }
            }
            
            imagealphablending($output, true);
        }
        
        imagecopy($this->image, $output, $left, $top, 0, 0, $width, $height);
        
        return $this;
    }
    
    
    /**
     * Saves image to the file. Quality is in the range 0..100 for JPEG (default 85), WEBP (default 80) and AVIF (default 30) and 0..9 for PNG (default 9).
     *
     * @param string $file
     * @param int $quality
     * @param int $type
     */
    public function save(string $file, int $quality = null, int $type = null) {
                
        if ($type === null) {
            
            $extensions = array_flip(self::FORMATS) + ["jpg" => self::JPEG];            
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                        
            if (!isset($extensions[$ext])) {
                throw new \InvalidArgumentException("Unsupported file extension '$ext'.");
            }
            
            $type = $extensions[$ext];
        }
        
        $this->output($type, $quality, $file);
    }
    
    
    /**
     * Outputs image to string. Quality is in the range 0..100 for JPEG (default 85), WEBP (default 80) and AVIF (default 30) and 0..9 for PNG (default 9).
     */
    public function toString(int $type = self::JPEG, int $quality = null): string {
        
        return self::capture(function () use ($type, $quality) {
            $this->output($type, $quality);
        });
            
            
    }
    
    
    /**
     * Outputs image to string.
     */
    public function __toString(): string {
        
        return $this->toString();
    }
    
    /**
     * Outputs image to browser. Quality is in the range 0..100 for JPEG (default 85), WEBP (default 80) and AVIF (default 30) and 0..9 for PNG (default 9).
     *
     * @param int $type
     * @param int $quality
     */
    public function send(int $type = self::JPEG, int $quality = null) {
        
        header('Content-Type: ' . self::typeToMimeType($type));
        
        $this->output($type, $quality);
    }
    
    
    /**
     * Outputs image to browser or file.
     * @throws \InvalidArgumentException
     * @throws \Exception
     */
    private function output(int $type, int $quality = null, string $file = null) {
        
        
        switch ($type) {
            case self::JPEG:
                
                $quality = $quality === null ? 85 : max(0, min(100, $quality));
                $success = @imagejpeg($this->image, $file, $quality); // @ is escalated to exception
                
            break;
                
            case self::PNG:
                
                $quality = $quality === null ? 9 : max(0, min(9, $quality));
                $success = @imagepng($this->image, $file, $quality); // @ is escalated to exception
                
            break;
                
            case self::GIF:
                
                $success = @imagegif($this->image, $file); // @ is escalated to exception
                
            break;
                
            case self::WEBP:
                
                $quality = $quality === null ? 80 : max(0, min(100, $quality));
                $success = @imagewebp($this->image, $file, $quality); // @ is escalated to exception
                
             break;
                
            default:
                throw new \InvalidArgumentException("Unsupported image type '$type'.");
        }
        
        if (!$success) {
            
            throw new \Exception(static::getLastError() ?: 'Unknown error');
        }
    }
    
    
    /**
     * Call to undefined method.
     *
     * @param string $name
     * @param array $args
     * @throws \Exception
     * @return mixed
     */
    public function __call(string $name, array $args) {
        
        $function = "image" . $name;
        
        if (!function_exists($function)) {
            
            throw new \Exception(sprintf("Call to undefined method: %s::%s()", static::class, $name));
        }
        
        foreach ($args as $key => $value) {
            if ($value instanceof self) {
                
                $args[$key] = $value->getImageResource();
                
            } elseif (is_array($value) && isset($value['red'])) { // rgb
                
                $args[$key] = imagecolorallocatealpha($this->image, $value['red'], $value['green'], $value['blue'], $value['alpha']) ?: imagecolorresolvealpha( $this->image, $value['red'], $value['green'], $value['blue'], $value['alpha']);
            }
        }
        
        $res = $function($this->image, ...$args);
        
        return (is_resource($res) && get_resource_type($res) === "gd") ? $this->setImageResource($res) : $res;
    }
    
    
    /**
     *
     */
    public function __clone() {
        
        ob_start(function () {});
        
        imagegd2($this->image);
        
        $this->setImageResource(imagecreatefromstring(ob_get_clean()));
    }
    
    
    /**
     *
     * @param int|string $num
     * @throws \InvalidArgumentException
     * @return bool
     */
    private static function isPercent( &$num): bool {
        
        if (is_string($num) && static::str_ends_with($num, '%')) {
            
            $num = (float) substr($num, 0, -1);
            
            return true;
            
        } elseif (is_int($num) || $num === (string) (int) $num) {
            
            $num = (int) $num;
            
            return false;
        }
        
        throw new \InvalidArgumentException("Expected dimension in int|string, '$num' given.");
    }
    
    
    /**
     * Prevents serialization.
     */
    public function __sleep(): array {
        
        throw new \LogicException('You cannot serialize or unserialize ' . self::class . ' instances.');
    }

    
}
