<?php declare(strict_types=1);

namespace Stefna\Sniffs\Functions;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use Stefna\Utils\TokenCollection;

final class ArgumentTrailingCommaSniff implements Sniff
{
	public function register(): array
	{
		return [
			T_FUNCTION,
			T_CLOSURE,
			T_FN,
		];
	}

	public function process(File $phpcsFile, int $stackPtr): void
	{
		$tokens = new TokenCollection($phpcsFile->getTokens());

		$argumentStartPtr = $tokens->parenthesisOpener($stackPtr);
		$argumentEndPtr = $tokens->parenthesisCloser($stackPtr);

		if ($tokens->sameLine($argumentStartPtr, $argumentEndPtr)) {
			return;
		}

		$lastArgumentPtr = $phpcsFile->findPrevious(
			[T_WHITESPACE, T_COMMENT],
			$argumentEndPtr - 1,
			$argumentStartPtr,
			exclude: true,
		);

		if (!$lastArgumentPtr) {
			return;
		}

		if ($tokens->code($lastArgumentPtr) !== T_COMMA) {
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
}
