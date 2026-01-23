<?php declare(strict_types=1);

namespace StefnaTest\Sniffs\Functions\data;

class MultiLineFunctionDeclarationSniff
{
	public function __construct(string $str){}

	public function A(): string{}

	public function B(): string|null  {}
}
