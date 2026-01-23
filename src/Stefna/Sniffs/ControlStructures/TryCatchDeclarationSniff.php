<?php declare(strict_types=1);

namespace Stefna\Sniffs\ControlStructures;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;
use Stefna\Utils\TokenCollection;

final class TryCatchDeclarationSniff implements Sniff
{
	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return int[]
	 */
	public function register(): array
	{
		return [
			T_TRY,
			T_CATCH,
			T_FINALLY,
		];
	}

	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @param File $phpcsFile The file being scanned.
	 * @param int $stackPtr The position of the current token in the stack passed in $tokens.
	 */
	public function process(File $phpcsFile, int $stackPtr): void
	{
		$tokens = new TokenCollection($phpcsFile->getTokens());

		$nextNonEmpty = $phpcsFile->findNext(Tokens::$emptyTokens, ($stackPtr + 1), null, true);
		if ($nextNonEmpty === false) {
			return;
		}

		// Single space after the keyword.
		$expected = 1;

		$found = 1;
		if ($tokens->code($stackPtr + 1) !== T_WHITESPACE) {
			$found = 0;
		}
		else {
			if ($tokens->content($stackPtr + 1) !== ' ') {
				if (str_contains($tokens->content($stackPtr + 1), $phpcsFile->eolChar)) {
					$found = 'newline';
				}
				else {
					$found = $tokens->length($stackPtr + 1);
				}
			}
		}

		if ($found !== $expected) {
			$error = 'Expected %s space(s) after %s keyword; %s found';
			$data = [
				$expected,
				strtoupper($tokens->content($stackPtr)),
				$found,
			];

			$fix = $phpcsFile->addFixableError($error, $stackPtr, 'SpaceAfterKeyword', $data);
			if ($fix === true) {
				if ($found === 0) {
					$phpcsFile->fixer->addContent($stackPtr, str_repeat(' ', $expected));
				}
				else {
					$phpcsFile->fixer->replaceToken(($stackPtr + 1), str_repeat(' ', $expected));
				}
			}
		}

		// Single space after closing parenthesis.
		if (
			$tokens->hasParenthesisCloser($stackPtr)
			&& $tokens->hasScopeOpener($stackPtr)
		) {
			$expected = 1;

			$closer = $tokens->parenthesisCloser($stackPtr);
			$opener = $tokens->scopeOpener($stackPtr);
			$content = $phpcsFile->getTokensAsString(($closer + 1), ($opener - $closer - 1));

			if (trim($content) === '') {
				if (str_contains($content, $phpcsFile->eolChar)) {
					$found = 'newline';
				}
				else {
					$found = strlen($content);
				}
			}
			else {
				$found = '"' . str_replace($phpcsFile->eolChar, '\n', $content) . '"';
			}

			if ($found !== $expected) {
				$error = 'Expected %s space(s) after closing parenthesis; found %s';
				$data = [
					$expected,
					$found,
				];

				$fix = $phpcsFile->addFixableError($error, $closer, 'SpaceAfterCloseParenthesis', $data);
				if ($fix === true) {
					$padding = str_repeat(' ', $expected);
					if ($closer === ($opener - 1)) {
						$phpcsFile->fixer->addContent($closer, $padding);
					}
					else {
						$phpcsFile->fixer->beginChangeset();
						if (trim($content) === '') {
							$phpcsFile->fixer->addContent($closer, $padding);
							if ($found !== 0) {
								for ($i = ($closer + 1); $i < $opener; $i++) {
									$phpcsFile->fixer->replaceToken($i, '');
								}
							}
						}
						else {
							$phpcsFile->fixer->addContent($closer, $padding . $tokens->content($opener));
							$phpcsFile->fixer->replaceToken($opener, '');

							if (!$tokens->sameLine($opener, $closer)) {
								$next = $phpcsFile->findNext(T_WHITESPACE, ($opener + 1), null, true);
								if (!$tokens->sameLine($next, $opener)) {
									for ($i = ($opener + 1); $i < $next; $i++) {
										$phpcsFile->fixer->replaceToken($i, '');
									}
								}
							}
						}

						$phpcsFile->fixer->endChangeset();
					}
				}
			}
		}

		// Single newline after opening brace.
		if ($tokens->hasScopeOpener($stackPtr)) {
			$opener = $tokens->scopeOpener($stackPtr);
			for ($next = ($opener + 1); $next < $phpcsFile->numTokens; $next++) {
				$code = $tokens->code($next);

				if (
					$code === T_WHITESPACE ||
					($code === T_INLINE_HTML && trim($tokens->content($next)) === '')
				) {
					continue;
				}

				// Skip all empty tokens on the same line as the opener.
				if (
					$tokens->sameLine($next, $opener)
					&& (isset(Tokens::$emptyTokens[$code]) === true
						|| $code === T_CLOSE_TAG)
				) {
					continue;
				}

				// We found the first bit of a code, or a comment on the
				// following line.
				break;
			}

			if (
				$tokens->sameLine($next, $opener) &&
				$tokens->column($next) !== $tokens->column($opener) + 1
			) {
				$error = 'Newline required after opening brace';
				$fix = $phpcsFile->addFixableError($error, $opener, 'NewlineAfterOpenBrace');
				if ($fix === true) {
					$phpcsFile->fixer->beginChangeset();
					for ($i = ($opener + 1); $i < $next; $i++) {
						if (trim($tokens->content($i)) !== '') {
							break;
						}

						// Remove whitespace.
						$phpcsFile->fixer->replaceToken($i, '');
					}

					$phpcsFile->fixer->addContent($opener, $phpcsFile->eolChar);
					$phpcsFile->fixer->endChangeset();
				}
			}
		}

		if (
			$tokens->code($stackPtr) === T_CATCH ||
			$tokens->code($stackPtr) === T_FINALLY
		) {
			$closer = $phpcsFile->findPrevious(Tokens::$emptyTokens, ($stackPtr - 1), null, true);
			if ($closer === false || $tokens->code($closer) !== T_CLOSE_CURLY_BRACKET) {
				return;
			}
		}
		else {
			return;
		}

		$this->emptyBodyHandler($phpcsFile, $stackPtr, $tokens);

		// Single newline after closing brace.
		$found = 1;
		if ($tokens->code($closer + 1) !== T_WHITESPACE) {
			$found = 0;
		}
		else {
			if (!$tokens->sameLine($closer, $stackPtr)) {
				$found = 1;
			}
			else {
				if ($tokens->content($closer + 1) !== ' ') {
					$found = $tokens->length($closer + 1);
				}
			}
		}

		if ($found !== 1) {
			$error = 'Expected 1 space after closing brace; %s found';
			$data = [$found];

			if ($phpcsFile->findNext(Tokens::$commentTokens, ($closer + 1), $stackPtr) !== false) {
				// Comment found between closing brace and keyword, don't auto-fix.
				$phpcsFile->addError($error, $closer, 'SpaceAfterCloseBrace', $data);
				return;
			}

			$fix = $phpcsFile->addFixableError($error, $closer, 'SpaceAfterCloseBrace', $data);
			if ($fix === true) {
				if ($found === 0) {
					$phpcsFile->fixer->addContent($closer, ' ');
				}
				else {
					$phpcsFile->fixer->replaceToken(($closer + 1), ' ');
				}
			}
		}
	}

	private function emptyBodyHandler(File $phpcsFile, int $stackPtr, TokenCollection $tokens): void
	{
		if (!$tokens->hasScopeOpener($stackPtr) || !$tokens->hasScopeCloser($stackPtr)) {
			// Ignore pure definitions
			return;
		}

		$scopeOpener = $tokens->scopeOpener($stackPtr);
		$scopeCloser = $tokens->scopeCloser($stackPtr);

		$next = $phpcsFile->findNext(T_WHITESPACE, $scopeOpener + 1, exclude: true);

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
