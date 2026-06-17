<?php

declare(strict_types=1);

namespace Lemonade\Image;

use Lemonade\Image\Providers\FileProvider;
use Lemonade\Image\Providers\ImageProvider;
use Throwable;

/**
 * Handles one image request.
 *
 * This is the application-level workflow:
 * - browser cache shortcut
 * - generated cache shortcut
 * - source image generation
 * - fallback image generation
 */
final class ImageApplication
{
    public function __construct(
        private readonly FileProvider $provider,
    ) {}

    public function run(): void
    {
        try {
            if ($this->provider->sendBrowserImage()) {
                return;
            }

            if ($this->provider->sendCacheImage()) {
                return;
            }

            if ($this->provider->isFileExists($this->provider->getFileFs())) {
                ImageProvider::imageCreate($this->provider);
                return;
            }

            $this->provider->deleteCache();
            ImageProvider::imageError($this->provider);
        } catch (Throwable) {
            $this->sendFallbackImage();
        }
    }

    private function sendFallbackImage(): void
    {
        $data = $this->provider->getData();

        if ($data->isMissingAllSize()) {
            $data->setWidth(600);
            $data->setHeight(600);
        }

        ImageProvider::imageError($this->provider);
    }
}
