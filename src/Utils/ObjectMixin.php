<?php declare(strict_types = 1);


namespace Lemonade\Image\Utils;
use Lemonade\Image\Traits\ObjectTrait;
use Lemonade\Image\Exceptions\MemberAccessException;
use Lemonade\Image\Exceptions\UnexpectedValueException;
use Lemonade\Image\Exceptions\InvalidArgumentException;

/**
 * Nette\Object behaviour mixin.
 */
class ObjectMixin {
    
    use ObjectTrait;

	/** @var array [name => [type => callback]] used by extension methods */
	private static $extMethods = [];


	/**
	 * __call() implementation.
	 * @param  object
	 * @param  string
	 * @param  array
	 * @return mixed
	 * @throws MemberAccessException
	 */
	public static function call($_this, $name, $args)
	{
		trigger_error('Class Nette\Utils\ObjectMixin is deprecated', E_USER_DEPRECATED);
		$class = get_class($_this);
		$isProp = ObjectHelpers::hasProperty($class, $name);

		if ($name === '') {
		    
			throw new MemberAccessException("Call to class '$class' method without name.");

		} elseif ($isProp === 'event') { // calling event handlers
			
		    if (is_array($_this->$name) || $_this->$name instanceof \Traversable) {
				foreach ($_this->$name as $handler) {
					Callback::invokeArgs($handler, $args);
				}
				
			} elseif ($_this->$name !== null) {
			    
				throw new UnexpectedValueException("Property $class::$$name must be array or null, " . gettype($_this->$name) . ' given.');
			}

		} elseif ($isProp && $_this->$name instanceof \Closure) { // closure in property
		    
			return call_user_func_array($_this->$name, $args);

		} elseif (($methods = &self::getMethods($class)) && isset($methods[$name]) && is_array($methods[$name])) { // magic @methods
			
		    list($op, $rp, $type) = $methods[$name];
			
			if (count($args) !== ($op === 'get' ? 0 : 1)) {
			
			    throw new InvalidArgumentException("$class::$name() expects " . ($op === 'get' ? 'no' : '1') . ' argument, ' . count($args) . ' given.');

			} elseif ($type && $args && !self::checkType($args[0], $type)) {
			    
			    throw new InvalidArgumentException("Argument passed to $class::$name() must be $type, " . gettype($args[0]) . ' given.');
			}

			if ($op === 'get') {
				
			    return $rp->getValue($_this);
				
			} elseif ($op === 'set') {
			    
				$rp->setValue($_this, $args[0]);
				
			} elseif ($op === 'add') {
			    
				$val = $rp->getValue($_this);
				$val[] = $args[0];
				$rp->setValue($_this, $val);
			}
			
			return $_this;

		} elseif ($cb = self::getExtensionMethod($class, $name)) { // extension methods
			
		    return Callback::invoke($cb, $_this, ...$args);

		} else {
			ObjectHelpers::strictCall($class, $name, array_keys(self::getExtensionMethods($class)));
		}
	}



	/**
	 * __get() implementation.
	 * @param  object
	 * @param  string  property name
	 * @return mixed   property value
	 * @throws MemberAccessException if the property is not defined.
	 */
	public static function &get($_this, $name)
	{
		$class = get_class($_this);
		$uname = ucfirst($name);
		$methods = &self::getMethods($class);

		if ($name === '') {
			throw new MemberAccessException("Cannot read a class '$class' property without name.");

		} elseif (isset($methods[$m = 'get' . $uname]) || isset($methods[$m = 'is' . $uname])) { // property getter
			if ($methods[$m] === 0) {
				$methods[$m] = (new \ReflectionMethod($class, $m))->returnsReference();
			}
			if ($methods[$m] === true) {
				return $_this->$m();
			} else {
				$val = $_this->$m();
				return $val;
			}

		} elseif (isset($methods[$name])) { // public method as closure getter
			if (preg_match('#^(is|get|has)([A-Z]|$)#', $name) && !(new \ReflectionMethod($class, $name))->getNumberOfRequiredParameters()) {
				trigger_error("Did you forget parentheses after $name" . self::getSource() . '?', E_USER_WARNING);
			}
			$val = Callback::closure($_this, $name);
			return $val;

		} elseif (isset($methods['set' . $uname])) { // property getter
			throw new MemberAccessException("Cannot read a write-only property $class::\$$name.");

		} else {
			ObjectHelpers::strictGet($class, $name);
		}
	}



	/********************* magic @properties ****************d*g**/

	/** @internal */
	public static function getMagicProperty($class, $name)
	{
		$props = ObjectHelpers::getMagicProperties($class);
		return isset($props[$name]) ? $props[$name] : null;
	}


	/********************* magic @methods ****************d*g**/


