<?php declare(strict_types=1);

namespace StefnaTest\Sniffs\Naming\data;

require_once('BaseInterface.no-testing.php');

final class CamelCapsMethodNameSniff implements BaseInterface
{
	public function interface_snake_case(): void {}
}
