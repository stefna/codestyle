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

		self::assertSniffError($report, 3, 'CommentAfterControlStatement');
		self::assertSniffError($report, 5, 'CommentAfterControlStatement');
		self::assertSniffError($report, 6, 'CommentAfterControlStatement');
		self::assertSniffError($report, 7, 'CommentAfterControlStatement');
		self::assertSniffError($report, 7, 'BracketBeforeControlStatement');
		self::assertSniffError($report, 7, 'CommentAfterControlStatement');
		self::assertSniffError($report, 7, 'BracketBeforeControlStatement');
		self::assertSniffError($report, 7, 'CommentAfterControlStatement');
		self::assertSniffError($report, 8, 'CommentAfterControlStatement');
		self::assertSniffError($report, 9, 'CommentAfterControlStatement');
		self::assertSniffError($report, 10, 'CommentAfterControlStatement');
		self::assertSniffError($report, 10, 'BracketBeforeControlStatement');
		self::assertSniffError($report, 10, 'CommentAfterControlStatement');
		self::assertSniffError($report, 10, 'BracketBeforeControlStatement');
		self::assertSniffError($report, 10, 'CommentAfterControlStatement');

		self::assertAllFixedInFile($report);
	}
}
