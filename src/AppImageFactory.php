<?php

declare(strict_types=1);

namespace Lemonade\Image;

use Lemonade\Image\Application\ImageApplication;
use Lemonade\Image\Cache\ImageCacheResponder;
use Lemonade\Image\Cache\ImageCacheStorage;
use Lemonade\Image\Context\ImageContext;
use Lemonade\Image\Context\ImageFileContext;
use Lemonade\Image\Detection\ImageFileInspector;
use Lemonade\Image\Filesystem\ImageDirectoryResolver;
use Lemonade\Image\Generator\ImageGenerator;
use Lemonade\Image\Generator\ImageRequest;
use Lemonade\Image\Http\ImageResponseEmitter;
use Lemonade\Image\Options\ImageOptionsDTO;
use Lemonade\Image\Options\ImageOptionsParser;
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
            storageConfig: ImageStorageConfig::createDefault(),
        );
    }

    public function __construct(
        private readonly FileSystem $filesystem,
        private readonly ImageStorageConfig $storageConfig,
    ) {}

    public function createApplication(ImageRequest $request): ImageApplication
    {
        $responseEmitter = new ImageResponseEmitter();
        $fileInspector = new ImageFileInspector();

        return new ImageApplication(
            context: $this->createContext(
                request: $request,
            ),
            cacheResponder: new ImageCacheResponder(
                responseEmitter: $responseEmitter,
                fileInspector: $fileInspector,
            ),
            cacheStorage: new ImageCacheStorage(),
            responseEmitter: $responseEmitter,
            generator: new ImageGenerator(
                fileInspector: $fileInspector,
            ),
            fileInspector: $fileInspector,
        );
    }

    private function createContext(ImageRequest $request): ImageContext
    {
        return new ImageContext(
            file: $this->createFileContext(
                request: $request,
            ),
        );
    }

    private function createFileContext(ImageRequest $request): ImageFileContext
    {
        return new ImageFileContext(
            directory: $this->createDirectoryResolver(
                request: $request,
            ),
            options: $this->createOptions(
                request: $request,
            ),
            filesystem: $this->filesystem,
            file: $request->baseName,
        );
    }

    private function createOptions(ImageRequest $request): ImageOptionsDTO
    {
        return (new ImageOptionsParser(
            args: $request->args,
        ))->toDTO();
    }

    private function createDirectoryResolver(ImageRequest $request): ImageDirectoryResolver
    {
        return new ImageDirectoryResolver(
            config: $this->storageConfig,
            level: $request->level,
            storageTypeId: $request->storageTypeId,
            moduleId: $request->moduleId,
            artId: $request->artId,
        );
    }

}
