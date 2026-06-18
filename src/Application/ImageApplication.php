<?php

declare(strict_types=1);

namespace Lemonade\Image\Application;

use Lemonade\Image\Cache\ImageCacheResponder;
use Lemonade\Image\Cache\ImageCacheStorage;
use Lemonade\Image\Context\ImageContext;
use Lemonade\Image\Detection\ImageFileInspector;
use Lemonade\Image\Generator\ImageGenerator;
use Lemonade\Image\Http\ImageHttpResponse;
use Lemonade\Image\Http\ImageHttpResponseFactory;
use Throwable;

/**
 * Coordinates the image request workflow.
 *
 * Resolves browser cache, filesystem cache, source generation and fallback
 * response handling for a single image request.
 *
 * @package     Lemonade
 * @subpackage  Image\Application
 * @category    Application
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
 */
final class ImageApplication
{
    public function __construct(
        private readonly ImageContext $context,
        private readonly ImageCacheResponder $cacheResponder,
        private readonly ImageCacheStorage $cacheStorage,
        private readonly ImageHttpResponseFactory $responseFactory,
        private readonly ImageGenerator $generator,
        private readonly ImageFileInspector $fileInspector,
    ) {}

    public function handle(): ImageHttpResponse
    {
        $file = $this->context->getFile();

        $response = $this->cacheResponder->createNotModifiedResponseIfFresh(
            file: $file,
        );

        if ($response !== null) {
            return $response;
        }

        $response = $this->cacheResponder->createCacheResponseIfExists(
            file: $file,
        );

        if ($response !== null) {
            return $response;
        }

        if (!$this->fileInspector->exists(
            file: $file->getSourceFile(),
        )) {
            $this->cacheStorage->deleteVariantCache(
                context: $this->context,
            );

            return $this->createFallbackResponse();
        }

        try {
            $result = $this->generator->createVariant(
                file: $file,
            );
        } catch (Throwable) {
            return $this->createFallbackResponse();
        }

        $this->cacheStorage->saveVariant(
            context: $this->context,
            result: $result,
        );

        return $this->responseFactory->fromResult(
            result: $result,
        );
    }

    private function createFallbackResponse(): ImageHttpResponse
    {
        $file = $this->context->getFile();

        $this->context->ensureFallbackSize(
            width: 600,
            height: 600,
        );

        $result = $this->generator->createFallback(
            file: $file,
        );

        $this->cacheStorage->saveFallback(
            context: $this->context,
            result: $result,
        );

        return $this->responseFactory->fromResult(
            result: $result,
        );
    }

}
