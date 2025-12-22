<?php declare(strict_types=1);

namespace Stefna\Sniffs\Closures;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use Stefna\Utils\TokenCollection;

final class StaticSniff implements Sniff
{
	public function register(): array
	{
		return [
			T_CLOSURE,
			T_FN,
		];
	}

	public function process(File $phpcsFile, int $stackPtr): void
	{
		$tokens = new TokenCollection($phpcsFile->getTokens());

		$bodyStartPtr = $tokens->scopeOpener($stackPtr);
		$bodyEndPtr = $tokens->scopeCloser($stackPtr);

		$staticClosure = true;
		for ($ptr = $bodyStartPtr; $ptr < $bodyEndPtr; $ptr++) {
			if ($tokens->code($ptr) === T_VARIABLE && $tokens->content($ptr) === '$this') {
				$staticClosure = false;
				break;
			}
		}

		if ($staticClosure) {
			$this->staticClosure($phpcsFile, $stackPtr, $tokens);
		}
	}

	private function staticClosure(File $phpcsFile, int $stackPtr, TokenCollection $tokens): void
	{
		$prevToken = $phpcsFile->findPrevious(T_WHITESPACE, $stackPtr - 1, exclude: true);

		if ($prevToken && $tokens->code($prevToken) !== T_STATIC) {
			$error = 'Closures not using `$this` must be static';

			$phpcsFile->addWarningOnLine($error, $tokens->line($stackPtr), 'MissingStaticOnClosure');
		}
	}
}
