<?php declare(strict_types=1);

class Test
{
	public function __construct(private string $str){ }

	public function A() { }

	public function B(): string{ }

	public function C(): string|null  { }

	public function D($param): string { }

	public function E(int $param): string { }
}
