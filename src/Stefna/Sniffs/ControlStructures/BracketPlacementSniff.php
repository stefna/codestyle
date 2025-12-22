<?php declare(strict_types=1);

namespace Stefna\Sniffs\ControlStructures;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use Stefna\Utils\TokenCollection;

final class BracketPlacementSniff implements Sniff
{
	public function register(): array
	{
		return [
			T_CATCH,
			T_DO,
			T_ELSE,
			T_ELSEIF,
			T_FINALLY,
			T_FOR,
			T_FOREACH,
			T_IF,
			T_SWITCH,
			T_TRY,
			T_WHILE,
		];
	}

	public function process(File $phpcsFile, int $stackPtr): void
	{
		$tokens = new TokenCollection($phpcsFile->getTokens());

		$previousTokenPtr = $phpcsFile->findPrevious(T_WHITESPACE, $stackPtr - 1, exclude: true);
		if ($previousTokenPtr) {
			if ($tokens->sameLine($previousTokenPtr, $stackPtr)) {
				$error = 'Control statement needs to be own it\'s own line';
				$fix = $phpcsFile->addFixableError($error, $previousTokenPtr, 'ControlStamentNotAlone');
				if ($fix) {
					if ($tokens->code($stackPtr - 1) === T_WHITESPACE) {
						$phpcsFile->fixer->replaceToken($stackPtr - 1, $phpcsFile->eolChar);
					}
					else {
						$phpcsFile->fixer->addNewline($previousTokenPtr);
					}
				}
			}
		}

		if ($tokens->hasScopeCloser($stackPtr)) {
			$scopeCloserPtr = $tokens->scopeCloser($stackPtr);

			$previousTokenPtr = $phpcsFile->findPrevious(T_WHITESPACE, $scopeCloserPtr - 1, exclude: true);
			if ($previousTokenPtr) {
				if ($tokens->sameLine($previousTokenPtr, $scopeCloserPtr) && $tokens->code($previousTokenPtr) !== T_OPEN_CURLY_BRACKET) {
					$error = 'Closing brace must be on a line by itself';
					$fix = $phpcsFile->addFixableError($error, $previousTokenPtr, 'ClosingBracketNotAlone');
					if ($fix) {
						$phpcsFile->fixer->addNewline($previousTokenPtr);
					}
				}
			}
		}
	}
}
