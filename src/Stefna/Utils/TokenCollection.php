<?php declare(strict_types=1);

namespace Stefna\Utils;

class TokenCollection
{
	/**
	 * @param array<int,mixed> $tokens
	 */
	public function __construct(
		private array $tokens,
	) {}

	public function code(int $stackPtr): int|string
	{
		return $this->tokens[$stackPtr]['code'];
	}

	public function content(int $stackPtr): string
	{
		return $this->tokens[$stackPtr]['content'];
	}

	public function line(int $stackPtr): int
	{
		return $this->tokens[$stackPtr]['line'];
	}

	public function parenthesisCloser(int $stackPtr): int
	{
		return $this->tokens[$stackPtr]['parenthesis_closer'];
	}

	public function scopeCondition(int $stackPtr): int
	{
		return $this->tokens[$stackPtr]['scope_condition'];
	}

	public function scopeOpener(int $stackPtr): int
	{
		return $this->tokens[$stackPtr]['scope_opener'];
	}

	public function has(int $stackPtr): bool
	{
		return isset($this->tokens[$stackPtr]);
	}

	public function hasScopeCloser(int $stackPtr): bool
	{
		return isset($this->tokens[$stackPtr]['scope_closer']);
	}

	public function hasScopeOpener(int $stackPtr): bool
	{
		return isset($this->tokens[$stackPtr]['scope_opener']);
	}

	public function hasParenthesisCloser(int $stackPtr): bool
	{
		return isset($this->tokens[$stackPtr]['parenthesis_closer']);
	}


	public function sameLine(int $firstPtr, int $secondPtr): bool
	{
		return $this->tokens[$firstPtr]['line'] === $this->tokens[$secondPtr]['line'];
	}
}
