<?php declare(strict_types=1);

namespace StefnaTest\Sniffs\Closures;

use StefnaTest\Sniffs\TestCase;

class StaticSniffTest extends TestCase
{
	public function testStatic(): void
	{
		$this->checkFile('Static');

		self::assertSniffError('MissingStaticOnClosure', line: 3);
		self::assertSniffError('MissingStaticOnClosure', line: 7);

		self::assertAllFixedInFile();
	}

	public function testNonStatic(): void
	{
		$this->checkFile('NonStatic');

		self::assertSniffError('InvalidStaticOnClosure', line: 3);
		self::assertSniffError('InvalidStaticOnClosure', line: 7);

		self::assertAllFixedInFile();
	}
}
