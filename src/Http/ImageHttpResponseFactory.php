<?php

declare(strict_types=1);

namespace Lemonade\Image\Http;

use Lemonade\Image\Exceptions\Image\ImageRenderException;
use Lemonade\Image\ImageResult;

/**
 * Creates HTTP image responses from generated image results.
 *
 * Converts rendered image output into immutable response DTOs without emitting
 * headers, output or terminating the request.
 *
 * @package     Lemonade
 * @subpackage  Image\Http
 * @category    Factory
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
 */
final class ImageHttpResponseFactory
{
    public function fromResult(ImageResult $result): ImageHttpResponse
    {
        $content = $result->getImage()->toString(
            type: $result->getType(),
            quality: $result->getQuality(),
        );

        if ($content === '') {
            throw ImageRenderException::failed();
        }

        return ImageHttpResponse::binary(
            content: $content,
            type: $result->getType(),
        );
    }
}
