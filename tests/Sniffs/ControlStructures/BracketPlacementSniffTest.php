<?php declare(strict_types=1);

namespace StefnaTest\Sniffs\ControlStructures;

use StefnaTest\Sniffs\TestCase;

class BracketPlacementSniffTest extends TestCase
{
	public function testDoWhile(): void
	{
		$this->checkFile('DoWhile');

		self::assertSniffError('ControlStamentNotAlone', line: 3);
		self::assertSniffError('ControlStamentNotAlone', line: 5);

		self::assertAllFixedInFile();
	}

	public function testWhile(): void
	{
		$this->checkFile('While');

		self::assertSniffError('ControlStamentNotAlone', line: 3);

		self::assertAllFixedInFile();
	}

	public function testFor(): void
	{
		$this->checkFile('For');

		self::assertSniffError('ControlStamentNotAlone', line: 3);

		self::assertAllFixedInFile();
	}

	public function testIf(): void
	{
		$this->checkFile('If');

		self::assertSniffError('ControlStamentNotAlone', line: 3, occurance: 3);
		self::assertSniffError('ControlStamentNotAlone', line: 7);
		self::assertSniffError('ClosingBracketNotAlone', line: 13);

		self::assertAllFixedInFile();
	}

	public function testTryCatchFinally(): void
	{
		$this->checkFile('TryCatchFinally');

		self::assertSniffError('ControlStamentNotAlone', line: 3, occurance: 2);
		self::assertSniffError('ControlStamentNotAlone', line: 5, occurance: 2);

		self::assertAllFixedInFile();
	}
}
