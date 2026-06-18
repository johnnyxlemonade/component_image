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
 * Wires request context, cache handling, generation, filesystem and response
 * services into a ready-to-run image application instance.
 *
 * @package     Lemonade
 * @subpackage  Image
 * @category    Factory
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
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
                fileInspector: $fileInspector,
            ),
            cacheStorage: new ImageCacheStorage(),
            responseEmitter: $responseEmitter,
            generator: new ImageGenerator(
                fileInspector: $fileInspector,
                storageConfig: $this->storageConfig,
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
            file: $request->getBaseName(),
        );
    }

    private function createOptions(ImageRequest $request): ImageOptionsDTO
    {
        return (new ImageOptionsParser(
            args: $request->getArgs(),
        ))->toDTO();
    }

    private function createDirectoryResolver(ImageRequest $request): ImageDirectoryResolver
    {
        return new ImageDirectoryResolver(
            config: $this->storageConfig,
            level: $request->getLevel(),
            storageTypeId: $request->getStorageTypeId(),
            moduleId: $request->getModuleId(),
            artId: $request->getArtId(),
        );
    }

}
