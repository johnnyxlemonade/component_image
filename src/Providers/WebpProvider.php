<?php declare(strict_types=1);


namespace Lemonade\Image\Providers;

use Lemonade\Image\Traits\StaticTrait;

final class WebpProvider {
    
    use StaticTrait;

    /**
     * Webp
     * @var bool
     */
    private $webp = false;
    

    /**
     * @return bool
     */
    public static function hasSupport(): bool
    {
    
        if (isset($_SERVER["HTTP_ACCEPT"]) && strpos($_SERVER["HTTP_ACCEPT"], "image/webp") !== false || isset($_SERVER["HTTP_USER_AGENT"]) && strpos($_SERVER["HTTP_USER_AGENT"], " Chrome/") !== false) {
            
            return true;
        }
        
        return false;
    }
    
}