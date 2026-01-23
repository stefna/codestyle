<?php declare(strict_types=1);

namespace StefnaTest\Sniffs\Functions\data\MultiLineFunctionDeclarationSniff;

final class Test
{
	public function __construct(
		private readonly PhotoTable $table,
	) {}
}
