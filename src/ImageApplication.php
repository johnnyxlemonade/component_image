<?php

declare(strict_types=1);

namespace Lemonade\Image;

use Lemonade\Image\Providers\ImageProvider;
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
                $result = ImageProvider::imageCreate($provider);
                $this->responseEmitter->sendResult($result);
            }

            $this->cacheStorage->deleteCache($this->context);

            $result = ImageProvider::imageError($provider);
            $this->responseEmitter->sendResult($result);
        } catch (Throwable) {
            $this->sendFallbackImage();
        }
    }

    private function sendFallbackImage(): void
    {
        $provider = $this->context->getFileProvider();
        $data = $provider->getData();

        if ($data->isMissingAllSize()) {
            $data->setWidth(600);
            $data->setHeight(600);
        }

        $result = ImageProvider::imageError($provider);
        $this->responseEmitter->sendResult($result);
    }
}
