# Lemonade Image Component

GD-based image component for PHP 8.1+.

The component provides image loading, resizing, cropping, cache generation and WEBP output support.

## Requirements

- PHP 8.1+
- GD extension
- mbstring extension

## Installation

```bash
composer require lemonade/component_image
```

## Features

- JPEG, PNG, GIF and WEBP support
- Resize, crop, fit and canvas-based transformations
- Generated image cache
- WEBP support detection
- HTTP cache headers
- Typed component exceptions
- PHPStan level 10 ready

## Development

Install dependencies:

```bash
composer update
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

Run all checks:

```bash
composer check
```

## License

MIT

## Links

- https://lemonadeframework.cz/
