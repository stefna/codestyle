<?php declare(strict_types=1);

namespace StefnaTest\Sniffs\Files;

use StefnaTest\Sniffs\TestCase;

class DeclareStrictSniffTest extends TestCase
{
	public function testNoErrors(): void
	{
		$report = $this->checkFile('OK');

		self::assertNoSniffErrorInFile($report);
	}

	public function testMissingErrors(): void
	{
		$report = $this->checkFile('Missing');

		self::assertSniffError($report, 1, 'MissingDeclareStrictInFile');

		self::assertAllFixedInFile($report);
	}

	public function testWrongLineErrors(): void
	{
		$report = $this->checkFile('WrongLine');

		self::assertSniffError($report, 3, 'DeclareStrictWrongLineInFile');

		self::assertAllFixedInFile($report);
	}

	public function testMultipleSpacesErrors(): void
	{
		$report = $this->checkFile('MultipleWhitespace');

		self::assertSniffError($report, 1, 'MultipleSpaceAfterOpeningTag');

		self::assertAllFixedInFile($report);
	}
}
