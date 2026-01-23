<?php declare(strict_types=1);

namespace Stefna\Sniffs\Functions;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use Stefna\Utils\TokenCollection;

final class EmptyFunctionBodySniff implements Sniff
{

	public function register(): array
	{
		return [
			T_FUNCTION,
		];
	}

	public function process(File $phpcsFile, int $stackPtr): void
	{
		$tokens = new TokenCollection($phpcsFile->getTokens());

		if (!$tokens->hasScopeOpener($stackPtr) || !$tokens->hasScopeCloser($stackPtr)) {
			// Ignore pure definitions
			return;
		}

		$scopeOpener = $tokens->scopeOpener($stackPtr);
		$scopeCloser = $tokens->scopeCloser($stackPtr);

		$next = $phpcsFile->findNext(T_WHITESPACE,  $scopeOpener+1, exclude: true);

		if ($next == $scopeCloser) {
			if ($scopeCloser !== $scopeOpener + 1) {
				$error = 'Whitespace not allowed between braces on empty method';
				$fix = $phpcsFile->addFixableError($error, $scopeOpener, 'WhiteSpaceBetweenBraces');
				if ($fix) {
					$phpcsFile->fixer->beginChangeset();
					for ($i = $scopeOpener + 1; $i < $scopeCloser; $i++) {
						$phpcsFile->fixer->replaceToken($i, '');
					}
					$phpcsFile->fixer->endChangeset();
				}
			}
		}
	}
}
