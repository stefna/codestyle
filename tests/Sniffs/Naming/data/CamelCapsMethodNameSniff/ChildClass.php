<?php declare(strict_types=1);

namespace StefnaTest\Sniffs\Naming\data;

require_once('BaseClass.no-testing.php');

final class CamelCapsMethodNameSniff extends BaseClass
{
	public function override_snake_case(): void {}
}
