<?php declare(strict_types=1);

namespace Stefna\Sniffs\ControlStructures;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

final class ScopeClosingBraceSniff implements Sniff
{
	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array
	 */
	public function register()
	{
		return Tokens::$scopeOpeners;
	}//end register()

	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile All the tokens found in the document.
	 * @param int $stackPtr The position of the current token in the stack passed in $tokens.
	 *
	 * @return void
	 */
	public function process(File $phpcsFile, $stackPtr)
	{
		$tokens = $phpcsFile->getTokens();

		// If this is an inline condition (ie. there is no scope opener), then
		// return, as this is not a new scope.
		if (isset($tokens[$stackPtr]['scope_closer']) === false) {
			return;
		}

		// We need to actually find the first piece of content on this line,
		// as if this is a method with tokens before it (public, static etc)
		// or an if with an else before it, then we need to start the scope
		// checking from there, rather than the current token.
		$lineStart = $phpcsFile->findFirstOnLine([T_WHITESPACE, T_INLINE_HTML], $stackPtr, true);
		while ($tokens[$lineStart]['code'] === T_CONSTANT_ENCAPSED_STRING
			&& $tokens[($lineStart - 1)]['code'] === T_CONSTANT_ENCAPSED_STRING
		) {
			$lineStart = $phpcsFile->findFirstOnLine([T_WHITESPACE, T_INLINE_HTML], ($lineStart - 1), true);
		}

		// Check if first token is a closing bracket and if so find next
		// this is because the check for closing bracket is done in BracketPlacementSniff
		// it also fixes the issue
		$indentCheck = true;
		if ($tokens[$lineStart]['code'] === T_CLOSE_CURLY_BRACKET) {
			$lineStart = $phpcsFile->findNext([T_WHITESPACE, T_CLOSE_CURLY_BRACKET], $lineStart, exclude: true);
			$indentCheck = false;
		}

		$startColumn = $tokens[$lineStart]['column'];
		$scopeStart = $tokens[$stackPtr]['scope_opener'];
		$scopeEnd = $tokens[$stackPtr]['scope_closer'];

		// Check that the closing brace is on it's own line.
		$lastContent = $phpcsFile->findPrevious([T_INLINE_HTML, T_WHITESPACE, T_OPEN_TAG], ($scopeEnd - 1), $scopeStart, true);
		if ($tokens[$lastContent]['line'] === $tokens[$scopeEnd]['line']) {

			$abortWithError = true;

			// Allow empty catch statements if using php8
			if ($tokens[$lineStart]['code'] === T_CATCH &&
				$tokens[$scopeEnd]['column'] === $tokens[$scopeStart]['column'] + 1 &&
				isset($tokens[$lineStart + 2]['parenthesis_closer']) &&
				$phpcsFile->findNext(
					[T_WHITESPACE],
					$lineStart + 2,
					$tokens[$lineStart + 2]['parenthesis_closer']
				) === false
			) {
				$abortWithError = false;
			}

			// Allow anonymous classes to be throw on a single line
			if ($tokens[$lineStart]['code'] === T_THROW &&
				isset($tokens[$lineStart + 4]) &&
				$tokens[$lineStart + 4]['code'] === T_ANON_CLASS
			) {
				$abortWithError = false;
			}

			$lineIsFinal = $tokens[$lineStart]['code'] === T_FINAL;
			$lineStartNr = $lineIsFinal ? $lineStart + 2 : $lineStart;

			if (
				in_array($tokens[$lineStartNr]['code'], [
					T_PUBLIC,
					T_PRIVATE,
					T_PROTECTED,
				], true)
				&& isset($tokens[$lineStartNr + 4]['content'])
				&& $tokens[$lineStartNr + 4]['content'] === '__construct'
			) {
				$abortWithError = false;
			}

			if ($abortWithError) {
				$error = 'Closing brace must be on a line by itself';
				$fix = $phpcsFile->addFixableError($error, $scopeEnd, 'ContentBefore');
				if ($fix === true) {
					$phpcsFile->fixer->addNewlineBefore($scopeEnd);
				}

				return;
			}
		}

		if ($tokens[$stackPtr]['code'] === T_FUNCTION) {
			$funcToken = $tokens[$stackPtr];
			$funcNamePtr = $phpcsFile->findNext([T_STRING], $stackPtr);
			$openingCurly = $tokens[$funcToken['scope_opener']];
			$closingCurly = $tokens[$funcToken['scope_closer']];
			if ($tokens[$funcNamePtr]['content'] === '__construct') {
				$this->checkCtorPropertyPromotions($tokens, $funcToken, $phpcsFile);
				if (
					$openingCurly['line'] !== $closingCurly['line']
					|| $openingCurly['column'] !== $closingCurly['column'] - 1
				) {
					$this->checkCtorEmptyBody($tokens, $funcToken, $phpcsFile);
				}
			}
		}

		if (!$indentCheck) {
			return;
		}

		// Check now that the closing brace is lined up correctly.
		$lineStart = $phpcsFile->findFirstOnLine([T_WHITESPACE, T_INLINE_HTML], $scopeEnd, true);
		$braceIndent = $tokens[$lineStart]['column'];
		if ($tokens[$stackPtr]['code'] !== T_DEFAULT
			&& $tokens[$stackPtr]['code'] !== T_CASE
			&& $braceIndent !== $startColumn
		) {
			$error = 'Closing brace indented incorrectly; expected %s spaces, found %s';
			$data = [
				($startColumn - 1),
				($braceIndent - 1),
			];

			$fix = $phpcsFile->addFixableError($error, $scopeEnd, 'Indent', $data);
			if ($fix === true) {
				$diff = ($startColumn - $braceIndent);
				if ($diff > 0) {
					$phpcsFile->fixer->addContentBefore($lineStart, str_repeat(' ', $diff));
				}
				else {
					$phpcsFile->fixer->substrToken(($lineStart - 1), 0, $diff);
				}
			}
		}//end if

	}//end process()

