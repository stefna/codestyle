<?php declare(strict_types=1);

namespace Tests\Examples;

class Example
{
	private bool $modified = false;

	public string $foo = 'default value' {
		get {
			if ($this->modified) {
				return $this->foo . ' (modified)';
			}
			return $this->foo;
		}
		set(string $value) {
			$this->foo = strtolower($value);
			$this->modified = true;
		}
	}
}

$example = new Example();
$example->foo = 'changed';
print $example->foo;
