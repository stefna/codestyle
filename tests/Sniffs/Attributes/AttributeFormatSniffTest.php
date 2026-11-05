<?php declare(strict_types=1);

namespace StefnaTest\Sniffs\Attributes;

use StefnaTest\Sniffs\TestCase;

class AttributeFormatSniffTest extends TestCase
{
	public function testOver120Chars(): void
	{
		$this->checkFile('Over120Chars');

		self::assertSniffError('AttributeTooLong', line: 7);

		self::assertAllFixedInFile();
	}

	public function testThreeOrMoreArgsOver80(): void
	{
		$this->checkFile('ThreeOrMoreArgsOver80');

		self::assertSniffError('TooManyArguments', line: 7);

		self::assertAllFixedInFile();
	}

	public function testThreeOrMoreArgsUnder80(): void
	{
		$this->checkFile('ThreeOrMoreArgsUnder80');

		self::assertSniffWarning('TooManyArgumentsWarning', line: 7);

		self::assertAllWarningsChecked();
	}

	public function testTwoArgsUnder120(): void
	{
		$this->checkFile('TwoArgsUnder120');

		self::assertNoSniffErrorInFile();
	}

	public function testMultilineMalformed(): void
	{
		$this->checkFile('MultilineMalformed');

		self::assertSniffError('OneArgumentPerLine', line: 7);
		self::assertSniffError('OneArgumentPerLine', line: 8);
		self::assertSniffError('CloserNotOwnLine', line: 8);

		self::assertAllFixedInFile(skipErrorCheck: true);
	}

	public function testOK(): void
	{
		$this->checkFile('OK');

		self::assertNoSniffErrorInFile();
	}
}
