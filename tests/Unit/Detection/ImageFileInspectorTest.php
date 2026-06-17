<?php

declare(strict_types=1);

namespace Lemonade\Image\Tests\Unit\Detection;

use Lemonade\Image\Detection\ImageFileInspector;
use PHPUnit\Framework\TestCase;

final class ImageFileInspectorTest extends TestCase
{
    public function testReturnsTrueWhenFileExists(): void
    {
        $file = $this->createTemporaryFile();

        $inspector = new ImageFileInspector();

        self::assertTrue(
            $inspector->exists(
                file: $file,
            ),
        );

        unlink($file);
    }

    public function testReturnsTrueWhenDirectoryExists(): void
    {
        $inspector = new ImageFileInspector();

        self::assertTrue(
            $inspector->exists(
                file: sys_get_temp_dir(),
            ),
        );
    }

    public function testReturnsFalseWhenFileDoesNotExist(): void
    {
        $inspector = new ImageFileInspector();

        self::assertFalse(
            $inspector->exists(
                file: sys_get_temp_dir() . '/lemonade-image-missing-file-' . uniqid('', true) . '.png',
            ),
        );
    }

    public function testReturnsFalseForNullPath(): void
    {
        $inspector = new ImageFileInspector();

        self::assertFalse(
            $inspector->exists(
                file: null,
            ),
        );
    }

    public function testReturnsFalseForEmptyPath(): void
    {
        $inspector = new ImageFileInspector();

        self::assertFalse(
            $inspector->exists(
                file: '',
            ),
        );
    }

    private function createTemporaryFile(): string
    {
        $file = tempnam(
            directory: sys_get_temp_dir(),
            prefix: 'lemonade-image-',
        );

        if ($file === false) {
            self::fail('Unable to create temporary file.');
        }

        file_put_contents(
            filename: $file,
            data: 'test',
        );

        return $file;
    }
}
