<?php declare(strict_types = 1);


namespace Lemonade\Image\Utils;
use Lemonade\Image\Traits\StaticTrait;
use Lemonade\Image\Exceptions\InvalidArgumentException;

/**
 * PHP callable tools.
 */
class Callback {
    
    use StaticTrait;

	/**
	 * @param  mixed   class, object, callable
	 * @param  string  method
	 * @return \Closure
	 */
	public static function closure($callable, $m = null) {
	    
		if ($m !== null) {
		    
			$callable = [$callable, $m];

		} elseif (is_string($callable) && count($tmp = explode('::', $callable)) === 2) {
			
		    $callable = $tmp;

		} elseif ($callable instanceof \Closure) {
			
		    return $callable;

		} elseif (is_object($callable)) {
			
		    $callable = [$callable, '__invoke'];
		}

		if (is_string($callable) && function_exists($callable)) {
			
		    return (new \ReflectionFunction($callable))->getClosure();

		} elseif (is_array($callable) && method_exists($callable[0], $callable[1])) {
			
		    return (new \ReflectionMethod($callable[0], $callable[1]))->getClosure(is_object($callable[0]) ? $callable[0] : null);		    
		}

		self::check($callable);
		$_callable_ = $callable;
		
		return function (...$args) use ($_callable_) {
			return $_callable_(...$args);
		};
	}

	/**
	 * Invokes internal PHP function with own error handler.
	 * @param  string
	 * @return mixed
	 */
	public static function invokeSafe($function, array $args, $onError) {
	    
		$prev = set_error_handler(function ($severity, $message, $file) use ($onError, &$prev, $function) {
		
		    if ($file === '' && defined('HHVM_VERSION')) { // https://github.com/facebook/hhvm/issues/4625
				$file = func_get_arg(5)[1]['file'];
			}
			
			if ($file === __FILE__) {
			
			    $msg = $message;
				
				if (ini_get('html_errors')) {
					$msg = html_entity_decode(strip_tags($msg));
				}
				
				$msg = preg_replace("#^$function\(.*?\): #", '', $msg);
				
				if ($onError($msg, $severity) !== false) {
					return;
				}
				
			}
			
			return $prev ? $prev(...func_get_args()) : false;
		});

		try {
		    
			return call_user_func_array($function, $args);
			
		} finally {
			
		    restore_error_handler();
		}
	}


	/**
	 * @return callable
	 */
	public static function check($callable, $syntax = false) {
	    
		if (!is_callable($callable, $syntax)) {
			
		    throw new InvalidArgumentException($syntax ? 'Given value is not a callable type.' : sprintf("Callback '%s' is not callable.", self::toString($callable)));
		}
		
		return $callable;
	}


	/**
	 * @return string
	 */
	public static function toString($callable) {
	    
		if ($callable instanceof \Closure) {
		    
			$inner = self::unwrap($callable);
			
			return '{closure' . ($inner instanceof \Closure ? '}' : ' ' . self::toString($inner) . '}');
			
		} elseif (is_string($callable) && $callable[0] === "\0") {
			
		    return '{lambda}';
		    
		} else {
			
		    is_callable(is_object($callable) ? [$callable, '__invoke'] : $callable, true, $textual);
			
		    return $textual;
		}
	}

	/**
	 * 
	 * @return \ReflectionMethod|\ReflectionFunction
	 */
	public static function toReflection($callable) {
	    
		if ($callable instanceof \Closure) {
			$callable = self::unwrap($callable);
		}

		
		if (is_string($callable) && strpos($callable, "::")) {
		    
		    return new \ReflectionMethod($callable);
			
		} elseif (is_array($callable)) {
			
		    return new \ReflectionMethod($callable[0], $callable[1]);
		    
		} elseif (is_object($callable) && !$callable instanceof \Closure) {
			
		    return new \ReflectionMethod($callable, "__invoke");
		    
		} else {
			
		    return new \ReflectionFunction($callable);
		}
	}


	/**
	 * @return bool
	 */
	public static function isStatic($callable) {
	    
		return is_array($callable) ? is_string($callable[0]) : is_string($callable);
	}

	/**
	 * Unwraps closure created by self::closure()
	 * @internal
	 * @return callable
	 */
	public static function unwrap(\Closure $closure) {
	    
		$r = new \ReflectionFunction($closure);
		
		if (substr($r->getName(), -1) === "}") {
		
		    $vars = $r->getStaticVariables();
		    
			return isset($vars["_callable_"]) ? $vars["_callable_"] : $closure;

		} elseif ($obj = $r->getClosureThis()) {
			
		    return [$obj, $r->getName()];

		} elseif ($class = $r->getClosureScopeClass()) {
			
		    return [$class->getName(), $r->getName()];

		} else {
			
		    return $r->getName();
		}
	}
}
