<?php declare(strict_types=1);

namespace Stefna\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Standards\Generic\Sniffs\Commenting\DocCommentSniff as DocCommentSniffBase;
use Stefna\Utils\TokenCollection;

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

	public function process(File $phpcsFile, int $stackPtr): void
	{
		$tokens = new TokenCollection($phpcsFile->getTokens());
		$commentEnd = $phpcsFile->findNext(T_DOC_COMMENT_CLOSE_TAG, $stackPtr + 1);
		$commentStart = $tokens->commentOpener($commentEnd);

		if ($tokens->sameLine($commentStart, $commentEnd)) {
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
			if ($tokens->code($ptr) === T_DOC_COMMENT_STRING && $currentTagLine !== $tokens->line($ptr)) {
				$hasContent = true;
			}
			elseif ($tokens->code($ptr) === T_DOC_COMMENT_TAG) {
				$currentTagLine = $tokens->line($ptr);
				$tagCount++;
				$ignore = in_array($tokens->content($ptr), ['@inheritdoc', '@noinspection', '@dataProvider'], true);
				$ignoreIfContent = in_array($tokens->content($ptr), ['@return'], true);
				$fixable = in_array($tokens->content($ptr), ['@var', '@type'], true);

				if ($onlyClassTags !== false) {
					$onlyClassTags = in_array(
						$tokens->content($ptr),
						['@property', '@property-read', '@property-write', '@method'],
						true,
					);
				}
			}
		}

		if ($ignoreIfContent) {
			//ignore @return without description
			if (!$hasContent && $tagCount === 1) {
				$this->alignBlock($phpcsFile, $stackPtr, $tokens);
				return;
			}
		}
		elseif ($onlyClassTags) {
			//ignore rules if there are only @property and @method tags
			if (!$hasContent) {
				$this->alignBlock($phpcsFile, $stackPtr, $tokens);
				return;
			}
		}
		elseif ($ignore && !$hasContent) {
			$this->alignBlock($phpcsFile, $stackPtr, $tokens);
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
					if (in_array($tokens->code($fixPtr), [T_DOC_COMMENT_TAG, T_DOC_COMMENT_STRING], true)) {
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

	private function alignBlock(File $phpcsFile, int $stackPtr, TokenCollection $tokens): void
	{
		$expectedColumn = $tokens->column($stackPtr) + 1;
		$endTagPtr = $tokens->commentCloser($stackPtr);

		$starPtr = $stackPtr;
		do {
			$starPtr = $phpcsFile->findNext([T_DOC_COMMENT_STAR, T_DOC_COMMENT_CLOSE_TAG], $starPtr + 1);
			if (!$tokens->sameLine($stackPtr, $starPtr) && $tokens->column($starPtr) !== $expectedColumn) {
				$error = 'Comment stars should align indented with a single space';
				$fix = $phpcsFile->addFixableError($error, $starPtr, 'MissAlignedBlock');
				if ($fix) {
					$phpcsFile->fixer->beginChangeset();
					if ($tokens->column($starPtr) < $expectedColumn) {
						$phpcsFile->fixer->addContentBefore($starPtr, ' ');
					}
					else {
						$padding = str_repeat(' ', $expectedColumn - 1);
						$phpcsFile->fixer->replaceToken($starPtr - 1, $padding);
					}
					$phpcsFile->fixer->endChangeset();
				}
			}
		}
		while ($starPtr !== false && $starPtr < $endTagPtr);
	}
}
