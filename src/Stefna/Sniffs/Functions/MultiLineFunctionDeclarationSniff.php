<?php declare(strict_types=1);

namespace Stefna\Sniffs\Functions;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Standards\Squiz\Sniffs\Functions\MultiLineFunctionDeclarationSniff as BaseSniff;
use Stefna\Utils\TokenCollection;

final class MultiLineFunctionDeclarationSniff extends BaseSniff
{
	public function processSingleLineDeclaration(File $phpcsFile, int $stackPtr, array $tokens): void
	{
		$tokensCollection = new TokenCollection($tokens);

		if ($this->isOneLineMethod($phpcsFile, $stackPtr, $tokensCollection)) {
			$scopeStart = $tokensCollection->scopeOpener($stackPtr);
			$scopeEnd = $tokensCollection->scopeCloser($stackPtr);

			if ($tokensCollection->code($scopeStart - 1) !== T_WHITESPACE) {
				$error = 'Scope start requires 1 space before; found 0';
				$fix = $phpcsFile->addFixableError($error, $scopeStart, 'SpaceBeforeScope');
				if ($fix) {
					$phpcsFile->fixer->addContentBefore($scopeStart, ' ');
				}
			}
			elseif ($tokensCollection->content($scopeStart - 1) != ' ') {
				$error = 'Scope start requires 1 space before; found %s';
				$data = [
					strlen($tokensCollection->content($scopeStart - 1)),
				];
				$fix = $phpcsFile->addFixableError($error, $scopeStart, 'SpaceBeforeScope', $data);
				if ($fix) {
					$phpcsFile->fixer->replaceToken($scopeStart - 1, ' ');
				}
			}

			// allow brackets on same line if body is empty
			if ($scopeEnd === ($scopeStart + 1)) {
				return;
			}
		}

		parent::processSingleLineDeclaration($phpcsFile, $stackPtr, $tokens);
	}

	private function isOneLineMethod(File $phpcsFile, int $stackPtr, TokenCollection $tokens): bool
	{
		if (!$tokens->hasScopeCloser($stackPtr)) {
			// probably an abstract method
			return false;
		}

		// We need to actually find the first piece of content on this line,
		// as if this is a method with tokens before it (public, static etc)
		// or an if with an else before it, then we need to start the scope
		// checking from there, rather than the current token.
		$lineStart = $phpcsFile->findFirstOnLine([T_WHITESPACE, T_INLINE_HTML], $stackPtr, exclude: true);
		while (
			$tokens->code($lineStart) === T_CONSTANT_ENCAPSED_STRING
			&& $tokens->code($lineStart - 1) === T_CONSTANT_ENCAPSED_STRING
		) {
			$lineStart = $phpcsFile->findFirstOnLine([T_WHITESPACE, T_INLINE_HTML], $lineStart - 1, exclude: true);
		}

		$lineIsFinal = $tokens->code($lineStart) === T_FINAL;
		$lineStartPtr = $lineIsFinal ? $lineStart + 2 : $lineStart;
		$scopeEnd = $tokens->scopeCloser($stackPtr);

		if (!$tokens->sameLine($lineStartPtr, $scopeEnd)) {
			// method declaration spans multiple lines
			return false;
		}

		if (
			in_array($tokens->code($lineStartPtr), [
				T_PUBLIC,
				T_PRIVATE,
				T_PROTECTED,
			], true)
			&& $tokens->hasContent($lineStartPtr + 4)
		) {
			return true;
		}
		return false;
	}
}
