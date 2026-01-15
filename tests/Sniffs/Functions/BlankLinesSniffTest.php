<?php declare(strict_types=1);

namespace StefnaTest\Sniffs\Functions;

use StefnaTest\Sniffs\TestCase;

class BlankLinesSniffTest extends TestCase
{
	public function testClassMethod(): void
	{
		$this->checkFile('ClassMethod');

		self::assertSniffError('FunctionScopeContentSpacing', 10);
		self::assertSniffError('TooManyBlankLines', 13);

		self::assertAllFixedInFile();
	}

	public function testFunction(): void
	{
		$this->checkFile('Function');

		self::assertSniffError('FunctionScopeContentSpacing', 8);
		self::assertSniffError('TooManyBlankLines', 11);

		self::assertAllFixedInFile();
	}

	public function testComment(): void
	{
		$this->checkFile('Comments');

		self::assertNoSniffErrorInFile();
	}
}
