<?php declare(strict_types=1);

namespace StefnaTest\Sniffs\ControlStructures;

use StefnaTest\Sniffs\TestCase;

class ControlStructureSpacingSniffTest extends TestCase
{
	public function testNoErrors(): void
	{
		$report = $this->checkFile('OK');

		self::assertNoSniffErrorInFile($report);
	}

	public function testLineIndentErrors(): void
	{
		$report = $this->checkFile('LineIndent');

		self::assertSniffError($report, 4, 'LineIndent');
	}

	public function testLCloseParenthesisLineErrors(): void
	{
		$report = $this->checkFile('CloseParenthesisLine');

		self::assertSniffError($report, 4, 'CloseParenthesisLine');
	}

	public function testLCloseParenthesisIndentErrors(): void
	{
		$report = $this->checkFile('CloseParenthesisIndent');

		self::assertSniffError($report, 5, 'CloseParenthesisIndent');
	}
}
