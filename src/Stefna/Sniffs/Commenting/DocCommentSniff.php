<?php declare(strict_types=1);

namespace Stefna\Sniffs\Commenting;

use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;

class DocCommentSniff extends \PHP_CodeSniffer\Standards\Generic\Sniffs\Commenting\DocCommentSniff
{
	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @param File $phpcsFile The file being scanned.
	 * @param int  $stackPtr  The position of the current token
	 *                        in the stack passed in $tokens.
	 *
	 * @return void
	 */
	public function process(File $phpcsFile, $stackPtr)
	{
		$tokens       = $phpcsFile->getTokens();
		$commentEnd   = $phpcsFile->findNext(T_DOC_COMMENT_CLOSE_TAG, ($stackPtr + 1));
		$commentStart = $tokens[$commentEnd]['comment_opener'];
		if ($tokens[$commentStart]['line'] === $tokens[$commentEnd]['line']) {
			$commentText = $phpcsFile->getTokensAsString($commentStart, $commentEnd - $commentStart + 1);
			if (strpos($commentText, '@var') !== false || strpos($commentText, '@type') !== false) {
				// Skip inline block comments with variable type definition.
				return;
			}
		}
		$tagCount = 0;
		$fixable = false;
		for ($i = $stackPtr; $i < $commentEnd; $i++) {
			if ($tokens[$i]['code'] === T_DOC_COMMENT_TAG) {
				$tagCount++;
				$fixable = \in_array($tokens[$i]['content'], ['@var', '@type'], true);
			}
		}

		if ($tagCount === 1 && $fixable) {
			$error = 'Comments with only @var should be on one line';
			$fix = $phpcsFile->addFixableError($error, $stackPtr, 'OneLineTypeDeclare');
			if ($fix === true) {
				$phpcsFile->fixer->beginChangeset();
				//add space around declaration
				$phpcsFile->fixer->addContent($stackPtr, ' ');
				for ($i = $stackPtr + 1; $i < $commentEnd; $i++) {
					if (\in_array($tokens[$i]['code'], [T_DOC_COMMENT_TAG, T_DOC_COMMENT_STRING], true)) {
						$phpcsFile->fixer->addContent($i, ' ');
						continue;
					}
					$phpcsFile->fixer->replaceToken($i, '');
				}

				$phpcsFile->fixer->endChangeset();
			}
			return;
		}

		parent::process($phpcsFile, $stackPtr);
	}//end process()
}//end class