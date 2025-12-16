<?php declare(strict_types=1);

$closure = static function () {
	$this->something();
};

$fn = static fn () => $this->something();
