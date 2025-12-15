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

		self::assertSniffError($report, 7, 'ScopeNotCamelCaps');
		self::assertSniffError($report, 8, 'ScopeNotCamelCaps');
		self::assertSniffError($report, 11, 'NotCamelCaps');
		self::assertSniffError($report, 12, 'NotCamelCaps');
	}

	public function testOverrideNoFixErrors(): void
	{
		$report = $this->checkFile('ChildClass');

		self::assertSniffError($report, 9, 'ScopeNotCamelCaps');
	}

	public function testInterfaceNoFixErrors(): void
	{
		$report = $this->checkFile('ImplementInterface');

		self::assertSniffError($report, 9, 'ScopeNotCamelCaps');
	}
}
