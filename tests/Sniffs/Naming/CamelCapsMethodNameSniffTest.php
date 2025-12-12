<?php declare(strict_types=1);

namespace StefnaTest\Sniffs\Naming;

use StefnaTest\Sniffs\TestCase;

class CamelCapsMethodNameSniffTest extends TestCase
{
	public function testNoErrors(): void
	{
		$report = $this->checkFile('OK');

		self::assertNoSniffErrorInFile($report);
	}

	public function testBadNoFixErrors(): void
	{
		$report = $this->checkFile('BadNoFix');

		self::assertSniffError($report, 5, 'ScopeNotCamelCaps');
		self::assertSniffError($report, 6, 'ScopeNotCamelCaps');
		self::assertSniffError($report, 9, 'NotCamelCaps');
		self::assertSniffError($report, 10, 'NotCamelCaps');
	}
}
