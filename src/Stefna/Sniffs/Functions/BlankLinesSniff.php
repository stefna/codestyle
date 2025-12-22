<?php declare(strict_types=1);

namespace Stefna\Sniffs\Functions;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use Stefna\Utils\TokenCollection;

final class BlankLinesSniff implements Sniff
{
	private const MAX_LINES_SPACE = 1;

	public function register(): array
	{
		return [
			T_FUNCTION,
		];
	}

	public function process(File $phpcsFile, int $stackPtr): void
	{
		$tokens = new TokenCollection($phpcsFile->getTokens());

		if (!$tokens->hasScopeOpener($stackPtr) || !$tokens->hasScopeCloser($stackPtr)) {
			// Ignore pure definitions
			return;
		}

		$scopeOpener = $tokens->scopeOpener($stackPtr);
		$scopeCloser = $tokens->scopeCloser($stackPtr);

		if ($tokens->sameLine($scopeOpener, $scopeCloser)) {
			// Ignore single line functions
			return;
		}

		$nextPtr = $this->firstLine($phpcsFile, $scopeOpener, $tokens);

		$prevLine = $tokens->line($nextPtr);
		// Go directly to 2nd line
		$nextPtr = $this->firstOnNextLine($phpcsFile, $nextPtr, $tokens);

		while ($nextPtr < $scopeCloser) {
			$lineDiff = $tokens->line($nextPtr) - $prevLine - 1;

			if ($lineDiff > self::MAX_LINES_SPACE) {
				$error = 'Expected maximum %d blank lines; found %d';
				$data = [
					self::MAX_LINES_SPACE,
					$lineDiff,
				];

				$fix = $phpcsFile->addFixableError($error, $nextPtr, 'TooManyBlankLines', $data);
				if ($fix) {
					$lastTokenPtr = $phpcsFile->findPrevious(T_WHITESPACE, $nextPtr - 1, exclude: true);

					$phpcsFile->fixer->beginChangeset();

					for ($ptr = $lastTokenPtr + 1; $ptr < $nextPtr; $ptr++) {
						$phpcsFile->fixer->replaceToken($ptr, '');
					}

					for ($i = 0; $i <= self::MAX_LINES_SPACE; $i++) {
						$phpcsFile->fixer->addNewline($lastTokenPtr);
					}
					$phpcsFile->fixer->endChangeset();
				}
			}

			$prevLine = $tokens->line($nextPtr);
			$nextPtr = $this->firstOnNextLine($phpcsFile, $nextPtr, $tokens);
		}
	}

	private function firstLine(File $phpcsFile, int $stackPtr, TokenCollection $tokens): int|false
	{
		$nextPtr = $phpcsFile->findNext(T_WHITESPACE, $stackPtr + 1, exclude: true);
		if (!$nextPtr) {
			return false;
		}

		$lineDiff = $tokens->line($nextPtr) - $tokens->line($stackPtr) - 1;

		if ($lineDiff !== 0) {
			$error = 'Scope requires 0 blank lines before content; found %d';
			$data = [
				$lineDiff,
			];
			$fix = $phpcsFile->addFixableError($error, $nextPtr, 'FunctionScopeContentSpacing', $data);
			if ($fix) {
				$phpcsFile->fixer->beginChangeset();

				for ($ptr = $stackPtr + 1; $ptr < $nextPtr; $ptr++) {
					$phpcsFile->fixer->replaceToken($ptr, '');
				}

				$phpcsFile->fixer->addNewline($stackPtr);
				$phpcsFile->fixer->endChangeset();
			}
		}

		return $nextPtr;
	}

	private function firstOnNextLine(File $phpcsFile, int $stackPtr, TokenCollection $tokens): int
	{
		$next = $stackPtr + 1;
		do {
			$next = $phpcsFile->findNext(T_WHITESPACE, $next + 1, exclude: true);
		}
		while ($tokens->sameLine($stackPtr, $next));

		return $next;
	}
}
