<?php declare(strict_types=1);

namespace StefnaTest\Sniffs\Functions;

use StefnaTest\Sniffs\TestCase;

class MultiLineFunctionDeclarationSniffTest extends TestCase
{
	public function testNoErrors(): void
	{
		$report = $this->checkFile('OK');

		self::assertNoSniffErrorInFile($report);
	}

	public function testSingleLineWhitespace(): void
	{
		$report = $this->checkFile('SingleLineWhitespace');

		self::assertSniffError($report, 5, 'WhiteSpaceBetweenBraces');
		self::assertSniffError($report, 7, 'WhiteSpaceBetweenBraces');
		self::assertSniffError($report, 9, 'WhiteSpaceBetweenBraces');
		self::assertSniffError($report, 11, 'WhiteSpaceBetweenBraces');
		self::assertSniffError($report, 13, 'WhiteSpaceBetweenBraces');
		self::assertSniffError($report, 15, 'WhiteSpaceBetweenBraces');

		self::assertAllErrorsChecked($report);

		self::assertAllFixedInFile($report);
	}
}
