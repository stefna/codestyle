<?php declare(strict_types=1);

namespace Stefna\Sniffs\Naming;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Standards\Generic\Sniffs\NamingConventions\CamelCapsFunctionNameSniff as GenericCamelCapsFunctionNameSniff;

final class CamelCapsMethodNameSniff extends GenericCamelCapsFunctionNameSniff
{
	protected function processTokenWithinScope(File $phpcsFile, int $stackPtr, int $currScope): void
	{
		$methodName = $phpcsFile->getDeclarationName($stackPtr);
		if ($methodName === '') {
			// Live coding or parse error. Bow out.
			return;
		}

		// Ignore methods that are ONLY UPPER case .
		if (strtoupper($methodName) === $methodName) {
			return;
		}


		parent::processTokenWithinScope($phpcsFile, $stackPtr, $currScope);
	}
}
