<?php declare(strict_types = 1);


namespace Lemonade\Image\Traits;
use Lemonade\Image\Utils\ObjectHelpers;
use Lemonade\Image\Exceptions\MemberAccessException;

/**
 * Static class.
 */
trait StaticTrait {

    /**
     * 
     * @throws \Error
     */
	final public function __construct() {
    
	    throw new \Error('Class ' . get_class($this) . ' is static and cannot be instantiated.');
	}
	
	/**
	 * Zavolat neexistujici statickou metodu
	 * @param string $name
	 * @param array $args
	 * @throws MemberAccessException
	 */
	public static function __callStatic(string $name, array $args) {
	    
	    ObjectHelpers::strictStaticCall(get_called_class(), $name);
	}
}
