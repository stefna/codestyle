<?php
/**
 * Ensure single and multi-line function declarations are defined correctly.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace Stefna\Sniffs\Functions;

use PHP_CodeSniffer\Util\Tokens;
use PHP_CodeSniffer\Standards\Squiz\Sniffs\Functions\MultiLineFunctionDeclarationSniff as BaseSniff;

class MultiLineFunctionDeclarationSniff extends BaseSniff
{

	/**
	 * Processes single-line declarations.
	 *
	 * Just uses the Generic BSD-Allman brace sniff.
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
	 * @param int                         $stackPtr  The position of the current token
	 *                                               in the stack passed in $tokens.
	 * @param array                       $tokens    The stack of tokens that make up
	 *                                               the file.
	 *
	 * @return void
	 */
	public function processSingleLineDeclaration($phpcsFile, $stackPtr, $tokens)
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
	}//end processSingleLineDeclaration()

	/**
	 * Processes single-line declarations.
	 *
	 * Just uses the Generic BSD-Allman brace sniff.
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
	 * @param int                         $stackPtr  The position of the current token
	 *                                               in the stack passed in $tokens.
	 * @param array                       $tokens    The stack of tokens that make up
	 *                                               the file.
	 *
	 * @return void
	 */
	private function isOneLineMethod($phpcsFile, $stackPtr, $tokens): bool
	{
		if (!isset($tokens[$stackPtr]['scope_closer'])) {
			// properly an abstract method
			return false;
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
		$lineIsFinal = $tokens[$lineStart]['code'] === T_FINAL;
		$lineStartNr = $lineIsFinal ? $lineStart + 2 : $lineStart;
		$scopeEnd = $tokens[$stackPtr]['scope_closer'];

		// method declaration spans multiple lines
		if ($tokens[$lineStartNr]['line'] !== $tokens[$scopeEnd]['line']) {
			return false;
		}

		if (
			in_array($tokens[$lineStartNr]['code'], [
				T_PUBLIC,
				T_PRIVATE,
				T_PROTECTED,
			], true)
			&& isset($tokens[$lineStartNr + 4]['content'])
		) {
			var_dump($tokens[$lineStartNr + 4]['content']);
			return true;
		}
		return false;
	}
}
