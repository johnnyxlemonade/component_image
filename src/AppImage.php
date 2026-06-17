<?php

declare(strict_types=1);

namespace Lemonade\Image;

use Lemonade\Image\Generator\ImageRequest;

/**
 * Provides the backward-compatible image component entrypoint.
 *
 * Keeps the legacy static integration API while delegating request handling
 * to the modern application factory and runtime workflow.
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
     * Legacy framework entrypoint.
     *
     * Parameter $storageTypId intentionally keeps the original name
     * to avoid breaking named-argument calls.
     */
    public static function factoryApp(
        int $level,
        string|int|null $storageTypId,
        string|int|null $moduleId,
        string|int|null $artId,
        ?string $baseName,
        ?string $args,
    ): void {
        $request = new ImageRequest(
            level: $level,
            storageTypeId: $storageTypId,
            moduleId: $moduleId,
            artId: $artId,
            baseName: $baseName,
            args: $args,
        );

        AppImageFactory::createDefault()
            ->createApplication($request)
            ->run();
    }
}
