<?php declare(strict_types=1);

namespace Stefna\Sniffs\Classes;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Standards\PSR2\Sniffs\Classes\PropertyDeclarationSniff as PropertyDeclarationSniffBase;
use Stefna\Utils\TokenCollection;

final class PropertyDeclarationSniff extends PropertyDeclarationSniffBase
{
	protected function processMemberVar(File $phpcsFile, int $stackPtr): int|null
	{
		parent::processMemberVar($phpcsFile, $stackPtr);

		$tokens = new TokenCollection($phpcsFile->getTokens());

		$nextToken = $phpcsFile->findNext(T_WHITESPACE, $stackPtr + 1, exclude: true);

		if ($tokens->content($nextToken) === '{') {
			$this->removeInvalidErrors($phpcsFile, $nextToken, $tokens);
			return $this->processHookVariable($phpcsFile, $stackPtr, $tokens);
		}

		return null;
	}

	private function removeInvalidErrors(File $phpcsFile, int $stackPtr, TokenCollection $tokens): void
	{
		$removeError = function (int $line, int $column): void {
			$toRemove = [];

			foreach ($this->errors as $line => $columns) {
				foreach ($columns as $column => $errors) {
					if ($errors[0]['source'] === 'Stefna.Classes.PropertyDeclaration.Multiple') {
						$toRemove[$line] = $column;
					}
				}
			}

			foreach ($toRemove as $line => $column) {
				unset($this->errors[$line][$column]);
				if (empty($this->errors[$line])) {
					unset($this->errors[$line]);
				}

				$this->errorCount -= 1;
			}
		};

		$boundClosure = \Closure::bind($removeError, $phpcsFile, File::class);
		$boundClosure($tokens->line($stackPtr), $tokens->column($stackPtr));
	}

	private function processHookVariable(File $phpcsFile, int $stackPtr, TokenCollection $tokens): int
	{
		$stackPtr = $phpcsFile->findNext(T_WHITESPACE, $stackPtr + 1, exclude: true);
		$hookScopeEnd = $tokens->bracketCloser($stackPtr);

		while ($stackPtr < $hookScopeEnd) {
			$stackPtr = $phpcsFile->findNext(T_WHITESPACE, $stackPtr + 1, exclude: true);
			if ($tokens->code($stackPtr) !== T_STRING) {
				$error = 'Expected hook get/set; found %s';
				$data = [
					$tokens->content($stackPtr),
				];
				$phpcsFile->addError($error, $stackPtr, 'InvalidHook', $data);
			}

			$nextToken = $phpcsFile->findNext(T_WHITESPACE, $stackPtr + 1, exclude: true);
			if ($tokens->content($nextToken) === '{') {
				if ($stackPtr === $nextToken - 1) {
					$error = 'Expected 1 space between hook and "{"; found 0';
					$fix = $phpcsFile->addFixableError($error, $stackPtr, 'SpacingAfterHook');
					if ($fix) {
						$phpcsFile->fixer->addContent($stackPtr, ' ');
					}
				}
				elseif ($stackPtr === $nextToken - 2 && $tokens->code($stackPtr + 1) === T_WHITESPACE) {
					$contentLength = strlen($tokens->content($stackPtr + 1));

					if ($contentLength !== 1) {
						$error = 'Expected 1 space between hook and "{"; found %d';
						$data = [
							$contentLength,
						];
						$fix = $phpcsFile->addFixableError($error, $stackPtr, 'SpacingAfterHook', $data);
						if ($fix) {
							$phpcsFile->fixer->replaceToken($stackPtr + 1, ' ');
						}
					}
				}

				$stackPtr = $phpcsFile->findNext(T_WHITESPACE, $tokens->bracketCloser($nextToken) + 1, exclude: true);
			}
		}

		return $hookScopeEnd;
	}
}
