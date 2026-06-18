<?php

declare(strict_types=1);

use Lemonade\Image\AppImageFactory;
use Lemonade\Image\Generator\ImageRequest;
use Lemonade\Image\Http\ImageResponseEmitter;
use Lemonade\Image\ImageStorageConfig;
use Lemonade\Image\Utils\FileSystem;

require __DIR__ . '/../vendor/autoload.php';

$runtimeDirectory = dirname(__DIR__) . '/build/examples';

createPlaceholderImage(
    file: $runtimeDirectory . '/placeholder.png',
);

$factory = new AppImageFactory(
    filesystem: new FileSystem(),
    storageConfig: new ImageStorageConfig(
        storageRoot: $runtimeDirectory,
        storageDirectory: 'storage',
        cacheDirectory: 'cache',
        fallbackModuleId: '0',
        fallbackStorageTypeId: '0',
        placeholderImageFile: $runtimeDirectory . '/placeholder.png',
    ),
);

$request = ImageRequest::create(
    level: 6,
    storageTypeId: 'thumbnail',
    moduleId: 30,
    artId: 99999,
    baseName: 'missing-source.png',
    args: 'w600-h400-cffffff-q85',
);

$response = $factory
    ->createApplication(
        request: $request,
    )
    ->handle();

(new ImageResponseEmitter())->emit(
    response: $response,
);

/**
 * Creates a placeholder image used when the requested source image is missing.
 */
function createPlaceholderImage(string $file): void
{
    if (is_file($file)) {
        return;
    }

    $directory = dirname($file);

    if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
        throw new RuntimeException(sprintf('Unable to create directory "%s".', $directory));
    }

    $image = imagecreatetruecolor(
        width: 240,
        height: 160,
    );

    if ($image === false) {
        throw new RuntimeException('Unable to create placeholder image.');
    }

    imagesavealpha(
        image: $image,
        enable: true,
    );

    $transparent = imagecolorallocatealpha(
        image: $image,
        red: 255,
        green: 255,
        blue: 255,
        alpha: 127,
    );
    $border = imagecolorallocate(
        image: $image,
        red: 180,
        green: 180,
        blue: 180,
    );
    $text = imagecolorallocate(
        image: $image,
        red: 80,
        green: 80,
        blue: 80,
    );

    if ($transparent === false || $border === false || $text === false) {
        imagedestroy($image);

        throw new RuntimeException('Unable to allocate placeholder image colors.');
    }

    imagefill(
        image: $image,
        x: 0,
        y: 0,
        color: $transparent,
    );
    imagerectangle(
        image: $image,
        x1: 0,
        y1: 0,
        x2: 239,
        y2: 159,
        color: $border,
    );
    imagestring(
        image: $image,
        font: 5,
        x: 72,
        y: 72,
        string: 'Placeholder',
        color: $text,
    );

    imagepng(
        image: $image,
        file: $file,
    );
    imagedestroy($image);
}
