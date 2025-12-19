<?php declare(strict_types=1);

namespace StefnaTest\Sniffs\Naming;

use StefnaTest\Sniffs\TestCase;

class CamelCapsMethodNameSniffTest extends TestCase
{
	public function testBadNoFixErrors(): void
	{
		$this->checkFile('BadNoFix');

		self::assertSniffError('ScopeNotCamelCaps', line: 9);
		self::assertSniffError('NotCamelCaps', line: 14);

		self::assertAllErrorsChecked();
	}

	public function testOverrideNoFixErrors(): void
	{
		$report = $this->checkFile('ChildClass');

		self::assertSniffError('ScopeNotCamelCaps', line: 9);

		self::assertAllErrorsChecked();
	}

	public function testInterfaceNoFixErrors(): void
	{
		$report = $this->checkFile('ImplementInterface');

		self::assertSniffError('ScopeNotCamelCaps', 9);

		self::assertAllErrorsChecked();
	}
}
