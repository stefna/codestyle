<?php declare(strict_types=1);

namespace StefnaTest\Sniffs\ControlStructures;

use StefnaTest\Sniffs\TestCase;

class TryCatchDeclarationSniffTest extends TestCase
{
	public function testTryCatch(): void
	{
		$this->checkFile('TryCatch');

		self::assertSniffError('WhiteSpaceBetweenBraces', line:8);

		self::assertAllFixedInFile();
	}
}
