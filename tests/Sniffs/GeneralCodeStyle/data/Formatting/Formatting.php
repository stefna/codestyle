<?php declare(strict_types=1);

namespace StefnaTest\Sniffs\GeneralCodeStyle\data\Formatting;

class X
{
	public const string X = 'string';
	public string $var = '';
	public function __construct(
		public int $y = 53,

		public bool $q = true
	) {}


	public function test(): void
	{

		echo 'hi';
	}

	public function z(): void
	{
		if (random_int(0, 1)) {

			echo 'Y';
		}


		echo 'Z';

	}
}

random_int(
	0,
	1
);
