<?php

declare(strict_types=1);

namespace Lemonade\Image\Tests\Unit\Utils;

use Lemonade\Image\Exceptions\Filesystem\FileNotFoundException;
use Lemonade\Image\Exceptions\InvalidStateException;
use Lemonade\Image\Utils\FileSystem;
use PHPUnit\Framework\TestCase;
use Stringable;

final class FileSystemTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lemonade-filesystem-test-' . uniqid('', true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory(
            directory: $this->root,
        );
    }

    public function testCreatesDirectory(): void
    {
        $filesystem = new FileSystem();

        $directory = $this->root . DIRECTORY_SEPARATOR . 'a' . DIRECTORY_SEPARATOR . 'b';

        $filesystem->createDir(
            dir: $directory,
        );

        self::assertDirectoryExists($directory);
    }

    public function testCreatesDirectoryForFile(): void
    {
        $filesystem = new FileSystem();

        $file = $this->root . DIRECTORY_SEPARATOR . 'a' . DIRECTORY_SEPARATOR . 'b' . DIRECTORY_SEPARATOR . 'image.png';

        $filesystem->createDirForFile(
            file: $file,
        );

        self::assertDirectoryExists(dirname($file));
    }

    public function testWritesAndReadsFile(): void
    {
        $filesystem = new FileSystem();

        $file = $this->root . DIRECTORY_SEPARATOR . 'nested' . DIRECTORY_SEPARATOR . 'file.txt';

        $filesystem->write(
            file: $file,
            content: 'hello',
        );

        self::assertFileExists($file);
        self::assertSame(
            'hello',
            $filesystem->read(
                file: $file,
            ),
        );
    }

    public function testWritesStringableContent(): void
    {
        $filesystem = new FileSystem();

        $file = $this->root . DIRECTORY_SEPARATOR . 'stringable.txt';

        $filesystem->write(
            file: $file,
            content: new class implements Stringable {
                public function __toString(): string
                {
                    return 'stringable content';
                }
            },
        );

        self::assertSame(
            'stringable content',
            $filesystem->read(
                file: $file,
            ),
        );
    }

    public function testCopiesFile(): void
    {
        $filesystem = new FileSystem();

        $source = $this->root . DIRECTORY_SEPARATOR . 'source.txt';
        $destination = $this->root . DIRECTORY_SEPARATOR . 'nested' . DIRECTORY_SEPARATOR . 'destination.txt';

        $filesystem->write(
            file: $source,
            content: 'copied',
        );

        $filesystem->copy(
            source: $source,
            dest: $destination,
        );

        self::assertSame(
            'copied',
            $filesystem->read(
                file: $destination,
            ),
        );
    }

    public function testCopyThrowsWhenSourceFileIsMissing(): void
    {
        $filesystem = new FileSystem();

        $this->expectException(FileNotFoundException::class);

        $filesystem->copy(
            source: $this->root . DIRECTORY_SEPARATOR . 'missing.txt',
            dest: $this->root . DIRECTORY_SEPARATOR . 'destination.txt',
        );
    }

    public function testCopyWithoutOverwriteThrowsWhenDestinationExists(): void
    {
        $filesystem = new FileSystem();

        $source = $this->root . DIRECTORY_SEPARATOR . 'source.txt';
        $destination = $this->root . DIRECTORY_SEPARATOR . 'destination.txt';

        $filesystem->write(
            file: $source,
            content: 'source',
        );
        $filesystem->write(
            file: $destination,
            content: 'destination',
        );

        $this->expectException(InvalidStateException::class);

        $filesystem->copy(
            source: $source,
            dest: $destination,
            overwrite: false,
        );
    }

    public function testRenamesFile(): void
    {
        $filesystem = new FileSystem();

        $source = $this->root . DIRECTORY_SEPARATOR . 'source.txt';
        $destination = $this->root . DIRECTORY_SEPARATOR . 'nested' . DIRECTORY_SEPARATOR . 'renamed.txt';

        $filesystem->write(
            file: $source,
            content: 'renamed',
        );

        $filesystem->rename(
            name: $source,
            newName: $destination,
        );

        self::assertFileDoesNotExist($source);
        self::assertSame(
            'renamed',
            $filesystem->read(
                file: $destination,
            ),
        );
    }

    public function testRenameThrowsWhenSourceFileIsMissing(): void
    {
        $filesystem = new FileSystem();

        $this->expectException(FileNotFoundException::class);

        $filesystem->rename(
            name: $this->root . DIRECTORY_SEPARATOR . 'missing.txt',
            newName: $this->root . DIRECTORY_SEPARATOR . 'renamed.txt',
        );
    }

    public function testRenameWithoutOverwriteThrowsWhenDestinationExists(): void
    {
        $filesystem = new FileSystem();

        $source = $this->root . DIRECTORY_SEPARATOR . 'source.txt';
        $destination = $this->root . DIRECTORY_SEPARATOR . 'destination.txt';

        $filesystem->write(
            file: $source,
            content: 'source',
        );
        $filesystem->write(
            file: $destination,
            content: 'destination',
        );

        $this->expectException(InvalidStateException::class);

        $filesystem->rename(
            name: $source,
            newName: $destination,
            overwrite: false,
        );
    }

    public function testDeletesFile(): void
    {
        $filesystem = new FileSystem();

        $file = $this->root . DIRECTORY_SEPARATOR . 'file.txt';

        $filesystem->write(
            file: $file,
            content: 'delete me',
        );

        self::assertFileExists($file);

        $filesystem->delete(
            path: $file,
        );

        self::assertFileDoesNotExist($file);
    }

    public function testDeletesDirectoryRecursively(): void
    {
        $filesystem = new FileSystem();

        $file = $this->root . DIRECTORY_SEPARATOR . 'a' . DIRECTORY_SEPARATOR . 'b' . DIRECTORY_SEPARATOR . 'file.txt';

        $filesystem->write(
            file: $file,
            content: 'delete me',
        );

        self::assertDirectoryExists($this->root);

        $filesystem->delete(
            path: $this->root,
        );

        self::assertDirectoryDoesNotExist($this->root);
    }

    public function testDeleteIgnoresMissingPath(): void
    {
        $filesystem = new FileSystem();

        $missingPath = $this->root . DIRECTORY_SEPARATOR . 'missing';

        $filesystem->delete(
            path: $missingPath,
        );

        self::assertDirectoryDoesNotExist($this->root);
    }

    public function testDetectsAbsolutePaths(): void
    {
        $filesystem = new FileSystem();

        self::assertTrue(
            $filesystem->isAbsolute(
                path: '/tmp/lemonade',
            ),
        );
        self::assertTrue(
            $filesystem->isAbsolute(
                path: 'C:\\tmp\\lemonade',
            ),
        );
        self::assertTrue(
            $filesystem->isAbsolute(
                path: 'phar://archive/file.txt',
            ),
        );
        self::assertFalse(
            $filesystem->isAbsolute(
                path: 'storage/cache/file.png',
            ),
        );
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = scandir($directory);

        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $item;

            if (is_dir($path) && !is_link($path)) {
                $this->removeDirectory(
                    directory: $path,
                );

                continue;
            }

            if (is_file($path) || is_link($path)) {
                unlink($path);
            }
        }

        rmdir($directory);
    }
}
