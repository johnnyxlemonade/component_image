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
    ) {}

    public function run(): void
    {
        $provider = $this->context->getFileProvider();

        try {
            if ($this->cacheResponder->sendBrowserCacheIfFresh($provider)) {
                return;
            }

            if ($this->cacheResponder->sendCacheImageIfExists($provider)) {
                return;
            }

            if ($provider->isFileExists($provider->getFileFs())) {
                $result = $this->generator->createVariant($provider);
                $this->cacheStorage->saveVariant($this->context, $result);
                $this->responseEmitter->sendResult($result);
            }

            $this->cacheStorage->deleteCache($this->context);
            $this->createAndSendFallback();
        } catch (Throwable) {
            $this->createAndSendFallback();
        }
    }

    private function createAndSendFallback(): void
    {
        $provider = $this->context->getFileProvider();
        $data = $provider->getData();

        if ($data->isMissingAllSize()) {
            $data->setWidth(600);
            $data->setHeight(600);
        }

        $result = $this->generator->createFallback($provider);
        $this->cacheStorage->saveFallback($this->context, $result);
        $this->responseEmitter->sendResult($result);
    }
}
