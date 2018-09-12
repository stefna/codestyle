<?php

if (!file_exists('composer.json')) {
	die('No composer.json found');
}

$composer = json_decode(file_get_contents('composer.json'), true);

if (isset($composer['scripts']['check'])) {
	die('Composer file already have scripts check');
}

if (!isset($composer['scripts'])) {
	$composer['scripts'] = [];
}

$args = '--standard=vendor/stefna/codestyle/library.xml src/ tests/';

$composer['scripts']['check'] = './vendor/bin/phpcs ' . $args;
$composer['scripts']['fix'] = './vendor/bin/phpcbf ' . $args;

file_put_contents('composer.json', json_encode($composer));
