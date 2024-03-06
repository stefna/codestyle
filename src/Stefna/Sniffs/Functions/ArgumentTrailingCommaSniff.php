<?php declare(strict_types=1);

namespace Stefna\Sniffs\Functions;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

final class ArgumentTrailingCommaSniff implements Sniff
{
	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array
	 */
	public function register()
	{
		return [
			T_FUNCTION,
			T_CLOSURE,
			T_FN,
		];
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
		$functionToken = $tokens[$stackPtr];
		$argumentStartPtr = $functionToken['parenthesis_opener'];
		$argumentEndPtr = $functionToken['parenthesis_closer'];
		$argumentStartToken = $tokens[$argumentStartPtr];
		$argumentEndToken = $tokens[$argumentEndPtr];

		if ($argumentStartToken['line'] === $argumentEndToken['line']) {
			// one line function definition
			return;
		}

		$lastArgumentPtr = $phpcsFile->findPrevious(
			[T_WHITESPACE],
			$argumentEndPtr - 1,
			$argumentStartPtr,
			true,
		);

		if ($lastArgumentPtr === false) {
			return;
		}

		if ($tokens[$lastArgumentPtr]['code'] === T_COMMA) {
			// correct formatting
			return;
		}

		$fix = $phpcsFile->addFixableError(
			'Multi-line function declarations must always end with a comma ","',
			$lastArgumentPtr,
			'MissingTrailingComma',
		);

		if ($fix) {
			$phpcsFile->fixer->addContent($lastArgumentPtr, ',');
		}
	}
}
