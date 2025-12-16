<?php declare(strict_types=1);

namespace Stefna\Sniffs\ControlStructures;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;
use Stefna\Utils\TokenCollection;

class ControlSignatureSniff implements Sniff
{
	public function register(): array
	{
		return [
			T_DO,
			T_ELSE,
			T_ELSEIF,
			T_FOR,
			T_FOREACH,
			T_IF,
			T_SWITCH,
			T_WHILE,
		];
	}

	public function process(File $phpcsFile, int $stackPtr): void
	{
		$tokens = new TokenCollection($phpcsFile->getTokens());

		if (!$tokens->has($stackPtr + 1)) {
			return;
		}

		$isAlternative = false;
		if ($tokens->hasScopeOpener($stackPtr) && $tokens->code($tokens->scopeOpener($stackPtr)) === T_COLON) {
			$isAlternative = true;
		}

		// Single newline after opening brace.
		if ($tokens->hasScopeOpener($stackPtr)) {
			$opener = $tokens->scopeOpener($stackPtr);
			for ($next = ($opener + 1); $next < $phpcsFile->numTokens; $next++) {
				$code = $tokens->code($next);

				if ($code === T_WHITESPACE || ($code === T_INLINE_HTML && trim($tokens->content($next)) === '')) {
					continue;
				}

				// Skip all empty tokens on the same line as the opener
				if ($tokens->sameLine($next, $opener) && (isset(Tokens::EMPTY_TOKENS[$code]) || $code === T_CLOSE_TAG)) {
					continue;
				}

				// We found the first bit of a code, or a comment on the following line
				break;
			}

			if ($tokens->sameLine($next, $opener)) {
				$error = 'Newline required after opening brace';
				$fix = $phpcsFile->addFixableError($error, $opener, 'NewlineAfterOpenBrace');
				if ($fix) {
					$phpcsFile->fixer->beginChangeset();
					for ($i = $opener + 1; $i < $next; $i++) {
						if (trim($tokens->content($i)) !== '') {
							break;
						}

						$phpcsFile->fixer->replaceToken($i, '');
					}

					$phpcsFile->fixer->addContent($opener, $phpcsFile->eolChar);
					$phpcsFile->fixer->endChangeset();
				}
			}
		}
		elseif ($tokens->code($stackPtr) === T_WHILE) {
			// Zero spaces after parenthesis closer.
			$closer = $tokens->parenthesisCloser($stackPtr);
			$found = 0;
			if ($tokens->code($closer + 1) === T_WHITESPACE) {
				if (strpos($tokens->content($closer + 1), $phpcsFile->eolChar) !== false) {
					$found = 'newline';
				}
				else {
					$found = strlen($tokens->content($closer + 1));
				}
			}

			if ($found !== 0) {
				$error = 'Expected 0 spaces before semicolon; %s found';
				$data = [$found];
				$fix = $phpcsFile->addFixableError($error, $closer, 'SpaceBeforeSemicolon', $data);
				if ($fix) {
					$phpcsFile->fixer->replaceToken($closer + 1, '');
				}
			}
		}

		// Only want to check multi-keyword structures from here on.
		if ($tokens->code($stackPtr) === T_WHILE) {
			if (!$tokens->hasScopeCloser($stackPtr)) {
				return;
			}

			$closer = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, $stackPtr - 1, exclude: true);
			if ($closer === false || $tokens->code($closer) !== T_CLOSE_CURLY_BRACKET || $tokens->code($tokens->scopeCondition($closer)) !== T_DO) {
				return;
			}
		}
		elseif (in_array($tokens->code($stackPtr), [T_ELSE, T_ELSEIF, T_CATCH, T_FINALLY], true)) {
			if ($tokens->hasScopeOpener($stackPtr) && $tokens->code($tokens->scopeOpener($stackPtr)) === T_COLON) {
				// Special case for alternative syntax, where this token is actually
				// the closer for the previous block, so there is no spacing to check.
				return;
			}

			$closer = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, $stackPtr - 1, exclude: true);
			if ($closer === false || $tokens->code($closer) !== T_CLOSE_CURLY_BRACKET) {
				return;
			}
		}
		else {
			return;
		}

		// Single space after closing brace.
		$found = 1;
		if ($tokens->code($closer + 1) !== T_WHITESPACE) {
			$found = 0;
		}
		elseif (!$tokens->sameLine($closer, $stackPtr)) {
			// Custom to allow newline before else and catch
			$found = 1;
		}
		elseif ($tokens->content($closer + 1) !== ' ') {
			$found = strlen($tokens->content($closer + 1));
		}

		if ($found !== 1) {
			$error = 'Expected 1 space after closing brace; %s found';
			$data = [$found];

			if ($phpcsFile->findNext(Tokens::COMMENT_TOKENS, $closer + 1, $stackPtr) !== false) {
				// Comment found between closing brace and keyword, don't auto-fix.
				$phpcsFile->addError($error, $closer, 'SpaceAfterCloseBrace', $data);
				return;
			}

			$fix = $phpcsFile->addFixableError($error, $closer, 'SpaceAfterCloseBrace', $data);
			if ($fix) {
				if ($found === 0) {
					$phpcsFile->fixer->addContent($closer, ' ');
				}
				else {
					$phpcsFile->fixer->replaceToken($closer + 1, ' ');
				}
			}
		}
	}
}
