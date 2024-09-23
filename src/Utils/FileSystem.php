<?php declare(strict_types=1);


namespace Lemonade\Image\Utils;

use Lemonade\Image\Traits\StaticTrait;
use Lemonade\Image\Exceptions\IOException;
use Lemonade\Image\Exceptions\InvalidStateException;

/**
 * File system tool.
 */
class FileSystem {

    use StaticTrait;

	/**
	 * Vytvorit adresare
	 * @param mixed $dir
	 * @param number $mode
	 * @throws IOException
	 */
	public static function createDir( $dir, $mode = 0777) {
	    
		if (!is_dir($dir) && !@mkdir($dir, $mode, true) && !is_dir($dir)) { 
		    
			throw new IOException("Unable to create directory '$dir'. " . self::getLastError());
		}
	}


	/**
	 * Zkopirovat soubor do adresare
	 * @param mixed $source
	 * @param mixed $dest
	 * @param boolean $overwrite
	 * @throws IOException
	 * @throws InvalidStateException
	 */
	public static function copy($source, $dest, $overwrite = true) {
	    
		if (stream_is_local($source) && !file_exists($source)) {
		    
		    throw new IOException("File or directory '$source' not found.");

		} elseif (!$overwrite && file_exists($dest)) {
		    
			throw new InvalidStateException("File or directory '$dest' already exists.");

		} elseif (is_dir($source)) {
			
		    static::createDir($dest);
			
			foreach (new \FilesystemIterator($dest) as $item) {
			    
				static::delete($item->getPathname());
			}
			
			foreach ($iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST) as $item) {
				
			    if ($item->isDir()) {
					
			        static::createDir($dest . '/' . $iterator->getSubPathName());
					
				} else {
					
				    static::copy($item->getPathname(), $dest . '/' . $iterator->getSubPathName());
				}
			}

		} else {
		    
			static::createDir(dirname($dest));
			
			if (@stream_copy_to_stream(fopen($source, 'r'), fopen($dest, 'w')) === false) { 
			    
			    throw new IOException("Unable to copy file '$source' to '$dest'. " . self::getLastError());
			}
		}
	}

	/**
	 * Smazat soubor/adresar
	 * @param mixed $path
	 * @throws IOException
	 * @return void
	 */
	public static function delete($path) {
	    
		if (is_file($path) || is_link($path)) {
		
		    $func = DIRECTORY_SEPARATOR === '\\' && is_dir($path) ? 'rmdir' : 'unlink';
			
			if (!@$func($path)) {
			    
				throw new IOException("Unable to delete '$path'. " . self::getLastError());
			}

		} elseif (is_dir($path)) {
		    
			foreach (new \FilesystemIterator($path) as $item) {
			    
				static::delete($item->getPathname());
			}
			
			if (!@rmdir($path)) {
			    
				throw new IOException("Unable to delete directory '$path'. " . self::getLastError());
			}
		}
	}

	/**
	 * Prejmenovat soubor/adresar
	 * @param mixed $name
	 * @param mixed $newName
	 * @param boolean $overwrite
	 * @throws InvalidStateException
	 * @throws IOException
	 */
	public static function rename($name, $newName, $overwrite = true) {
	    
		if (!$overwrite && file_exists($newName)) {
		    
			throw new InvalidStateException("File or directory '$newName' already exists.");

		} elseif (!file_exists($name)) {
		    
			throw new IOException("File or directory '$name' not found.");

		} else {
			
		    static::createDir(dirname($newName));
			
			if (realpath($name) !== realpath($newName)) {
			
			    static::delete($newName);
			}
			
			if (!@rename($name, $newName)) {
			    
				throw new IOException("Unable to rename file or directory '$name' to '$newName'. " . self::getLastError());
			}
		}
	}


	/**
	 * Precist soubor
	 * 
	 * @param string $file
	 * @throws IOException
	 * @return string
	 */
	public static function read(string $file) {
	    
		$content = @file_get_contents($file); 
		
		if ($content === false) {
		    
			throw new IOException("Unable to read file '$file'. " . self::getLastError());
		}
		
		return $content;
	}

	/**
	 * Zapsat do souboru
	 * 
	 * @param string $file
	 * @param mixed $content
	 * @param number $mode
	 * @throws IOException
	 */
	public static function write(string $file, $content, $mode = 0666) {
	    
		static::createDir(dirname($file));
		
		if (@file_put_contents($file, $content) === false) { 
		    
		    throw new IOException("Unable to write file '$file'. " . self::getLastError());
		}
		
		if ($mode !== null && !@chmod($file, $mode)) {
		    
			throw new IOException("Unable to chmod file '$file'. " . self::getLastError());
		}
	}


	/**
	 * Absolutni cesta
	 * 
	 * @return bool
	 */
	public static function isAbsolute($path) {
	    
		return (bool) preg_match('#([a-z]:)?[/\\\\]|[a-z][a-z0-9+.-]*://#Ai', $path);
	}


	/**
	 * 
	 * @return mixed
	 */
	private static function getLastError() {
	    
		return preg_replace('#^\w+\(.*?\): #', '', error_get_last()['message']);
	}
}
