<?php declare(strict_types=1);

namespace Stefna\Sniffs\ControlStructures;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;
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

		$previousCloseBracketPtr = $phpcsFile->findPrevious(T_CLOSE_CURLY_BRACKET, $stackPtr - 1);
		if ($previousCloseBracketPtr) {
			if ($tokens->sameLine($stackPtr, $previousCloseBracketPtr)) {
				$error = 'Closing brace must be on a line by itself';
				$fix = $phpcsFile->addFixableError($error, $previousCloseBracketPtr, 'BracketBeforeControlStatement');
				if ($fix) {
					$phpcsFile->fixer->addNewline($previousCloseBracketPtr);
				}
			}
		}

		$nextCommentPtr = $phpcsFile->findNext(Tokens::COMMENT_TOKENS, $stackPtr);
		if ($nextCommentPtr) {
			if ($tokens->sameLine($stackPtr, $nextCommentPtr)) {
				$error = 'Can\'t have comment on the same line as control statement';
				$fix = $phpcsFile->addError($error, $stackPtr, 'CommentAfterControlStatement');
				if ($fix) {
					$phpcsFile->fixer->addNewlineBefore($nextCommentPtr);
				}
			}
		}
	}
}
