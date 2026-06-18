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
    file: $runtimeDirectory . '/storage/10/1/00/30/39/example.png',
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
    moduleId: 10,
    artId: 12345,
    baseName: 'example.png',
    args: 'w320-h240-z1-cffffff-q85',
);

$response = $factory
    ->createApplication(
        request: $request,
    )
    ->handle();

(new ImageResponseEmitter())->emit(
    response: $response,
);

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
        width: 800,
        height: 500,
    );

    if ($image === false) {
        throw new RuntimeException('Unable to create demo image.');
    }

    $background = imagecolorallocate(
        image: $image,
        red: 245,
        green: 245,
        blue: 245,
    );
    $accent = imagecolorallocate(
        image: $image,
        red: 40,
        green: 90,
        blue: 160,
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
    imagefilledrectangle(
        image: $image,
        x1: 80,
        y1: 80,
        x2: 720,
        y2: 420,
        color: $accent,
    );
    imagestring(
        image: $image,
        font: 5,
        x: 300,
        y: 235,
        string: 'Lemonade Image',
        color: $text,
    );

    imagepng(
        image: $image,
        file: $file,
    );
    imagedestroy($image);
}
