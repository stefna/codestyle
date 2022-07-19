<?php declare(strict_types=1);

namespace Stefna\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;

class DocCommentSniff extends \PHP_CodeSniffer\Standards\Generic\Sniffs\Commenting\DocCommentSniff
{
	private const ALLOWED_ONE_LINE_COMMENTS = [
		'@var',
		'@phpstan-var',
		'@type',
		'@lang',
		'@noinspection',
		'@use',
		'@phpstan-ignore-next-line',
	];

	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @param File $phpcsFile The file being scanned.
	 * @param int $stackPtr The position of the current token in the stack passed in $tokens.
	 *
	 * @return void
	 */
	public function process(File $phpcsFile, $stackPtr)
	{

		$tokens = $phpcsFile->getTokens();
		$commentEnd = $phpcsFile->findNext(T_DOC_COMMENT_CLOSE_TAG, ($stackPtr + 1));
		$commentStart = $tokens[$commentEnd]['comment_opener'];
		if ($tokens[$commentStart]['line'] === $tokens[$commentEnd]['line']) {
			$commentText = $phpcsFile->getTokensAsString($commentStart, $commentEnd - $commentStart + 1);
			[, $commentType] = explode(' ', $commentText);
			if (in_array($commentType, self::ALLOWED_ONE_LINE_COMMENTS, true)) {
				// Skip inline block comments with variable type definition.
				return;
			}
		}

		$tagCount = 0;
		$onlyClassTags = null;
		$fixable = false;
		$ignore = false;
		$ignoreIfContent = false;
		$hasContent = false;
		$currentTagLine = -1;
		for ($i = $stackPtr; $i < $commentEnd; $i++) {
			if ($tokens[$i]['code'] === T_DOC_COMMENT_STRING && $currentTagLine !== $tokens[$i]['line']) {
				$hasContent = true;
			}
			if ($tokens[$i]['code'] === T_DOC_COMMENT_TAG) {
				$currentTagLine = $tokens[$i]['line'];
				$tagCount++;
				$ignore = \in_array($tokens[$i]['content'], ['@inheritdoc', '@noinspection', '@dataProvider'], true);
				$ignoreIfContent = \in_array($tokens[$i]['content'], ['@return'], true);
				$fixable = \in_array($tokens[$i]['content'], ['@var', '@type'], true);

				if ($onlyClassTags !== false) {
					$onlyClassTags = \in_array($tokens[$i]['content'],
						['@property', '@property-read', '@property-write', '@method'], true);
				}
			}
		}

		if ($ignoreIfContent) {
			//ignore @return without description
			if (!$hasContent && $tagCount === 1) {
				return;
			}
		}
		elseif ($onlyClassTags) {
			//ignore rules if there are only @property and @method tags
			if (!$hasContent) {
				return;
			}
		}
		elseif ($ignore && !$hasContent) {
			return;
		}

		if ($tagCount === 1 && $fixable && !$hasContent) {
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
	}
}
