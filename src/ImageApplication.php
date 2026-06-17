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
    ) {}

    public function run(): void
    {
        $provider = $this->context->getFileProvider();

        try {
            if ($provider->sendBrowserImage()) {
                return;
            }

            if ($provider->sendCacheImage()) {
                return;
            }

            if ($provider->isFileExists($provider->getFileFs())) {
                ImageProvider::imageCreate($provider);
                return;
            }

            $provider->deleteCache();
            ImageProvider::imageError($provider);
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

        ImageProvider::imageError($provider);
    }
}
