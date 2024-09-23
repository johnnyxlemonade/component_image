<?php declare(strict_types = 1);


namespace Lemonade\Image\Traits;

trait MixedTrait {
    
    
    /**
     * String end with (PHP8 symfony polyfill)
     *
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    public static function str_ends_with(string $haystack, string $needle): bool {
        
        return "" === $needle || ("" !== $haystack && 0 === substr_compare($haystack, $needle, -\strlen($needle)));        
    }
    
    /**
     * Executes a callback and returns the captured output as a string.
     *
     * @param callable $func
     * @throws \Throwable
     * @return string
     */
    public static function capture(callable $func): string {
        
        ob_start(function () {});
        
        try {
            
            $func();
            
            return ob_get_clean();
            
        } catch (\Throwable $e) {
            
            ob_end_clean();
            
            throw $e;
        }
    }
    
    
    
    /**
     * Returns the last occurred PHP error or an empty string if no error occurred. Unlike error_get_last(),
     * it is nit affected by the PHP directive html_errors and always returns text, not HTML.
     */
    public static function getLastError(): string {
        
        $message = error_get_last()['message'] ?? '';
        $message = ini_get('html_errors') ? html_entity_decode(strip_tags($message), ENT_QUOTES | ENT_HTML5, 'UTF-8') : $message;
        $message = preg_replace('#^\w+\(.*?\): #', '', $message);
        
        return $message;
    }
    
}