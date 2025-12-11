<?php declare(strict_types=1);

namespace Stefna\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Standards\Generic\Sniffs\Commenting\DocCommentSniff as DocCommentSniffBase;

final class DocCommentSniff extends DocCommentSniffBase
{
	private const ALLOWED_ONE_LINE_COMMENTS = [
		'@var',
		'@phpstan-var',
		'@type',
		'@lang',
		'@noinspection',
		'@use',
		'@deprecated',
		'@phpstan-ignore-next-line',
	];

	public function process(File $phpcsFile, int $stackPtr): void {
		$tokens = $phpcsFile->getTokens();
		$commentEnd = $phpcsFile->findNext(T_DOC_COMMENT_CLOSE_TAG, $stackPtr + 1);
		$commentStart = $tokens[$commentEnd]['comment_opener'];

		if ($tokens[$commentStart]['line'] === $tokens[$commentEnd]['line']) {
			$commentText = $phpcsFile->getTokensAsString($commentStart, $commentEnd - $commentStart + 1);
			[, $commentType] = explode(' ', $commentText);
			if (in_array($commentType, self::ALLOWED_ONE_LINE_COMMENTS, true)) {
				// Skip inline block comments with variable type definition

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
		for ($ptr = $stackPtr; $ptr < $commentEnd; $ptr++) {
			if ($tokens[$ptr]['code'] === T_DOC_COMMENT_STRING && $currentTagLine !== $tokens[$ptr]['line']) {
				$hasContent = true;
			}
			elseif ($tokens[$ptr]['code'] === T_DOC_COMMENT_TAG) {
				$currentTagLine = $tokens[$ptr]['line'];
				$tagCount++;
				$ignore = in_array($tokens[$ptr]['content'], ['@inheritdoc', '@noinspection', '@dataProvider'], true);
				$ignoreIfContent = in_array($tokens[$ptr]['content'], ['@return'], true);
				$fixable = in_array($tokens[$ptr]['content'], ['@var', '@type'], true);

				if ($onlyClassTags !== false) {
					$onlyClassTags = in_array(
						$tokens[$ptr]['content'],
						['@property', '@property-read', '@property-write', '@method'],
						true
					);
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
			if ($fix) {
				$phpcsFile->fixer->beginChangeset();
				// Add space around declaration
				$phpcsFile->fixer->addContent($stackPtr, ' ');
				for ($fixPtr = $stackPtr + 1; $fixPtr < $commentEnd; $fixPtr++) {
					if (in_array($tokens[$fixPtr]['code'], [T_DOC_COMMENT_TAG, T_DOC_COMMENT_STRING], true)) {
						$phpcsFile->fixer->addContent($fixPtr, ' ');
					}
					else {
						$phpcsFile->fixer->replaceToken($fixPtr, '');
					}
				}
				$phpcsFile->fixer->endChangeset();
			}
		}

		parent::process($phpcsFile, $stackPtr);

		$this->alignBlock($phpcsFile, $stackPtr, $tokens);
	}
	/**
	 * @param array<int,mixed> $tokens
	 */
	private function alignBlock(File $phpcsFile, int $stackPtr, array $tokens): void
	{
		$expectedColumn = $tokens[$stackPtr]['column'] + 1;
		$endTagPtr = $tokens[$stackPtr]['comment_closer'];

		$starPtr = $stackPtr;
		do {
			$starPtr = $phpcsFile->findNext([T_DOC_COMMENT_STAR, T_DOC_COMMENT_CLOSE_TAG], $starPtr + 1);
			if ($tokens[$starPtr]['column'] !== $expectedColumn) {
				$error = 'Comment stars should align indented with a single space';
				$fix = $phpcsFile->addFixableError($error, $starPtr, 'MissAlignedBlock');
				if ($fix) {
					$phpcsFile->fixer->beginChangeset();
					if ($tokens[$starPtr]['column'] < $expectedColumn) {
						$phpcsFile->fixer->addContentBefore($starPtr, ' ');
					}
					else {
						if ($tokens[$starPtr - 1]['line'] === $tokens[$starPtr]['line'] && $tokens[$starPtr - 1]['code'] === T_WHITESPACE) {
							$phpcsFile->fixer->replaceToken($starPtr - 1, ' ');
						} else {
							$phpcsFile->fixer->addContentBefore($starPtr, ' ');
						}
					}
					$phpcsFile->fixer->endChangeset();
				}
			}
		} while ($starPtr !== false && $starPtr < $endTagPtr);
	}
}
