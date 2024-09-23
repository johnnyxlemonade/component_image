<?php declare(strict_types = 1);


namespace Lemonade\Image\Traits;

use Lemonade\Image\Utils\Callback;
use Lemonade\Image\Utils\ObjectHelpers;
use Lemonade\Image\Utils\ObjectMixin;
use Lemonade\Image\Exceptions\MemberAccessException;
use Lemonade\Image\Exceptions\InvalidArgumentException;
use Lemonade\Image\Exceptions\UnexpectedValueException;


/**
 * Strict class for better experience.
 * - 'did you mean' hints
 * - access to undeclared members throws exceptions
 * - support for @property annotations
 * - support for calling event handlers stored in $onEvent via onEvent()
 */
trait ObjectTrait {

	/**
	 * @return mixed
	 * @throws MemberAccessException
	 */
    public function __call(string $name, array $args) {
        
        $class = static::class;
        
        if (ObjectHelpers::hasProperty($class, $name) === 'event') { // calling event handlers
                        
            $handlers = $this->$name ?? null;
            
            if (static::_is_iterable($handlers)) {
                foreach ($handlers as $handler) {
                
                    $handler(...$args);
                    
                }
                
            } elseif ($handlers !== null) {
                
                throw new UnexpectedValueException("Property $class::$$name must be iterable or null, " . gettype($handlers) . ' given.');
            }
            
            return null;
        }
        
        ObjectHelpers::strictCall($class, $name);
    }


	/**
	 * @return void
	 * @throws MemberAccessException
	 */
	public static function __callStatic($name, $args) {
	    
		ObjectHelpers::strictStaticCall(get_called_class(), $name);
	}


	/**
	 * @return mixed   property value
	 * @throws MemberAccessException if the property is not defined.
	 */
	public function &__get($name) {
	    
	    $class = static::class;
	    
	    if ($prop = ObjectHelpers::getMagicProperties($class)[$name] ?? null) { // property getter
	        
	        if (!($prop & 0b0001)) {
	            throw new MemberAccessException("Cannot read a write-only property $class::\$$name.");
	        }
	        
	        $m = ($prop & 0b0010 ? 'get' : 'is') . ucfirst($name);
	        
	        if ($prop & 0b10000) {
	            
	            $trace = debug_backtrace(0, 1)[0]; // suppose this method is called from __call()
	            $loc = isset($trace['file'], $trace['line']) ? " in $trace[file] on line $trace[line]" : '';
	            
	            trigger_error("Property $class::\$$name is deprecated, use $class::$m() method$loc.", E_USER_DEPRECATED);
	        }
	        
	        if ($prop & 0b0100) { // return by reference
	            
	            return $this->$m();
	            
	        } else {
	        
	            $val = $this->$m();
	            return $val;
	        }
	        
	    } else {
	        
	        ObjectHelpers::strictGet($class, $name);
	    }
	}


	/**
	 * @return void
	 * @throws MemberAccessException if the property is not defined or is read-only
	 */
	public function __set($name, $value) {
	    
	    $class = static::class;
	    
	    if (ObjectHelpers::hasProperty($class, $name)) { // unsetted property
	        
	        $this->$name = $value;
	        
	    } elseif ($prop = ObjectHelpers::getMagicProperties($class)[$name] ?? null) { // property setter
	        
	        if (!($prop & 0b1000)) {
	            throw new MemberAccessException("Cannot write to a read-only property $class::\$$name.");
	        }
	        
	        $m = 'set' . ucfirst($name);
	        
	        if ($prop & 0b10000) {
	            
	            $trace = debug_backtrace(0, 1)[0]; // suppose this method is called from __call()
	            $loc = isset($trace['file'], $trace['line']) ? " in $trace[file] on line $trace[line]" : '';
	            
	            trigger_error("Property $class::\$$name is deprecated, use $class::$m() method$loc.", E_USER_DEPRECATED);
	        }
	        
	        $this->$m($value);
	        
	    } else {
	        
	        ObjectHelpers::strictSet($class, $name);
	    }
	}


	/**
	 * @return void
	 * @throws MemberAccessException
	 */
	public function __unset(string $name) {
	    
	    $class = static::class;
	    
	    if (!ObjectHelpers::hasProperty($class, $name)) {
	    
	        throw new MemberAccessException("Cannot unset the property $class::\$$name.");
	    }
	    
	}

	/**
	 * 
	 * @param string $name
	 * @return bool
	 */
	public function __isset(string $name): bool {
	    
	    return isset(ObjectHelpers::getMagicProperties(static::class)[$name]);
	}

	
	
	/**
	 * Verify that the contents of a variable is an iterable value
	 * PHP 7.1.0
	 * @param mixed $obj
	 * @return boolean
	 */
	private static function _is_iterable($obj): bool {
	    
	    return is_array($obj) || (is_object($obj) && ($obj instanceof \Traversable));	    
	}

}
