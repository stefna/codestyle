<?php declare(strict_types=1);

namespace StefnaTest\Sniffs\Functions;

use StefnaTest\Sniffs\TestCase;

class NamedArgumentOrderSniffTest extends TestCase
{
	public function testNamedArgumentOrder(): void
	{
		$this->checkFile('Check');

		self::assertSniffError('Order', line: 18);
		self::assertSniffError('Order', line: 24);
		self::assertSniffError('Order', line: 30);
		self::assertSniffError('Order', line: 35);
		self::assertSniffError('Order', line: 38);
		self::assertSniffError('Order', line: 44);
		self::assertSniffError('Order', line: 53);
		self::assertSniffError('Order', line: 59);
		self::assertSniffError('Order', line: 65);

		self::assertAllFixedInFile();
	}
}
