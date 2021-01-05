<?php declare(strict_types=1);

namespace Stefna\Sniffs\ControlStructures;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

final class TryCatchDeclarationSniff implements Sniff
{
	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return int[]
	 */
	public function register()
	{
		return [
			T_TRY,
			T_CATCH,
			T_FINALLY,
		];
	}//end register()

	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
	 * @param int $stackPtr The position of the current token in the
	 *                                               stack passed in $tokens.
	 *
	 * @return void
	 */
	public function process(File $phpcsFile, $stackPtr)
	{
		$tokens = $phpcsFile->getTokens();

		$nextNonEmpty = $phpcsFile->findNext(Tokens::$emptyTokens, ($stackPtr + 1), null, true);
		if ($nextNonEmpty === false) {
			return;
		}

		// Single space after the keyword.
		$expected = 1;

		$found = 1;
		if ($tokens[($stackPtr + 1)]['code'] !== T_WHITESPACE) {
			$found = 0;
		}
		else {
			if ($tokens[($stackPtr + 1)]['content'] !== ' ') {
				if (strpos($tokens[($stackPtr + 1)]['content'], $phpcsFile->eolChar) !== false) {
					$found = 'newline';
				}
				else {
					$found = $tokens[($stackPtr + 1)]['length'];
				}
			}
		}

		if ($found !== $expected) {
			$error = 'Expected %s space(s) after %s keyword; %s found';
			$data = [
				$expected,
				strtoupper($tokens[$stackPtr]['content']),
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
		if (isset($tokens[$stackPtr]['parenthesis_closer']) === true &&
			isset($tokens[$stackPtr]['scope_opener']) === true
		) {
			$expected = 1;

			$closer = $tokens[$stackPtr]['parenthesis_closer'];
			$opener = $tokens[$stackPtr]['scope_opener'];
			$content = $phpcsFile->getTokensAsString(($closer + 1), ($opener - $closer - 1));

			if (trim($content) === '') {
				if (strpos($content, $phpcsFile->eolChar) !== false) {
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
							$phpcsFile->fixer->addContent($closer, $padding . $tokens[$opener]['content']);
							$phpcsFile->fixer->replaceToken($opener, '');

							if ($tokens[$opener]['line'] !== $tokens[$closer]['line']) {
								$next = $phpcsFile->findNext(T_WHITESPACE, ($opener + 1), null, true);
								if ($tokens[$next]['line'] !== $tokens[$opener]['line']) {
									for ($i = ($opener + 1); $i < $next; $i++) {
										$phpcsFile->fixer->replaceToken($i, '');
									}
								}
							}
						}

						$phpcsFile->fixer->endChangeset();
					}//end if
				}//end if
			}//end if
		}//end if

		// Single newline after opening brace.
		if (isset($tokens[$stackPtr]['scope_opener']) === true) {
			$opener = $tokens[$stackPtr]['scope_opener'];
			for ($next = ($opener + 1); $next < $phpcsFile->numTokens; $next++) {
				$code = $tokens[$next]['code'];

				if ($code === T_WHITESPACE ||
					($code === T_INLINE_HTML && trim($tokens[$next]['content']) === '')
				) {
					continue;
				}

				// Skip all empty tokens on the same line as the opener.
				if ($tokens[$next]['line'] === $tokens[$opener]['line']
					&& (isset(Tokens::$emptyTokens[$code]) === true
						|| $code === T_CLOSE_TAG)
				) {
					continue;
				}

				// We found the first bit of a code, or a comment on the
				// following line.
				break;
			}//end for


			if ($tokens[$next]['line'] === $tokens[$opener]['line'] &&
				$tokens[$next]['column'] !== $tokens[$opener]['column'] + 1
			) {
				$error = 'Newline required after opening brace';
				$fix = $phpcsFile->addFixableError($error, $opener, 'NewlineAfterOpenBrace');
				if ($fix === true) {
					$phpcsFile->fixer->beginChangeset();
					for ($i = ($opener + 1); $i < $next; $i++) {
						if (trim($tokens[$i]['content']) !== '') {
							break;
						}

						// Remove whitespace.
						$phpcsFile->fixer->replaceToken($i, '');
					}

					$phpcsFile->fixer->addContent($opener, $phpcsFile->eolChar);
					$phpcsFile->fixer->endChangeset();
				}
			}//end if
		}//end if

		if ($tokens[$stackPtr]['code'] === T_CATCH ||
			$tokens[$stackPtr]['code'] === T_FINALLY
		) {
			$closer = $phpcsFile->findPrevious(Tokens::$emptyTokens, ($stackPtr - 1), null, true);
			if ($closer === false || $tokens[$closer]['code'] !== T_CLOSE_CURLY_BRACKET) {
				return;
			}
		}
		else {
			return;
		}//end if

		// Single newline after closing brace.
		$found = 1;
		if ($tokens[($closer + 1)]['code'] !== T_WHITESPACE) {
			$found = 0;
		}
		else {
			if ($tokens[$closer]['line'] !== $tokens[$stackPtr]['line']) {
				$found = 1;
			}
			else {
				if ($tokens[($closer + 1)]['content'] !== ' ') {
					$found = $tokens[($closer + 1)]['length'];
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
	}//end process()
}//end class
