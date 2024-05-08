<?php declare(strict_types=1);

namespace Stefna\Sniffs\ControlStructures;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

final class BracketPlacementSniff implements Sniff
{
	public function register()
	{
		return [
			T_DO,
			T_WHILE,
			T_FOR,
			T_IF,
			T_FOREACH,
			T_ELSE,
			T_ELSEIF,
			T_SWITCH,
			T_CATCH,
			T_FINALLY,
		];
	}

	public function process(File $phpcsFile, $stackPtr)
	{
		$tokens = $phpcsFile->getTokens();

		$currentToken = $tokens[$stackPtr];
		$currentLine = $currentToken['line'];

		$previousCloseBracketPtr = $phpcsFile->findPrevious(T_CLOSE_CURLY_BRACKET, $stackPtr - 1);
		if ($previousCloseBracketPtr) {
			$bracketToken = $tokens[$previousCloseBracketPtr];
			if ($currentLine === $bracketToken['line']) {
				$error = 'Closing brace must be on a line by itself';
				$fix = $phpcsFile->addFixableError($error, $previousCloseBracketPtr, 'BracketBeforeControlStatement');
				if ($fix === true) {
					$phpcsFile->fixer->addNewline($previousCloseBracketPtr);
				}
			}
		}

		$nextCommentPtr = $phpcsFile->findNext(Tokens::$commentTokens, $stackPtr);
		if ($nextCommentPtr) {
			$commentToken = $tokens[$nextCommentPtr];
			if ($currentLine === $commentToken['line']) {
				$error = 'Can\'t have comment on same line as control statement';
				$phpcsFile->addError($error, $stackPtr, 'CommentAfterControlStatement');
			}
		}
	}
}
