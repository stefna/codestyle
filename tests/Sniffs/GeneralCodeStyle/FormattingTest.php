<?php declare(strict_types=1);

namespace StefnaTest\Sniffs\GeneralCodeStyle;

use StefnaTest\Sniffs\TestCase;

class FormattingTest extends TestCase
{
	public function testTrailingCommaCall(): void
	{
		$this->checkFile('TrailingCommaCall');

		self::assertAllFixedInFile(skipErrorCheck: true);
	}
}
