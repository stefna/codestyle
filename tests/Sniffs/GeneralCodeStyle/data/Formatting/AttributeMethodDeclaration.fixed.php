<?php declare(strict_types=1);

namespace StefnaTest\Sniffs\GeneralCodeStyle\data\Formatting;

class X
{
	public function setFoo(
		#[Beep]
		Foo $new
	): void {
	}
}
