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

### CamelCapsMethodNameSniff

Allow a the custom case of everything being upper case.

```php
class Example
{
	public function METHOD(): void {}
}
```

### BracketPlacementSniff

Two rules are enfored by this.

First, a control statement has to be on it's own line.
Example:

```php
if (false) {
    doSomething();
}
else {
    doSomethingElse();
}
```

Second, foces the closing bracket to be on it's own line
except if it's on a single line with an empty body.
Example:

```php
if (false) {}
```

### BlanLinesSniff

Enforces that inside a function, the first line after the opening `{` can't be empty
Example:

```php
function test(): void
{
	$var = 'This has to be the first line';
}
```

It also enforces that the spacing between rows can be 0 or 1 blank line.
Example:

```php
function test(): void
{
	$var = 0;

	$var += 1;
	$var *= 2;
}
```
