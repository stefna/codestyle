<?php declare(strict_types=1);

namespace StefnaTest\Sniffs\GeneralCodeStyle;

use StefnaTest\Sniffs\TestCase;

class FormattingTest extends TestCase
{
	public function testFormatting(): void
	{
		$this->checkFile('Formatting');

		self::assertAllFixedInFile(skipErrorCheck: true);
	}
}