	/**
	 * Returns array of magic methods defined by annotation @method.
	 * @return array
	 */
	public static function getMagicMethods($class)
	{
		trigger_error('Class Nette\Utils\ObjectMixin is deprecated', E_USER_DEPRECATED);
		$rc = new \ReflectionClass($class);
		preg_match_all('~^
			[ \t*]*  @method  [ \t]+
			(?: [^\s(]+  [ \t]+ )?
			(set|get|is|add)  ([A-Z]\w*)
			(?: ([ \t]* \()  [ \t]* ([^)$\s]*)  )?
		()~mx', (string) $rc->getDocComment(), $matches, PREG_SET_ORDER);

		$methods = [];
		foreach ($matches as list(, $op, $prop, $bracket, $type)) {
			if ($bracket !== '(') {
				trigger_error("Bracket must be immediately after @method $op$prop() in class $class.", E_USER_WARNING);
			}
			$name = $op . $prop;
			$prop = strtolower($prop[0]) . substr($prop, 1) . ($op === 'add' ? 's' : '');
			if ($rc->hasProperty($prop) && ($rp = $rc->getProperty($prop)) && !$rp->isStatic()) {
				$rp->setAccessible(true);
				if ($op === 'get' || $op === 'is') {
					$type = null;
					$op = 'get';
				} elseif (!$type && preg_match('#@var[ \t]+(\S+)' . ($op === 'add' ? '\[\]#' : '#'), (string) $rp->getDocComment(), $m)) {
					$type = $m[1];
				}
				if ($rc->inNamespace() && preg_match('#^[A-Z]\w+(\[|\||\z)#', (string) $type)) {
					$type = $rc->getNamespaceName() . '\\' . $type;
				}
				$methods[$name] = [$op, $rp, $type];
			}
		}
		return $methods;
	}


	/**
	 * Finds whether a variable is of expected type and do non-data-loss conversion.
	 * @return bool
	 * @internal
	 */
	public static function checkType(&$val, $type)
	{
		trigger_error('Class Nette\Utils\ObjectMixin is deprecated', E_USER_DEPRECATED);
		if (strpos($type, '|') !== false) {
			$found = null;
			foreach (explode('|', $type) as $type) {
				$tmp = $val;
				if (self::checkType($tmp, $type)) {
					if ($val === $tmp) {
						return true;
					}
					$found[] = $tmp;
				}
			}
			if ($found) {
				$val = $found[0];
				return true;
			}
			return false;

		} elseif (substr($type, -2) === '[]') {
			if (!is_array($val)) {
				return false;
			}
			$type = substr($type, 0, -2);
			$res = [];
			foreach ($val as $k => $v) {
				if (!self::checkType($v, $type)) {
					return false;
				}
				$res[$k] = $v;
			}
			$val = $res;
			return true;
		}

		switch (strtolower($type)) {
			case null:
			case 'mixed':
				return true;
			case 'bool':
			case 'boolean':
				return ($val === null || is_scalar($val)) && settype($val, 'bool');
			case 'string':
				return ($val === null || is_scalar($val) || (is_object($val) && method_exists($val, '__toString'))) && settype($val, 'string');
			case 'int':
			case 'integer':
				return ($val === null || is_bool($val) || is_numeric($val)) && ((float) (int) $val === (float) $val) && settype($val, 'int');
			case 'float':
				return ($val === null || is_bool($val) || is_numeric($val)) && settype($val, 'float');
			case 'scalar':
			case 'array':
			case 'object':
			case 'callable':
			case 'resource':
			case 'null':
				return call_user_func("is_$type", $val);
			default:
				return $val instanceof $type;
		}
	}


	/********************* extension methods ****************d*g**/


	/**
	 * Adds a method to class.
	 * @param  string
	 * @param  string
	 * @param  mixed   callable
	 * @return void
	 */
	public static function setExtensionMethod($class, $name, $callback)
	{
		$name = strtolower($name);
		self::$extMethods[$name][$class] = Callback::check($callback);
		self::$extMethods[$name][''] = null;
	}


	/**
	 * Returns extension method.
	 * @param  string
	 * @param  string
	 * @return mixed
	 */
	public static function getExtensionMethod($class, $name)
	{
		$list = &self::$extMethods[strtolower($name)];
		$cache = &$list[''][$class];
		if (isset($cache)) {
			return $cache;
		}

		foreach ([$class] + class_parents($class) + class_implements($class) as $cl) {
			if (isset($list[$cl])) {
				return $cache = $list[$cl];
			}
		}
		return $cache = false;
	}

	/********************* utilities ****************d*g**/

	/**
	 * Returns array of public (static, non-static and magic) methods.
	 * @return array
	 * @internal
	 */
	public static function &getMethods($class)
	{
		static $cache;
		if (!isset($cache[$class])) {
			$cache[$class] = array_fill_keys(get_class_methods($class), 0) + @self::getMagicMethods($class); // is deprecated
			if ($parent = get_parent_class($class)) {
				$cache[$class] += self::getMethods($parent);
			}
		}
		return $cache[$class];
	}


	/** @internal */
	public static function getSource()
	{
		foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS) as $item) {
			if (isset($item['file']) && dirname($item['file']) !== __DIR__) {
				return " in $item[file]:$item[line]";
			}
		}
	}
}