	private function checkCtorPropertyPromotions(array $tokens, array $funcToken, File $phpcsFile): void
	{
		$occupiedLines = [
			$funcToken['line'],
		];
		for ($i = $funcToken['parenthesis_opener'] + 1; $i < $funcToken['parenthesis_closer']; $i++) {
			if (in_array($tokens[$i]['code'], Tokens::$scopeModifiers, true)) {
				if (in_array($tokens[$i]['line'], $occupiedLines, true)) {
					$fix = $phpcsFile->addFixableError(
						'Property promotions needs to be on a separate line',
						$i,
						'ConstructorPropertyPromotion',
					);

					if ($fix) {
						$phpcsFile->fixer->addNewlineBefore($i);
					}
				}
				else {
					$occupiedLines[] = $tokens[$i]['line'];
				}
			}
		}
	}

	private function checkCtorEmptyBody(array $tokens, array $funcToken, File $phpcsFile): void
	{
		$emptyBody = true;
		for ($i = $funcToken['scope_opener'] + 1; $i < $funcToken['scope_closer']; $i++) {
			if ($tokens[$i]['code'] !== T_WHITESPACE) {
				$emptyBody = false;
				break;
			}
		}

		if (!$emptyBody) {
			return;
		}

		$error = 'Constructor with only property promotions should have the curly brackets next to each other';
		$fix = $phpcsFile->addFixableError($error, $funcToken['scope_opener'], 'ConstructorBrackets');

		if (!$fix) {
			return;
		}

		for ($i = $funcToken['scope_opener'] + 1; $i < $funcToken['scope_closer']; $i++) {
			if ($tokens[$i]['code'] === T_WHITESPACE) {
				$phpcsFile->fixer->replaceToken($i, '');
			}
		}
	}
}//end class
