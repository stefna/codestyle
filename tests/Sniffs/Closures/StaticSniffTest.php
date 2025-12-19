<?php declare(strict_types=1);

namespace StefnaTest\Sniffs\Closures;

use StefnaTest\Sniffs\TestCase;

class StaticSniffTest extends TestCase
{
	public function testStatic(): void
	{
		$this->checkFile('Static');

		self::assertSniffWarning('MissingStaticOnClosure', line: 3);
		self::assertSniffWarning('MissingStaticOnClosure', line: 7);

		self::assertAllWarningsChecked();
	}
}
