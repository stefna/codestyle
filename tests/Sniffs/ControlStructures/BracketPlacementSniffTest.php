<?php declare(strict_types=1);

namespace StefnaTest\Sniffs\ControlStructures;

use StefnaTest\Sniffs\TestCase;

class BracketPlacementSniffTest extends TestCase
{
	public function testNoErrors(): void
	{
		$report = $this->checkFile('OK');

		self::assertNoSniffErrorInFile($report);
	}

	public function testErrors(): void
	{
		$report = $this->checkFile('Errors');

		self::assertSniffError($report, 3, 'ControlStamentNotAlone');
		self::assertSniffError($report, 6, 'ControlStamentNotAlone');
		self::assertSniffError($report, 6, 'ControlStamentNotAlone');
		self::assertSniffError($report, 9, 'ClosingBracketNotAlone');
		self::assertSniffError($report, 10, 'ControlStamentNotAlone');
		self::assertSniffError($report, 10, 'ControlStamentNotAlone');
		self::assertSniffError($report, 14, 'ControlStamentNotAlone');

		self::assertAllFixedInFile($report);
	}
}
