<?php declare(strict_types=1);

namespace StefnaTest\Sniffs\ControlStructures;

try {
	// do stuff
}
catch (\Throwable $e) {}

try {
	// do stuff
}
catch (\Throwable $e) {
	// Do nothing
}
