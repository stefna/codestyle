<?php declare(strict_types=1);

namespace StefnaTest\Sniffs\ControlStructures;

use StefnaTest\Sniffs\TestCase;

class ControlSignatureSniffTest extends TestCase
{
	public function testNoErrors(): void
	{
		$report = $this->checkFile('OK');

		self::assertNoSniffErrorInFile($report);
	}

	public function testFixErrors(): void
	{
		$report = $this->checkFile('Bad');

		self::assertSniffError($report, 3, 'NewlineAfterOpenBrace');
		self::assertSniffError($report, 3, 'NewlineAfterOpenBrace');
		self::assertSniffError($report, 3, 'SpaceAfterCloseBrace');
		self::assertSniffError($report, 5, 'NewlineAfterOpenBrace');
		self::assertSniffError($report, 7, 'NewlineAfterOpenBrace');
		self::assertSniffError($report, 9, 'NewlineAfterOpenBrace');
		self::assertSniffError($report, 11, 'NewlineAfterOpenBrace');
		self::assertSniffError($report, 13, 'NewlineAfterOpenBrace');
		self::assertSniffError($report, 13, 'NewlineAfterOpenBrace');
		self::assertSniffError($report, 13, 'NewlineAfterOpenBrace');
		self::assertSniffError($report, 15, 'NewlineAfterOpenBrace');
		self::assertSniffError($report, 17, 'NewlineAfterOpenBrace');
		self::assertSniffError($report, 19, 'NewlineAfterOpenBrace');
		self::assertSniffError($report, 21, 'NewlineAfterOpenBrace');

		self::assertAllFixedInFile($report);
	}
}
