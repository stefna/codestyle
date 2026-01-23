<?php declare(strict_types=1);

namespace StefnaTest\Sniffs\Functions;

use StefnaTest\Sniffs\TestCase;

class PropertyPromotionSniffTest extends TestCase
{
	public function testPropertyPromotion(): void
	{
		$this->checkFile('Check');

		self::assertSniffError('PropertyPromotionNotOwnLine', line:7, occurance: 2);

		self::assertAllFixedInFile();
	}
}
