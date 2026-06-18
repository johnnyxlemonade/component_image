<?php

declare(strict_types=1);

use Lemonade\Image\AppImageFactory;
use Lemonade\Image\Generator\ImageRequest;
use Lemonade\Image\Http\ImageResponseEmitter;
use Lemonade\Image\ImageStorageConfig;
use Lemonade\Image\Utils\FileSystem;

require __DIR__ . '/../vendor/autoload.php';

$runtimeDirectory = dirname(__DIR__) . '/build/examples';

createExampleSourceImage(
    file: $runtimeDirectory . '/storage/20/2/00/d4/31/gallery-image.png',
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
    storageTypeId: 'gallery',
    moduleId: 20,
    artId: 54321,
    baseName: 'gallery-image.png',
    args: 'w480-h320-z2-cf7f7f7-q90',
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
 * Creates a small demo source image for the example.
 */
function createExampleSourceImage(string $file): void
{
    if (is_file($file)) {
        return;
    }

    $directory = dirname($file);

    if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
        throw new RuntimeException(sprintf('Unable to create directory "%s".', $directory));
    }

    $image = imagecreatetruecolor(
        width: 900,
        height: 600,
    );

    if ($image === false) {
        throw new RuntimeException('Unable to create demo image.');
    }

    $background = imagecolorallocate(
        image: $image,
        red: 250,
        green: 250,
        blue: 250,
    );
    $accent = imagecolorallocate(
        image: $image,
        red: 80,
        green: 120,
        blue: 60,
    );
    $text = imagecolorallocate(
        image: $image,
        red: 20,
        green: 20,
        blue: 20,
    );

    if ($background === false || $accent === false || $text === false) {
        imagedestroy($image);

        throw new RuntimeException('Unable to allocate demo image colors.');
    }

    imagefill(
        image: $image,
        x: 0,
        y: 0,
        color: $background,
    );
    imagefilledellipse(
        image: $image,
        center_x: 450,
        center_y: 300,
        width: 620,
        height: 360,
        color: $accent,
    );
    imagestring(
        image: $image,
        font: 5,
        x: 365,
        y: 290,
        string: 'Custom config',
        color: $text,
    );

    imagepng(
        image: $image,
        file: $file,
    );
    imagedestroy($image);
}
