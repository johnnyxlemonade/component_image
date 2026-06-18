# Lemonade Image examples

Small runnable examples for `lemonade/component_image`.

Install dependencies first:

```bash
composer install
```

Run the examples from the repository root:

```bash
php -S localhost:8000 -t examples
```

Then open:

```text
http://localhost:8000/basic.php
http://localhost:8000/custom.php
http://localhost:8000/placeholder.php
```

## Runtime files

All example runtime files are generated under:

```text
build/examples/
```

The `build/` directory is ignored by Git and can be safely deleted.

## basic.php

Uses `AppImageFactory` with explicit `ImageStorageConfig`.

The example creates a demo source image under:

```text
build/examples/storage/10/1/00/30/39/example.png
```

and then emits a resized image response.

This keeps the example isolated from the repository root storage path.

## custom.php

Uses `AppImageFactory` directly with custom `ImageStorageConfig`.

The example demonstrates how to configure the storage root, storage directory,
cache directory and placeholder image path explicitly:

```php
new ImageStorageConfig(
    storageRoot: dirname(__DIR__) . '/build/examples',
    storageDirectory: 'storage',
    cacheDirectory: 'cache',
    fallbackModuleId: '0',
    fallbackStorageTypeId: '0',
    placeholderImageFile: dirname(__DIR__) . '/build/examples/placeholder.png',
)
```

Generated source, cache and placeholder files stay under:

```text
build/examples/
```

## placeholder.php

Demonstrates fallback placeholder rendering when the requested source image does not exist.

The placeholder image path is configured through:

```php
new ImageStorageConfig(
    storageRoot: dirname(__DIR__) . '/build/examples',
    storageDirectory: 'storage',
    cacheDirectory: 'cache',
    fallbackModuleId: '0',
    fallbackStorageTypeId: '0',
    placeholderImageFile: dirname(__DIR__) . '/build/examples/placeholder.png',
)
```

The example intentionally does not create the requested source image, so the component renders a generated fallback image using the configured placeholder.

## Response handling

The examples use `AppImageFactory` directly so all generated files stay inside `build/examples/`.

Each example creates an `ImageRequest`, resolves an `ImageHttpResponse` through `ImageApplication::handle()` and emits it through `ImageResponseEmitter`.

The default facade `AppImage::emit()` can be used in application code when the default storage configuration is sufficient.

## Image option format

Image options use compact dash-separated tokens:

```text
w320-h240-z1-cffffff-q85
```

Supported examples:

```text
md
lg2
original
w320-h240
w320-h240-z1
w600-h400-cf5f5f5-q90
```

Common tokens:

```text
w = width
h = height
q = quality
c = canvas color
z = resize mode
e = missing image flag
```
