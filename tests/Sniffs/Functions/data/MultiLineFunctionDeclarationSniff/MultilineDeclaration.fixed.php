<?php declare(strict_types=1);

namespace StefnaTest\Sniffs\Functions\data\MultiLineFunctionDeclarationSniff;

class MultilineDeclaration
{
	public function setFoo(
		#[Beep]
		Foo $new,
	): void {
	}
}
