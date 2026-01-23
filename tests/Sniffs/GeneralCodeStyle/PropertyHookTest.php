<?php declare(strict_types=1);

namespace StefnaTest\Sniffs\GeneralCodeStyle;

use StefnaTest\Sniffs\TestCase;

class PropertyHookTest extends TestCase
{
	public function testHookProperties(): void
	{
		$this->checkFile('Supported');

		self::assertNoSniffErrorInFile();
	}
}
