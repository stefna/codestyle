# Stefna Codestyle

## Usage

```sh
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

### DocCommentSniff

Allows some doc blocks to work as single line:

- @var
- @phpstan-var
- @type
- @lang
- @noinspection
- @use
- @deprecated
- @phpstan-ignore-next-line

```php
/** @var string $var */
```

`@return` doesn't need any description/data:

```php
/**
 * @return
 */
```

### ControlStructureSpacingSniff

Adding an exception to allow multiline if-statments to be indented less,
if started on the same line as the opening parenthesis.

Example:

```php
	if (isset(
		$a['key'],
		$a['key']['subKey'],
	)) {
	}
```

### ArgumentTrailingCommaSniff

Now enforeces that multiline functions have trailing comma.
Example:

```php
function example(
	int $a,
	int $b,
): void {}
```
