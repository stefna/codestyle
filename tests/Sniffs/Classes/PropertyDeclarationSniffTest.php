<?php declare(strict_types=1);

namespace StefnaTest\Sniffs\Classes;

use StefnaTest\Sniffs\TestCase;

class PropertyDeclarationSniffTest extends TestCase
{
	public function testFullExample(): void
	{
		$this->checkFile('FullExample');

		self::assertNoSniffErrorInFile();
	}
}
