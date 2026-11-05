<?php declare(strict_types=1);

namespace StefnaTest\Sniffs\Attributes\data\AttributeFormatSniff;

class ThreeOrMoreArgsUnder80
{
	#[Route('/api', name: 'api', methods: ['GET'])]
	public function test(): void {}
}
