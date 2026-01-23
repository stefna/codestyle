<?php declare(strict_types=1);

namespace Stefna\Sniffs\Functions;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;
use Stefna\Utils\TokenCollection;

final class PropertyPromotionSniff implements Sniff
{
	public function register(): array
	{
		return [
			T_FUNCTION,
		];
	}

	public function process(File $phpcsFile, int $stackPtr): void
	{
		$tokens = new TokenCollection($phpcsFile->getTokens());

		if (!$tokens->hasParenthesisOpener($stackPtr) || !$tokens->hasParenthesisCloser($stackPtr)) {
			return;
		}

		$argumentListStart = $tokens->parenthesisOpener($stackPtr);
		$argumentListEnd = $tokens->parenthesisCloser($stackPtr);

		$propertyPromotion = $phpcsFile->findNext(Tokens::SCOPE_MODIFIERS, $argumentListStart + 1, $argumentListEnd);
		while ($propertyPromotion !== false) {
			$this->singleLinePropertyPromotion($phpcsFile, $propertyPromotion, $argumentListEnd, $tokens);

			$propertyPromotion = $phpcsFile->findNext(Tokens::SCOPE_MODIFIERS, $propertyPromotion + 1, $argumentListEnd);
		}
	}

	private function singleLinePropertyPromotion(File $phpcsFile, int $stackPtr, int $argumentListEnd, TokenCollection $tokens): void
	{
		$previousSeparator = $phpcsFile->findPrevious([T_OPEN_PARENTHESIS, T_COMMA], $stackPtr);

		if ($tokens->sameLine($previousSeparator, $stackPtr)) {
			$error = 'Propmoted property has to be on it\'s own line';
			$fix = $phpcsFile->addFixableError($error, $stackPtr, 'PropertyPromotionNotOwnLine');
			if ($fix) {
				$phpcsFile->fixer->addNewline($previousSeparator);
			}
		}

		if ($tokens->sameLine($argumentListEnd, $stackPtr)) {
			$error = 'Propmoted property has to be on it\'s own line';
			$fix = $phpcsFile->addFixableError($error, $stackPtr, 'PropertyPromotionNotOwnLine');
			if ($fix) {
				$phpcsFile->fixer->addNewlineBefore($argumentListEnd);
			}
		}
	}
}
