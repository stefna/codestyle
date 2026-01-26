<?php declare(strict_types=1);

namespace StefnaTest\Sniffs\ControlStructures;

use StefnaTest\Sniffs\TestCase;

class TryCatchDeclarationSniffTest extends TestCase
{
	public function testTryCatch(): void
	{
		$this->checkFile('TryCatch');

		self::assertSniffError('WhiteSpaceBetweenBraces', line:8);
		self::assertSniffError('EmptyFinally', line:10);

		self::assertSniffError('WhiteSpaceBetweenBraces', line:16);
		self::assertSniffError('EmptyFinally', line:19);

		self::assertAllFixedInFile();
	}

	public function testTryCatchWithVariable(): void
	{
		$this->checkFile('TryCatchWithVariable');

		self::assertSniffError('EmptyBodyCaughtException', line:8);
		self::assertSniffError('EmptyBodyCaughtException', line:13);

		self::assertAllFixedInFile();
	}
}
