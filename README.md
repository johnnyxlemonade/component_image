# Lemonade Image Component

Standalone GD-based image component for PHP 8.1+.

This package provides image loading, resizing, cropping, cache generation and WEBP output support. It is designed as a reusable component and can be used independently in different PHP projects.

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

- JPEG, PNG, GIF and WEBP support
- Image loading from file or string
- Blank image creation
- Resize, crop, fit and canvas-based transformations
- Generated image cache
- WEBP support detection
- HTTP cache headers
- Filesystem abstraction for cache writes
- Typed component exceptions
- PHPStan level 10 ready

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

## License

MIT
