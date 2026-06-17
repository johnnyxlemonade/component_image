<?php

declare(strict_types=1);

namespace Lemonade\Image\Detection;

use function file_exists;

/**
 * Checks image-related filesystem state.
 *
 * Provides safe file existence checks used by cache, source and fallback
 * resolution without exposing raw filesystem calls to application services.
 *
 * @package     Lemonade
 * @subpackage  Image\Detection
 * @category    Detector
 * @link        https://lemonadeframework.cz
 * @author      Honza Mudrak <honzamudrak@gmail.com>
 * @license     MIT
 * @since       1.0.0
 */
final class ImageFileInspector
{
    public function exists(?string $file): bool
    {
        return $file !== null && $file !== '' && file_exists($file);
    }
}
