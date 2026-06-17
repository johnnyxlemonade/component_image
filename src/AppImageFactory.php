<?php

declare(strict_types=1);

namespace Lemonade\Image;

use Lemonade\Image\Providers\DataProvider;
use Lemonade\Image\Providers\DirectoryProvider;
use Lemonade\Image\Providers\FileProvider;
use Lemonade\Image\Utils\FileSystem;

/**
 * Creates the default runtime graph for image request handling.
 *
 * This factory is responsible for wiring low-level providers together.
 */
final class AppImageFactory
{
    public static function createDefault(): self
    {
        return new self(
            filesystem: new FileSystem(),
        );
    }

    public function __construct(
        private readonly FileSystem $filesystem,
    ) {}

    public function createApplication(ImageRequest $request): ImageApplication
    {
        $responseEmitter = new ImageResponseEmitter();

        return new ImageApplication(
            context: $this->createContext($request),
            cacheResponder: new ImageCacheResponder(
                responseEmitter: $responseEmitter,
            ),
            cacheStorage: new ImageCacheStorage(),
            responseEmitter: $responseEmitter,
        );
    }

    private function createContext(ImageRequest $request): ImageContext
    {
        return new ImageContext(
            fileProvider: $this->createFileProvider($request),
        );
    }

    private function createFileProvider(ImageRequest $request): FileProvider
    {
        return new FileProvider(
            directory: $this->createDirectoryProvider($request),
            data: $this->createDataProvider($request),
            filesystem: $this->filesystem,
            file: $request->baseName,
        );
    }

    private function createDirectoryProvider(ImageRequest $request): DirectoryProvider
    {
        return new DirectoryProvider(
            level: $request->level,
            storageTypeId: $request->storageTypeId,
            moduleId: $request->moduleId,
            artId: $request->artId,
        );
    }

    private function createDataProvider(ImageRequest $request): DataProvider
    {
        return new DataProvider(
            args: $request->args,
        );
    }
}
