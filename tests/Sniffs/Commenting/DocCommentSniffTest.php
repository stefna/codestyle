<?php declare(strict_types=1);

namespace StefnaTest\Sniffs\Commenting;

use StefnaTest\Sniffs\TestCase;

class DocCommentSniffTest extends TestCase
{
	public function testNoErrors(): void
	{
		$report = $this->checkFile('OK');

		self::assertNoSniffErrorInFile($report);
	}

	public function testBadErrors(): void
	{
		$report = $this->checkFile('Bad');

		self::assertSniffError($report, 3, 'OneLineTypeDeclare');

		self::assertSniffError($report, 14, 'ContentAfterOpen');
		self::assertSniffError($report, 14, 'ContentBeforeClose');

		self::assertAllFixedInFile($report);
	}
}
