<?php

declare(strict_types=1);

namespace Lemonade\Image\Utils;

use Closure;
use GdImage;
use Lemonade\Image\Exceptions\Gd\GdImageOutputException;
use Lemonade\Image\Exceptions\Image\ImageRenderException;
use Lemonade\Image\Exceptions\Image\ImageTypeException;
use Lemonade\Image\Exceptions\IOException;
use Lemonade\Image\Generator\AppGenerator;
use Throwable;

use function array_flip;
use function bin2hex;
use function error_get_last;
use function function_exists;
use function getmypid;
use function header;
use function is_file;
use function ob_end_clean;
use function ob_get_clean;
use function ob_get_level;
use function ob_start;
use function pathinfo;
use function random_bytes;
use function sprintf;
use function strtolower;

use const PATHINFO_EXTENSION;

/**
 * Renders GD images into files, strings or HTTP responses.
 *
 * Handles output format resolution, temporary cache writes and output buffer
 * capture for generated image content.
 *
 * @package     Lemonade
 * @subpackage  Image\Utils
 * @category    Utility
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
 */
final class ImageRenderer
{
    public static function save(
        GdImage $image,
        string $file,
        ?int $quality = null,
        ?int $type = null,
        ?FileSystem $filesystem = null,
    ): void {
        if ($type === null) {
            $type = self::resolveTypeFromFile($file);
        }

        self::output(
            image: $image,
            type: $type,
            quality: $quality,
            file: $file,
            filesystem: $filesystem ?? new FileSystem(),
        );
    }

    public static function toString(GdImage $image, int $type = AppGenerator::JPEG, ?int $quality = null): string
    {
        return self::capture(static function () use ($image, $type, $quality): void {
            self::output(
                image: $image,
                type: $type,
                quality: $quality,
                file: null,
                filesystem: null,
            );
        });
    }

    public static function send(GdImage $image, int $type = AppGenerator::JPEG, ?int $quality = null): void
    {
        header('Content-Type: ' . ImageFormat::typeToMimeType($type));

        self::output(
            image: $image,
            type: $type,
            quality: $quality,
            file: null,
            filesystem: null,
        );
    }

    private static function resolveTypeFromFile(string $file): int
    {
        $extensions = array_flip(ImageFormat::getFormats()) + ['jpg' => AppGenerator::JPEG];
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        if (!isset($extensions[$extension])) {
            throw ImageTypeException::unsupportedExtension($extension);
        }

        return $extensions[$extension];
    }

    private static function output(
        GdImage $image,
        int $type,
        ?int $quality = null,
        ?string $file = null,
        ?FileSystem $filesystem = null,
    ): void {
        $tmpFile = null;
        $isCache = $file !== null;

        if ($isCache) {
            $targetFile = $file;

            if (is_file($targetFile)) {
                return;
            }

            $filesystem ??= new FileSystem();
            $filesystem->createDirForFile($targetFile);

            $tmpFile = $targetFile . '.tmp.' . getmypid() . '.' . bin2hex(random_bytes(4));
            $file = $tmpFile;
        } else {
            $targetFile = null;
        }

        $success = match ($type) {
            AppGenerator::JPEG => GdImageOperations::outputJpeg($image, $file, $quality ?? 85),
            AppGenerator::PNG => GdImageOperations::outputPng($image, $file, $quality ?? 9),
            AppGenerator::GIF => GdImageOperations::outputGif($image, $file),
            AppGenerator::WEBP => GdImageOperations::outputWebp($image, $file, $quality ?? 80),
            default => throw ImageTypeException::unsupported($type),
        };

        if (!$success) {
            self::handleOutputFailure($tmpFile, $isCache, $targetFile, $filesystem);

            return;
        }

        if ($tmpFile !== null && $targetFile !== null) {
            self::moveTemporaryFile($tmpFile, $targetFile, $filesystem ?? new FileSystem());
        }
    }

    private static function handleOutputFailure(
        ?string $tmpFile,
        bool $isCache,
        ?string $targetFile,
        ?FileSystem $filesystem,
    ): void {
        if ($tmpFile !== null && is_file($tmpFile)) {
            ($filesystem ?? new FileSystem())->delete($tmpFile);
        }

        if ($isCache) {
            if (function_exists('log_message')) {
                $lastError = self::getLastError();

                log_message(
                    'error',
                    sprintf(
                        'Image cache write failed (%s): %s',
                        $targetFile ?? 'unknown',
                        $lastError !== '' ? $lastError : 'unknown GD error',
                    ),
                );
            }

            return;
        }

        $lastError = self::getLastError();

        throw GdImageOutputException::output(
            $lastError !== '' ? $lastError : 'Image output failed.',
        );
    }

    private static function moveTemporaryFile(string $tmpFile, string $targetFile, FileSystem $filesystem): void
    {
        if (is_file($targetFile)) {
            $filesystem->delete($tmpFile);

            return;
        }

        try {
            $filesystem->rename($tmpFile, $targetFile);
        } catch (IOException) {
            if (is_file($targetFile)) {
                $filesystem->delete($targetFile);
            }

            try {
                $filesystem->rename($tmpFile, $targetFile);
            } catch (IOException) {
                if (is_file($tmpFile)) {
                    $filesystem->delete($tmpFile);
                }

                if (function_exists('log_message')) {
                    log_message(
                        'error',
                        sprintf('Failed to move image cache file: %s', $targetFile),
                    );
                }
            }
        }
    }

    private static function capture(Closure $callback): string
    {
        ob_start();

        try {
            $callback();

            $content = ob_get_clean();

            if ($content === false) {
                throw ImageRenderException::outputBufferFailed();
            }

            return $content;
        } catch (Throwable $e) {
            if (ob_get_level() > 0) {
                ob_end_clean();
            }

            throw $e;
        }
    }

    private static function getLastError(): string
    {
        $error = error_get_last();

        return $error === null ? '' : $error['message'];
    }
}
