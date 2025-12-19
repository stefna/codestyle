<?php declare(strict_types=1);

namespace StefnaTest\Sniffs\Naming\data;

final class CamelCapsMethodNameSniff
{
	public function tESt() {}

	public function test_snake() {}
}

function TEST()
{}
function TEST_SNAKE()
{}
