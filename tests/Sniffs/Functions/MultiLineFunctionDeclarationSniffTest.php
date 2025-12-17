<?php declare(strict_types=1);

namespace StefnaTest\Sniffs\Functions;

use StefnaTest\Sniffs\TestCase;

class MultiLineFunctionDeclarationSniffTest extends TestCase
{
	public function testSingleLineWhitespace(): void
	{
		$this->checkFile('SingleLineWhitespace');

		self::assertSniffError('SpaceBeforeScope', line: 7);
		self::assertSniffError('WhiteSpaceBetweenBraces', line: 7);
		self::assertSniffError('WhiteSpaceBetweenBraces', line: 9);
		self::assertSniffError('SpaceBeforeScope', line: 11);
		self::assertSniffError('WhiteSpaceBetweenBraces', line: 11);
		self::assertSniffError('SpaceBeforeScope', line: 13);
		self::assertSniffError('WhiteSpaceBetweenBraces', line: 13);
		self::assertSniffError('WhiteSpaceBetweenBraces', line: 15);
		self::assertSniffError('WhiteSpaceBetweenBraces', line: 17);

		self::assertAllFixedInFile('OK');
	}
}
