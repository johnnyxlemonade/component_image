# Lemonade Image Component

[![PHPStan](https://github.com/johnnyxlemonade/component_image/actions/workflows/phpstan.yml/badge.svg)](https://github.com/johnnyxlemonade/component_image/actions/workflows/phpstan.yml)
[![Tests](https://github.com/johnnyxlemonade/component_image/actions/workflows/phpunit.yml/badge.svg)](https://github.com/johnnyxlemonade/component_image/actions/workflows/phpunit.yml)
[![Lint](https://github.com/johnnyxlemonade/component_image/actions/workflows/lint.yml/badge.svg)](https://github.com/johnnyxlemonade/component_image/actions/workflows/lint.yml)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

Standalone GD-based image component for PHP 8.1+.

This package provides image loading, resizing, cropping, generated image caching, fallback image generation and WebP output support. It is designed as a reusable Lemonade component and can also be integrated into other PHP projects when the expected storage layout is provided.

## Requirements

- PHP 8.1+
- GD extension
- mbstring extension

## Installation

This package is not published on Packagist. Install it directly from the public GitHub repository by adding it as a Composer VCS repository in your project.

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/johnnyxlemonade/component_image.git"
    }
  ],
  "require": {
    "lemonade/component_image": "dev-master"
  }
}
```

Then run:

```bash
composer update lemonade/component_image
```

Alternatively, configure the repository from the command line:

```bash
composer config repositories.lemonade-component-image vcs https://github.com/johnnyxlemonade/component_image.git
composer require lemonade/component_image:dev-master
```

## Features

- JPEG, PNG, GIF and WebP support
- Image loading from file or string
- Blank image creation
- Resize, crop, fit and canvas-based transformations
- Generated image cache
- WebP support detection
- HTTP cache headers
- Filesystem abstraction for cache writes
- Typed component exceptions
- PHPUnit test suite
- PHPStan level 10 configuration

## Basic usage

The legacy-compatible entrypoint is `Lemonade\Image\AppImage::factoryApp()`.

```php
<?php

use Lemonade\Image\AppImage;

AppImage::factoryApp(
    level: 6,
    storageTypId: 'gallery',
    moduleId: 12,
    artId: 345,
    baseName: 'example.jpg',
    args: 'w800-h600-z3-cffffff-q85-e1',
);
```

The component resolves the source file, checks browser and filesystem cache, generates a resized variant when needed, stores the generated image in cache and emits the HTTP image response.

The `storageTypId` parameter intentionally keeps the historical name for backward compatibility with named arguments.

## Request parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `level` | `int` | Directory split depth used when building the object storage path from `artId`. |
| `storageTypId` | `string|int|null` | Storage type identifier or alias. Known aliases are `template`, `thumbnail`, `gallery` and `editor`. |
| `moduleId` | `string|int|null` | Module or storage namespace. Defaults to `0` when omitted. |
| `artId` | `string|int|null` | Object identifier used to build the nested storage directory. Defaults to `0` when omitted. |
| `baseName` | `string|null` | Source image filename. Defaults to `missing.png` when omitted. |
| `args` | `string|null` | Compact image option string. |

## Image option string

Image options are passed as a dash-separated string:

```txt
w800-h600-z3-cffffff-q85-e1
```

Supported tokens:

| Token | Example | Description |
| --- | --- | --- |
| `w` | `w800` | Target width in pixels. |
| `h` | `h600` | Target height in pixels. |
| `q` | `q85` | Output quality. Invalid values are ignored. When quality clamping is enabled in the parser implementation, values are normalized to the supported range. |
| `c` | `cffffff` | Canvas color as a 6-character hexadecimal value without `#`. |
| `e` | `e1` | Enables or disables missing-image fallback handling. Supported values are `0` and `1`. |
| `z` | `z3` | Resize mode. See the resize mode table below. |

Unknown or invalid tokens are ignored. If the same option is passed multiple times, the last valid value wins.

Examples:

```txt
w800
w800-h600
w800-h600-z3
w800-h600-z1-cf5f5f5
w800-h600-q85
```

## Presets

The component supports fixed size presets:

| Preset | Size |
| --- | --- |
| `xss` | 32×32 |
| `xs` | 48×48 |
| `sm` | 96×96 |
| `md` | 160×160 |
| `lg` | 320×320 |
| `xl` | 640×640 |

Presets can be scaled by appending a numeric suffix:

```txt
md2
```

This resolves to `320×320`.

The maximum preset scale is intentionally limited to prevent excessive generated image sizes.

## Original mode

The special option `original` preserves the source dimensions:

```txt
original
```

Original mode resets width and height and bypasses the normal fallback dimensions and size limits.

## Resize modes

Resize mode is controlled by the `z` token.

| Mode | Meaning |
| --- | --- |
| `z0` | Shrink only. |
| `z1` | Fit into a canvas using the normal canvas scale. |
| `z2` | Exact resize. |
| `z3` | Fit into the requested box. |
| `z4` | Fit into a canvas using a larger canvas scale. |
| `z5` | Fit into a canvas using the maximum canvas scale. |

The internal implementation may represent these modes through an enum, but the public option string remains backward-compatible with the numeric `z` values.

## Storage layout

By default, `ImageStorageConfig::createDefault()` uses the following base paths:

```txt
./storage
./storage/0/cache
./storage/0/cache/0/0
./themes/frontend/error.png
```

Meaning:

| Config value | Default |
| --- | --- |
| Storage base | `./storage` |
| Cache base | `./storage/0/cache` |
| Fallback cache directory | `./storage/0/cache/0/0` |
| Placeholder image file | `./themes/frontend/error.png` |

Source images are resolved under:

```txt
{storageBase}/{moduleId}/{storageTypeId}/{splitArtId}/{baseName}
```

Generated cache variants are stored under:

```txt
{cacheBase}/{moduleId}/{storageTypeId}/{splitArtId}/
```

Fallback cache images are stored under:

```txt
{fallbackCacheDirectory}/{optionsHash}.png
{fallbackCacheDirectory}/{optionsHash}.webp
```

## Storage type aliases

The directory resolver supports these storage type aliases:

| Alias | Resolved storage type |
| --- | --- |
| `template` | `template` |
| `thumbnail` | `1` |
| `gallery` | `2` |
| `editor` | `5` |

Numeric or string storage type identifiers can also be passed directly.

## Directory splitting

The `artId` is converted to hexadecimal and split into nested directory segments according to `level`.

For example, with `level: 6`, the component creates a deterministic nested path from the object identifier. This keeps large image collections distributed across multiple directories instead of storing everything in a single flat folder.

## Configuration

You can create a custom storage configuration:

```php
<?php

use Lemonade\Image\ImageStorageConfig;

$config = new ImageStorageConfig(
    storageRoot: '/var/www/project',
    storageDirectory: 'storage',
    cacheDirectory: 'cache',
    fallbackModuleId: '0',
    fallbackStorageTypeId: '0',
    placeholderImageFile: '/var/www/project/themes/frontend/error.png',
);
```

`AppImageFactory::createDefault()` uses `ImageStorageConfig::createDefault()`. For custom integration, instantiate `AppImageFactory` directly with a custom `ImageStorageConfig`.

```php
<?php

use Lemonade\Image\AppImageFactory;
use Lemonade\Image\Generator\ImageRequest;
use Lemonade\Image\ImageStorageConfig;
use Lemonade\Image\Utils\FileSystem;

$request = new ImageRequest(
    level: 6,
    storageTypeId: 'gallery',
    moduleId: 12,
    artId: 345,
    baseName: 'example.jpg',
    args: 'w800-h600-z3-q85',
);

$factory = new AppImageFactory(
    filesystem: new FileSystem(),
    storageConfig: new ImageStorageConfig(
        storageRoot: '/var/www/project',
    ),
);

$factory
    ->createApplication($request)
    ->run();
```

## Parsed options API

`ImageOptionsParser` converts the compact argument string into an immutable `ImageOptionsDTO` snapshot:

```php
<?php

use Lemonade\Image\Options\ImageOptionsParser;

$options = (new ImageOptionsParser(
    args: 'w800-h600-z3-q85',
))->toDTO();

$options->getWidth();       // 800
$options->getHeight();      // 600
$options->getCrop();        // 3
$options->getQuality();     // 85
$options->getCanvasColor(); // ffffff
$options->isMissing();      // true
$options->getHash();        // deterministic options hash
```

Runtime image processing should consume the DTO rather than depending on parser internals.

## Cache behavior

The image workflow checks cache in this order:

1. Browser cache through `If-Modified-Since`.
2. Existing filesystem cache variant.
3. Source image generation.
4. Missing-image fallback generation.

Generated variants are saved into the cache directory. WebP variants are generated when WebP is supported by the installed GD extension.

When stale-cache validation is enabled, a cached variant should only be reused when it is at least as fresh as the source image. If the source image is newer than the cache variant, the image is regenerated.

## Fallback behavior

If the source image does not exist or generation fails, the component generates a fallback image.

If the configured placeholder image exists, it is used as the fallback source. Otherwise, the component creates an internal transparent placeholder image and renders it into the requested fallback canvas.

Fallback images are cached separately by option hash.

## HTTP responses

The component emits image responses directly.

Cached image files are streamed from disk in chunks. Generated images are rendered to binary output and emitted with cache headers, content type and content length when available.

Supported output MIME types:

| Type | MIME type |
| --- | --- |
| JPEG | `image/jpeg` |
| PNG | `image/png` |
| GIF | `image/gif` |
| WebP | `image/webp` |

## Development

Install dependencies:

```bash
composer update
```

Run PHP syntax lint:

```bash
composer lint
```

Run static analysis:

```bash
composer stan
```

Run tests:

```bash
composer test
```

Run tests in CI mode:

```bash
composer test:ci
```

Check coding standards:

```bash
composer cs:check
```

Fix coding standards:

```bash
composer cs:fix
```

Run smoke test:

```bash
composer smoke
```

Run all checks:

```bash
composer check
```

## Composer scripts

| Script | Description |
| --- | --- |
| `composer lint` | Run PHP syntax lint over source files. |
| `composer smoke` | Run smoke checks for basic component wiring. |
| `composer composer:validate` | Validate `composer.json` in strict mode. |
| `composer stan` | Run PHPStan static analysis. |
| `composer stan:ci` | Run PHPStan static analysis with GitHub Actions output format. |
| `composer cs:check` | Check coding standards using PHP-CS-Fixer. |
| `composer cs:fix` | Fix coding standards using PHP-CS-Fixer. |
| `composer test` | Run PHPUnit tests. |
| `composer test:ci` | Run PHPUnit tests without colors. |
| `composer check:platform` | Check installed platform requirements. |
| `composer check` | Run Composer validation, platform checks, lint, coding standards, static analysis and tests. |

## License

MIT
