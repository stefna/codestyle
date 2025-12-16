<?php declare(strict_types=1);

namespace StefnaTest\Sniffs\Commenting;

use StefnaTest\Sniffs\TestCase;

class DocCommentSniffTest extends TestCase
{
	public function testVarError(): void
	{
		$this->checkFile('Var');

		self::assertSniffError('OneLineTypeDeclare', line: 3);

		self::assertAllFixedInFile();
	}

	public function testTypeError(): void
	{
		$this->checkFile('Type');

		self::assertSniffError('OneLineTypeDeclare', line: 3);

		self::assertAllFixedInFile();
	}

	public function testMissAlignedBlockErrors(): void
	{
		$this->checkFile('MissAlignedBlock');

		self::assertSniffError('MissAlignedBlock', line: 4);
		self::assertSniffError('MissAlignedBlock', line: 5);
		self::assertSniffError('MissAlignedBlock', line: 8);
		self::assertSniffError('MissAlignedBlock', line: 9);

		self::assertSniffError('MissAlignedBlock', line: 13);
		self::assertSniffError('MissAlignedBlock', line: 14);
		self::assertSniffError('MissAlignedBlock', line: 17);
		self::assertSniffError('MissAlignedBlock', line: 18);

		self::assertAllFixedInFile();
	}
}
