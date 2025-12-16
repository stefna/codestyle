<?php declare(strict_types=1);

$closure = function () {
	$this->something();
};

$fn = fn () => $this->something();
