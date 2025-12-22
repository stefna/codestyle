<?php declare(strict_types=1);

$closure = static function () {
	something();
};

$fn = static fn () => something();
