<?php declare(strict_types=1);

namespace StefnaTest\Sniffs\Functions;

use StefnaTest\Sniffs\TestCase;

class EmptyFunctionBodySniffTest extends TestCase
{
	public function testEmptyBody(): void
	{
		$this->checkFile('Check');

		self::assertSniffError('WhiteSpaceBetweenBraces', line: 7);
		self::assertSniffError('WhiteSpaceBetweenBraces', line: 10);

		self::assertAllFixedInFile();
	}
}
