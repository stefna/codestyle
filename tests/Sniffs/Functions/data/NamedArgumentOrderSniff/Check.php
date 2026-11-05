<?php declare(strict_types=1);

namespace StefnaTest\Sniffs\Functions\data\NamedArgumentOrderSniff;

function globalTest(string $a, string $b, string $c): void {}

class Example
{
	public function __construct(string $a = '', string $b = '', string $c = '') {}

	public function test(string $a, string $b, string $c): void {}

	public static function staticTest(string $a, string $b, string $c): void {}

	public function run(): void
	{
		$example = new Example(
			c: '2',
			a: '1',
			b: '3',
		);

		$example->test(
			a: '1',
			c: '2',
			b: '3',
		);

		$example->test(
			c: '2',
			a: '1',
			b: '3',
		);

		$example->test(a: '1', c: '2', b: '3');

		$example->test(
			'1',
			c: '2',
			b: '3',
		);

		$example->test(
			c: [
				'x' => '1',
				'y' => '2',
			],
			a: '1',
			b: fn($i) => $i * 2,
		);

		self::staticTest(
			c: '2',
			b: '3',
			a: '1',
		);

		(new Example('1', '2', '3'))->test(
			c: '2',
			a: '1',
			b: '3',
		);

		globalTest(
			c: '2',
			b: '3',
			a: '1',
		);

		// Correct order - should not report errors
		$example->test(
			a: '1',
			b: '2',
			c: '3',
		);
	}
}
