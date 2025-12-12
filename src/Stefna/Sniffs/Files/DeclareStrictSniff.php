<?php declare(strict_types=1);

namespace Stefna\Sniffs\Files;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use Stefna\Utils\TokenCollection;

final class DeclareStrictSniff implements Sniff
{
	/**
	 * @return list<T_*>
	 */
	public function register(): array
	{
		return [T_OPEN_TAG];
	}

	public function process(File $phpcsFile, int $stackPtr): void
	{
		$tokens = new TokenCollection($phpcsFile->getTokens());

		$declarePtr = $phpcsFile->findNext(T_DECLARE, $stackPtr);

		if ($declarePtr === false) {
			$error = 'There\'s no declare(strict_types=1) defined';

			$fix = $phpcsFile->addFixableError($error, $stackPtr, 'MissingDeclareStrictInFile');
			if ($fix) {
				$phpcsFile->fixer->beginChangeset();
				$phpcsFile->fixer->addContent($stackPtr, ' declare(strict_types=1);');
				$phpcsFile->fixer->endChangeset();
			}
		}
		else {
			if ($tokens->line($declarePtr) !== 1) {
				$error = 'Expected opening parenthesis directly after the declare statement';
				$fix = $phpcsFile->addFixableError($error, $declarePtr, 'DeclareStrictWrongLineInFile');
				if ($fix) {
					$phpcsFile->fixer->beginChangeset();
					$phpcsFile->fixer->addContent($stackPtr, ' ');
					for ($fixPtr = $stackPtr + 1; $fixPtr < $declarePtr; $fixPtr++) {
						$phpcsFile->fixer->replaceToken($fixPtr, '');
					}
					$phpcsFile->fixer->endChangeset();
				}
			}

			if ($tokens->code($declarePtr - 1) === T_WHITESPACE && $tokens->content($declarePtr - 1) !== ' ') {
				$error = 'Expected single space after opening tag';
				$fix = $phpcsFile->addFixableError($error, $declarePtr, 'MultipleSpaceAfterOpeningTag');
				if ($fix) {
					$phpcsFile->fixer->beginChangeset();
					$phpcsFile->fixer->replaceToken($declarePtr - 1, ' ');
					$phpcsFile->fixer->endChangeset();
				}
			}

			$this->checkValidDeclare($phpcsFile, $declarePtr, $tokens);
		}
	}

	private function checkValidDeclare(File $phpcsFile, int $stackPtr, TokenCollection $tokens): void
	{
		$openParenthesisPtr = $stackPtr + 1;
		if ($tokens->code($openParenthesisPtr) !== T_OPEN_PARENTHESIS) {
			$openParenthesisPtr = $phpcsFile->findNext(T_OPEN_PARENTHESIS, $openParenthesisPtr);

			$error = 'Expected opening parenthesis directly after the declare statement';
			$fix = $phpcsFile->addFixableError($error, $stackPtr, 'OpenParenthesisNotFoundAfterDeclare');
			if ($fix) {
				for ($fixPtr = $stackPtr + 1; $fixPtr < $openParenthesisPtr; $fixPtr++) {
					$phpcsFile->fixer->replaceToken($fixPtr, '');
				}
			}
		}

		$strictTypePtr = $openParenthesisPtr + 1;
		if ($tokens->code($strictTypePtr) !== T_STRING) {
			$strictTypePtr = $phpcsFile->findNext(T_STRING, $strictTypePtr);

			$error = 'Expected string literal directly after the opening parenthesis';
			$fix = $phpcsFile->addFixableError($error, $stackPtr, 'StringLiteralMissingAfterOpenParenthesis');
			if ($fix) {
				for ($fixPtr = $openParenthesisPtr + 1; $fixPtr < $strictTypePtr; $fixPtr++) {
					$phpcsFile->fixer->replaceToken($fixPtr, '');
				}
			}
		}
		if ($tokens->content($strictTypePtr) !== 'strict_types') {
			$error = 'Expected string literal to be `strict_types`';
			$fix = $phpcsFile->addFixableError($error, $strictTypePtr, 'WrongStringLiteralInDeclare');
			if ($fix) {
					$phpcsFile->fixer->replaceToken($strictTypePtr, 'strict_types');
			}
		}

		$equalPtr = $strictTypePtr + 1;
		if ($tokens->code($equalPtr) !== T_EQUAL) {
			$equalPtr = $phpcsFile->findNext(T_EQUAL, $equalPtr);

			$error = 'Expected equal sign directly after the string literal';
			$fix = $phpcsFile->addFixableError($error, $stackPtr, 'EqualMissingAfterStringLiteral');
			if ($fix) {
				for ($fixPtr = $strictTypePtr + 1; $fixPtr < $equalPtr; $fixPtr++) {
					$phpcsFile->fixer->replaceToken($fixPtr, '');
				}
			}
		}

		$onPtr = $equalPtr + 1;
		if ($tokens->code($onPtr) !== T_LNUMBER) {
			$onPtr = $phpcsFile->findNext(T_LNUMBER, $onPtr);

			$error = 'Expected number literal directly after the equal sign';
			$fix = $phpcsFile->addFixableError($error, $stackPtr, 'NumberLiteralMissingAfterEqual');
			if ($fix) {
				for ($fixPtr = $equalPtr + 1; $fixPtr < $onPtr; $fixPtr++) {
					$phpcsFile->fixer->replaceToken($fixPtr, '');
				}
			}
		}
		if ($tokens->content($onPtr) !== '1') {
			$error = 'Expected number literal directly to be `1`';
			$fix = $phpcsFile->addFixableError($error, $onPtr, 'WrongNumberLiteralInDeclare');
			if ($fix) {
					$phpcsFile->fixer->replaceToken($onPtr, '1');
			}
		}

		$closeParenthesisPtr = $onPtr + 1;
		if ($tokens->code($closeParenthesisPtr) !== T_CLOSE_PARENTHESIS) {
			$closeParenthesisPtr = $phpcsFile->findNext(T_CLOSE_PARENTHESIS, $closeParenthesisPtr);

			$error = 'Expected closeParentes sign directly after the number literal';
			$fix = $phpcsFile->addFixableError($error, $stackPtr, 'CloseParenthesisMissingAfterNumberLiteral');
			if ($fix) {
				for ($fixPtr = $onPtr + 1; $fixPtr < $closeParenthesisPtr; $fixPtr++) {
					$phpcsFile->fixer->replaceToken($fixPtr, '');
				}
			}
		}

		$semiColonPtr = $closeParenthesisPtr + 1;
		if ($tokens->code($semiColonPtr) !== T_SEMICOLON) {
			$semiColonPtr = $phpcsFile->findNext(T_SEMICOLON, $semiColonPtr);

			$error = 'Expected semicolon directly after the closeParentes sign';
			$fix = $phpcsFile->addFixableError($error, $stackPtr, 'SemicolonMissingAfterCloseParenthesis');
			if ($fix) {
				for ($fixPtr = $closeParenthesisPtr + 1; $fixPtr < $semiColonPtr; $fixPtr++) {
					$phpcsFile->fixer->replaceToken($fixPtr, '');
				}
			}
		}
	}
}
