<?php

declare(strict_types=1);

namespace Lemonade\Image;

use Lemonade\Image\Generator\ImageRequest;

/**
 * Backward-compatible facade for framework integration.
 *
 * New code should prefer AppImageFactory + ImageApplication.
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
