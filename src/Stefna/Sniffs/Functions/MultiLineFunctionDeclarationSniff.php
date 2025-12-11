<?php declare(strict_types=1);

namespace Stefna\Sniffs\Functions;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Standards\Squiz\Sniffs\Functions\MultiLineFunctionDeclarationSniff as BaseSniff;

final class MultiLineFunctionDeclarationSniff extends BaseSniff
{
	public function processSingleLineDeclaration(File $phpcsFile, int $stackPtr, array $tokens): void
	{
		if ($this->isOneLineMethod($phpcsFile, $stackPtr, $tokens)) {
			$scopeStart = $tokens[$stackPtr]['scope_opener'];
			$scopeEnd = $tokens[$stackPtr]['scope_closer'];

			// allow brackets on same line if body is empty
			if ($scopeEnd === ($scopeStart + 1)) {
				return;
			}

			if (($scopeEnd - $scopeStart) === 2 && $tokens[$scopeStart + 1]['code'] === T_WHITESPACE) {
				$error = 'Whitespace not allowed between braces on empty method';
				$fix = $phpcsFile->addFixableError($error, $scopeEnd, 'WhiteSpaceBetweenBraces');
				if ($fix === true) {
					$phpcsFile->fixer->replaceToken($scopeStart + 1, '');
				}
				return;
			}
		}

		parent::processSingleLineDeclaration($phpcsFile, $stackPtr, $tokens);
	}
	/**
	 * @param array $tokens
	 */
	private function isOneLineMethod(File $phpcsFile, int $stackPtr, array $tokens): bool
	{
		if (!isset($tokens[$stackPtr]['scope_closer'])) {
			// probably an abstract method
			return false;
		}

		// We need to actually find the first piece of content on this line,
		// as if this is a method with tokens before it (public, static etc)
		// or an if with an else before it, then we need to start the scope
		// checking from there, rather than the current token.
		$lineStart = $phpcsFile->findFirstOnLine([T_WHITESPACE, T_INLINE_HTML], $stackPtr, exclude: true);
		while ($tokens[$lineStart]['code'] === T_CONSTANT_ENCAPSED_STRING
		&& $tokens[$lineStart - 1]['code'] === T_CONSTANT_ENCAPSED_STRING) {
			$lineStart = $phpcsFile->findFirstOnLine([T_WHITESPACE, T_INLINE_HTML], $lineStart - 1, exclude: true);
		}

		$lineIsFinal = $tokens[$lineStart]['code'] === T_FINAL;
		$lineStartPtr = $lineIsFinal ? $lineStart + 2 : $lineStart;
		$scopeEnd = $tokens[$stackPtr]['scope_closer'];

		if ($tokens[$lineStartPtr]['line'] !== $tokens[$scopeEnd]['line']) {
			// method declaration spans multiple lines
			return false;
		}

		if (
			in_array($tokens[$lineStartPtr]['code'], [
				T_PUBLIC,
				T_PRIVATE,
				T_PROTECTED,
			], true)
			&& isset($tokens[$lineStartPtr + 4]['content'])
		) {
			return true;
		}
		return false;
	}
}
