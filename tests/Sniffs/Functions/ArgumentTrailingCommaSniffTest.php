<?php declare(strict_types=1);

namespace StefnaTest\Sniffs\Functions;

use StefnaTest\Sniffs\TestCase;

class ArgumentTrailingCommaSniffTest extends TestCase
{
	public function testMultiLineArgumentFunction(): void
	{
		$this->checkFile('MultiLineArgumentFunction');

		self::assertSniffError('MissingTrailingComma', line: 5);

		self::assertAllFixedInFile();
	}

	public function testMultiLineArgumentClosure(): void
	{
		$this->checkFile('MultiLineArgumentClosure');

		self::assertSniffError('MissingTrailingComma', line: 5);

		self::assertAllFixedInFile();
	}

	public function testMultiLineArgumentFn(): void
	{
		$this->checkFile('MultiLineArgumentFn');

		self::assertSniffError('MissingTrailingComma', line: 5);

		self::assertAllFixedInFile();
	}
}
