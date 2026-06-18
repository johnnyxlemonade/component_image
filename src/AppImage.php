<?php

declare(strict_types=1);

namespace Lemonade\Image;

use Lemonade\Image\Generator\ImageRequest;

/**
 * Provides the image component entrypoint.
 *
 * Delegates request handling to the application factory and runtime workflow.
 *
 * @package     Lemonade
 * @subpackage  Image
 * @category    Facade
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
 */
final class AppImage
{
    /**
     * Emits an image response for a prepared image request.
     */
    public static function emit(ImageRequest $request): void
    {
        AppImageFactory::createDefault()
            ->createApplication($request)
            ->run();
    }
}
