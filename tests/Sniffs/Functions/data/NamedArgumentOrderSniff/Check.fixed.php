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
			a: '1',
			b: '3',
			c: '2',
		);

		$example->test(
			a: '1',
			b: '3',
			c: '2',
		);

		$example->test(
			a: '1',
			b: '3',
			c: '2',
		);

		$example->test(a: '1', b: '3', c: '2');

		$example->test(
			'1',
			b: '3',
			c: '2',
		);

		$example->test(
			a: '1',
			b: fn($i) => $i * 2,
			c: [
				'x' => '1',
				'y' => '2',
			],
		);

		self::staticTest(
			a: '1',
			b: '3',
			c: '2',
		);

		(new Example('1', '2', '3'))->test(
			a: '1',
			b: '3',
			c: '2',
		);

		globalTest(
			a: '1',
			b: '3',
			c: '2',
		);

		// Correct order - should not report errors
		$example->test(
			a: '1',
			b: '2',
			c: '3',
		);
	}
}
