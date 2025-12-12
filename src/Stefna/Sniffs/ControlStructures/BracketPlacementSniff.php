<?php declare(strict_types=1);

namespace Stefna\Sniffs\ControlStructures;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

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
		$tokens = $phpcsFile->getTokens();

		$currentToken = $tokens[$stackPtr];
		$currentLine = $currentToken['line'];

		$previousCloseBracketPtr = $phpcsFile->findPrevious(T_CLOSE_CURLY_BRACKET, $stackPtr - 1);
		if ($previousCloseBracketPtr) {
			$bracketToken = $tokens[$previousCloseBracketPtr];
			if ($currentLine === $bracketToken['line']) {
				$error = 'Closing brace must be on a line by itself';
				$fix = $phpcsFile->addFixableError($error, $previousCloseBracketPtr, 'BracketBeforeControlStatement');
				if ($fix) {
					$phpcsFile->fixer->addNewline($previousCloseBracketPtr);
				}
			}
		}

		$nextCommentPtr = $phpcsFile->findNext(Tokens::COMMENT_TOKENS, $stackPtr);
		if ($nextCommentPtr) {
			$commentToken = $tokens[$nextCommentPtr];
			if ($currentLine === $commentToken['line']) {
				$error = 'Can\'t have comment on the same line as control statement';
				$fix = $phpcsFile->addError($error, $stackPtr, 'CommentAfterControlStatement');
				if ($fix) {
					$phpcsFile->fixer->addNewlineBefore($nextCommentPtr);
				}
			}
		}
	}
}
