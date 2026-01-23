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

	public function bracketCloser(int $stackPtr): int
	{
		return $this->tokens[$stackPtr]['bracket_closer'];
	}

	public function bracketOpener(int $stackPtr): int
	{
		return $this->tokens[$stackPtr]['bracket_opener'];
	}

	public function code(int $stackPtr): int|string
	{
		return $this->tokens[$stackPtr]['code'];
	}

	public function commentCloser(int $stackPtr): int
	{
		return $this->tokens[$stackPtr]['comment_closer'];
	}

	public function commentOpener(int $stackPtr): int
	{
		return $this->tokens[$stackPtr]['comment_opener'];
	}

	public function column(int $stackPtr): int
	{
		return $this->tokens[$stackPtr]['column'];
	}

	public function content(int $stackPtr): string
	{
		return $this->tokens[$stackPtr]['content'];
	}

	public function length(int $stackPtr): int
	{
		return $this->tokens[$stackPtr]['length'];
	}

	public function line(int $stackPtr): int
	{
		return $this->tokens[$stackPtr]['line'];
	}

	public function nestedParenthesis(int $stackPtr): int
	{
		return $this->tokens[$stackPtr]['nestedParenthesis'];
	}

	public function parenthesisCloser(int $stackPtr): int
	{
		return $this->tokens[$stackPtr]['parenthesis_closer'];
	}

	public function parenthesisOpener(int $stackPtr): int
	{
		return $this->tokens[$stackPtr]['parenthesis_opener'];
	}

	public function scopeCloser(int $stackPtr): int
	{
		return $this->tokens[$stackPtr]['scope_closer'];
	}

	public function scopeCondition(int $stackPtr): int
	{
		return $this->tokens[$stackPtr]['scope_condition'];
	}

	public function scopeOpener(int $stackPtr): int
	{
		return $this->tokens[$stackPtr]['scope_opener'];
	}

	public function sniffCode(int $stackPtr): string
	{
		return $this->tokens[$stackPtr]['sniffCode'];
	}

	public function sniffProperty(int $stackPtr): string
	{
		return $this->tokens[$stackPtr]['sniffProperty'];
	}

	public function sniffPropertyValue(int $stackPtr): string
	{
		return $this->tokens[$stackPtr]['sniffPropertyValue'];
	}

	public function type(int $stackPtr): string
	{
		return $this->tokens[$stackPtr]['type'];
	}

	public function has(int $stackPtr): bool
	{
		return isset($this->tokens[$stackPtr]);
	}

	public function hasContent(int $stackPtr): bool
	{
		return isset($this->tokens[$stackPtr]['content']);
	}

	public function hasScopeCloser(int $stackPtr): bool
	{
		return isset($this->tokens[$stackPtr]['scope_closer']);
	}

	public function hasScopeCondition(int $stackPtr): bool
	{
		return isset($this->tokens[$stackPtr]['scope_condition']);
	}

	public function hasScopeOpener(int $stackPtr): bool
	{
		return isset($this->tokens[$stackPtr]['scope_opener']);
	}

	public function hasNestedParenthesis(int $stackPtr): bool
	{
		return isset($this->tokens[$stackPtr]['nestedParenthesis']);
	}

	public function hasParenthesisCloser(int $stackPtr): bool
	{
		return isset($this->tokens[$stackPtr]['parenthesis_closer']);
	}

	public function hasParenthesisOpener(int $stackPtr): bool
	{
		return isset($this->tokens[$stackPtr]['parenthesis_opener']);
	}

	public function hasSniffCode(int $stackPtr): bool
	{
		return isset($this->tokens[$stackPtr]['sniffCode']);
	}

	public function sameLine(int $firstPtr, int $secondPtr): bool
	{
		return $this->tokens[$firstPtr]['line'] === $this->tokens[$secondPtr]['line'];
	}
}
