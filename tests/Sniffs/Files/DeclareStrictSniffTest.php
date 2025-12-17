<?php declare(strict_types=1);

namespace StefnaTest\Sniffs\Files;

use StefnaTest\Sniffs\TestCase;

class DeclareStrictSniffTest extends TestCase
{
	public function testMissingErrors(): void
	{
		$this->checkFile('Missing');

		self::assertSniffError(code: 'MissingDeclareStrictInFile', line: 1);

		self::assertAllFixedInFile(fixedVariant: 'OK');
	}

	public function testWrongLineErrors(): void
	{
		$this->checkFile('WrongLine');

		self::assertSniffError(code: 'DeclareStrictWrongLineInFile', line: 3);
		self::assertSniffError(code: 'MultipleSpaceAfterOpeningTag', line: 3);

		self::assertAllFixedInFile(fixedVariant: 'OK');
	}

	public function testMultipleSpacesErrors(): void
	{
		$this->checkFile('MultipleWhitespace');

		self::assertSniffError(code: 'MultipleSpaceAfterOpeningTag', line: 1);

		self::assertAllFixedInFile(fixedVariant: 'OK');
	}
}
