<?php

declare(strict_types=1);

namespace Lemonade\Image;

use Throwable;

/**
 * Handles one image request.
 */
final class ImageApplication
{
    public function __construct(
        private readonly ImageContext $context,
        private readonly ImageCacheResponder $cacheResponder,
        private readonly ImageCacheStorage $cacheStorage,
        private readonly ImageResponseEmitter $responseEmitter,
        private readonly ImageGenerator $generator,
        private readonly ImageFileInspector $fileInspector,
    ) {}

    public function run(): void
    {
        $file = $this->context->getFile();

        try {
            if ($this->cacheResponder->sendBrowserCacheIfFresh(
                file: $file,
            )) {
                return;
            }

            if ($this->cacheResponder->sendCacheImageIfExists(
                file: $file,
            )) {
                return;
            }

            if ($this->fileInspector->exists(
                file: $file->getSourceFile(),
            )) {
                $result = $this->generator->createVariant(
                    file: $file,
                );

                $this->cacheStorage->saveVariant(
                    context: $this->context,
                    result: $result,
                );

                $this->responseEmitter->sendResult(
                    result: $result,
                );
            }

            $this->cacheStorage->deleteCache(
                context: $this->context,
            );

            $this->createAndSendFallback();
        } catch (Throwable) {
            $this->createAndSendFallback();
        }
    }

    private function createAndSendFallback(): void
    {
        $file = $this->context->getFile();
        $data = $this->context->getData();

        if ($data->isMissingAllSize()) {
            $data->setWidth(600);
            $data->setHeight(600);
        }

        $result = $this->generator->createFallback(
            file: $file,
        );

        $this->cacheStorage->saveFallback(
            context: $this->context,
            result: $result,
        );

        $this->responseEmitter->sendResult(
            result: $result,
        );
    }
}
