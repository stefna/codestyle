<?php declare(strict_types=1);

namespace StefnaTest\Sniffs\Functions;

use StefnaTest\Sniffs\TestCase;

class MultiLineFunctionDeclarationSniffTest extends TestCase
{
	public function testSingleLineWhitespace(): void
	{
		$this->checkFile('SingleLineWhitespace');

		self::assertSniffError('SpaceBeforeScope', line: 7);
		self::assertSniffError('SpaceBeforeScope', line: 9);
		self::assertSniffError('SpaceBeforeScope', line: 11);

		self::assertAllFixedInFile('OK');
	}
}
