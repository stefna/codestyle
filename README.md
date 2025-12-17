# Stefna Codestyle

## Usage

```
vendor/bin/phpcs -n --standard=vendor/stefna/codestyle/library.xml src/
```

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

## Sniffs

### DeclareStrictSniff

Now enforces that `<?php declare(strict_types=1);`
becomes the first line in the file.

### MultiLineFunctionDeclarationSniff

Add support for empty methods on a single line.
Example:

```php
class Example
{
	public function empty(): void {};
}
```
