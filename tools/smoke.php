<?php

declare(strict_types=1);

use Lemonade\Image\AppGenerator;
use Lemonade\Image\Utils\FileSystem;


require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

/**
 * @throws RuntimeException
 */
function smokeAssert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

smokeAssert(extension_loaded('gd'), 'GD extension is not loaded.');

$root = dirname(__DIR__);
$outputDir = $root . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'smoke';

$filesystem = new FileSystem();
$filesystem->createDir($outputDir);

$sourceFile = $outputDir . DIRECTORY_SEPARATOR . 'source.png';
$resizedJpeg = $outputDir . DIRECTORY_SEPARATOR . 'resized.jpg';
$croppedPng = $outputDir . DIRECTORY_SEPARATOR . 'cropped.png';
$resizedWebp = $outputDir . DIRECTORY_SEPARATOR . 'resized.webp';

$image = AppGenerator::fromBlank(
    800,
    600,
    [
        'red' => 240,
        'green' => 240,
        'blue' => 240,
    ],
);

smokeAssert($image->getWidth() === 800, 'Blank image width mismatch.');
smokeAssert($image->getHeight() === 600, 'Blank image height mismatch.');

$image->save($sourceFile, 9, AppGenerator::PNG, $filesystem);

smokeAssert(file_exists($sourceFile), 'Source PNG was not created.');
smokeAssert((int) filesize($sourceFile) > 0, 'Source PNG is empty.');
smokeAssert(AppGenerator::detectTypeFromFile($sourceFile) === AppGenerator::PNG, 'Source file type detection failed.');

$loaded = AppGenerator::fromFile($sourceFile);

smokeAssert($loaded->getWidth() === 800, 'Loaded image width mismatch.');
smokeAssert($loaded->getHeight() === 600, 'Loaded image height mismatch.');

$resized = clone $loaded;
$resized->resize(320, 240, AppGenerator::FIT);
$resized->save($resizedJpeg, 85, AppGenerator::JPEG, $filesystem);

smokeAssert(file_exists($resizedJpeg), 'Resized JPEG was not created.');
smokeAssert((int) filesize($resizedJpeg) > 0, 'Resized JPEG is empty.');
smokeAssert(AppGenerator::detectTypeFromFile($resizedJpeg) === AppGenerator::JPEG, 'JPEG type detection failed.');

$cropped = clone $loaded;
$cropped->crop('50%', '50%', 200, 200);
$cropped->save($croppedPng, 9, AppGenerator::PNG, $filesystem);

smokeAssert(file_exists($croppedPng), 'Cropped PNG was not created.');
smokeAssert((int) filesize($croppedPng) > 0, 'Cropped PNG is empty.');
smokeAssert(AppGenerator::detectTypeFromFile($croppedPng) === AppGenerator::PNG, 'Cropped PNG type detection failed.');

$string = $resized->toString(AppGenerator::JPEG, 85);

smokeAssert($string !== '', 'JPEG string output is empty.');
smokeAssert(AppGenerator::detectTypeFromString($string) === AppGenerator::JPEG, 'JPEG string type detection failed.');

if (function_exists('imagewebp')) {
    $webp = clone $loaded;
    $webp->resize(320, 240, AppGenerator::FIT);
    $webp->save($resizedWebp, 80, AppGenerator::WEBP, $filesystem);

    smokeAssert(file_exists($resizedWebp), 'WEBP file was not created.');
    smokeAssert((int) filesize($resizedWebp) > 0, 'WEBP file is empty.');
    smokeAssert(AppGenerator::detectTypeFromFile($resizedWebp) === AppGenerator::WEBP, 'WEBP type detection failed.');
}

echo sprintf(
    "Smoke test passed. Output directory: %s\n",
    $outputDir,
);
