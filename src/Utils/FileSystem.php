<?php

declare(strict_types=1);

namespace Lemonade\Image\Utils;

use FilesystemIterator;
use Lemonade\Image\Exceptions\Filesystem\DirectoryCreateException;
use Lemonade\Image\Exceptions\Filesystem\FileCopyException;
use Lemonade\Image\Exceptions\Filesystem\FileDeleteException;
use Lemonade\Image\Exceptions\Filesystem\FileLockException;
use Lemonade\Image\Exceptions\Filesystem\FileNotFoundException;
use Lemonade\Image\Exceptions\Filesystem\FileOpenException;
use Lemonade\Image\Exceptions\Filesystem\FilePermissionException;
use Lemonade\Image\Exceptions\Filesystem\FileReadException;
use Lemonade\Image\Exceptions\Filesystem\FileRenameException;
use Lemonade\Image\Exceptions\Filesystem\FileWriteException;
use Lemonade\Image\Exceptions\InvalidStateException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Provides filesystem operations with component-specific exceptions.
 *
 * Wraps common file and directory actions used by the image cache layer.
 *
 * @package     Lemonade
 * @subpackage  Image\Utils
 * @category    Utility
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
 */
final class FileSystem
{
    public function createDir(string $dir, int $mode = 0777): void
    {
        if (!is_dir($dir) && !@mkdir($dir, $mode, true) && !is_dir($dir)) {
            throw DirectoryCreateException::forPath($dir, $this->getLastError());
        }
    }

    public function createDirForFile(string $file, int $mode = 0777): void
    {
        $this->createDir(dirname($file), $mode);
    }

    public function copy(string $source, string $dest, bool $overwrite = true): void
    {
        if (stream_is_local($source) && !file_exists($source)) {
            throw FileNotFoundException::forPath($source);
        }

        if (!$overwrite && file_exists($dest)) {
            throw new InvalidStateException("File or directory '$dest' already exists.");
        }

        if (is_dir($source)) {
            $this->createDir($dest);

            foreach ($this->iterateDirectory($dest) as $item) {
                $this->delete($item->getPathname());
            }

            $base = rtrim($source, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

            foreach ($this->iterateRecursive($source) as $item) {
                if ($item->isLink()) {
                    continue;
                }

                $relativePath = substr($item->getPathname(), strlen($base));
                $target = $dest . '/' . $relativePath;

                if ($item->isDir()) {
                    $this->createDir($target);
                } else {
                    $this->copy($item->getPathname(), $target);
                }
            }

            return;
        }

        $this->createDir(dirname($dest));

        $in = $this->open($source, 'r');
        $out = $this->open($dest, 'w');

        if (stream_copy_to_stream($in, $out) === false) {
            fclose($in);
            fclose($out);

            throw FileCopyException::fromTo($source, $dest, $this->getLastError());
        }

        fclose($in);
        fclose($out);
    }

    public function delete(string $path): void
    {
        if (is_link($path) || is_file($path)) {
            $result = false;

            if (DIRECTORY_SEPARATOR === '\\' && is_dir($path)) {
                $result = @rmdir($path);
            } else {
                $result = @unlink($path);
            }

            if (!$result) {
                clearstatcache(true, $path);

                if (file_exists($path)) {
                    throw FileDeleteException::forPath($path, $this->getLastError());
                }
            }

            return;
        }

        if (is_dir($path)) {
            foreach ($this->iterateDirectory($path) as $item) {
                $this->delete($item->getPathname());
            }

            if (!@rmdir($path)) {
                clearstatcache(true, $path);

                if (is_dir($path)) {
                    throw FileDeleteException::forPath($path, $this->getLastError());
                }
            }
        }
    }

    public function rename(string $name, string $newName, bool $overwrite = true): void
    {
        if (!$overwrite && file_exists($newName)) {
            throw new InvalidStateException("File or directory '$newName' already exists.");
        }

        if (!file_exists($name)) {
            throw FileNotFoundException::forPath($name);
        }

        $this->createDir(dirname($newName));

        if (realpath($name) !== realpath($newName) && file_exists($newName)) {
            $this->delete($newName);
        }

        if (!@rename($name, $newName)) {
            throw FileRenameException::fromTo($name, $newName, $this->getLastError());
        }
    }

    public function read(string $file): string
    {
        $handle = $this->open($file, 'rb');

        if (!flock($handle, LOCK_SH)) {
            fclose($handle);

            throw FileLockException::forPath($file);
        }

        try {
            $content = '';

            while (!feof($handle)) {
                $chunk = fread($handle, 8192);

                if ($chunk === false) {
                    throw FileReadException::forPath($file, $this->getLastError());
                }

                $content .= $chunk;
            }

            return $content;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    public function write(string $file, string|\Stringable $content, ?int $mode = 0666): void
    {
        $dir = dirname($file);
        $this->createDir($dir);

        $tmpFile = $dir . '/.' . basename($file) . '.' . uniqid('', true) . '.tmp';

        $handle = $this->open($tmpFile, 'wb');

        $data = is_string($content) ? $content : (string) $content;
        $length = strlen($data);
        $written = 0;

        while ($written < $length) {
            $result = fwrite($handle, substr($data, $written, 8192));

            if ($result === false) {
                fclose($handle);
                @unlink($tmpFile);

                throw FileWriteException::forPath($file, $this->getLastError());
            }

            $written += $result;
        }

        fflush($handle);
        fclose($handle);

        if ($mode !== null && !@chmod($tmpFile, $mode)) {
            @unlink($tmpFile);

            throw FilePermissionException::forPath($tmpFile, 'chmod', $this->getLastError());
        }

        $attempts = 3;

        while ($attempts-- > 0) {
            if (@rename($tmpFile, $file)) {
                if ($mode !== null && !@chmod($file, $mode)) {
                    throw FilePermissionException::forPath($file, 'chmod', $this->getLastError());
                }

                return;
            }

            usleep(50000);
        }

        @unlink($tmpFile);

        throw FileRenameException::fromTo($tmpFile, $file, $this->getLastError());
    }

    public function isAbsolute(string $path): bool
    {
        return (bool) preg_match('#([a-z]:)?[/\\\\]|[a-z][a-z0-9+.-]*://#Ai', $path);
    }

    private function getLastError(): string
    {
        $error = error_get_last();

        if ($error === null) {
            return '';
        }

        $message = preg_replace('#^\w+\(.*?\): #', '', $error['message']);

        if ($message === null) {
            return '';
        }

        return $message;
    }

    /**
     * @return resource
     */
    private function open(string $file, string $mode)
    {
        $handle = fopen($file, $mode);

        if ($handle === false) {
            throw FileOpenException::forPath($file, $mode, $this->getLastError());
        }

        return $handle;
    }

    /**
     * @return iterable<SplFileInfo>
     */
    private function iterateDirectory(string $path): iterable
    {
        foreach (new FilesystemIterator(
                     $path,
                     FilesystemIterator::SKIP_DOTS | FilesystemIterator::CURRENT_AS_FILEINFO
                 ) as $item) {
            /** @var SplFileInfo $item */
            yield $item;
        }
    }

    /**
     * @return iterable<SplFileInfo>
     */
    private function iterateRecursive(string $path): iterable
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $path,
                RecursiveDirectoryIterator::SKIP_DOTS | FilesystemIterator::CURRENT_AS_FILEINFO
            ),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            /** @var SplFileInfo $item */
            yield $item;
        }
    }
}
