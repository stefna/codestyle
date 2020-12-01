<?php declare(strict_types=1);

namespace Stefna\Sniffs\Naming;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Standards\Generic\Sniffs\NamingConventions\CamelCapsFunctionNameSniff as GenericCamelCapsFunctionNameSniff;
use PHP_CodeSniffer\Util\Common;

final class CamelCapsMethodNameSniff extends GenericCamelCapsFunctionNameSniff
{


	/**
	 * Processes the tokens within the scope.
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being processed.
	 * @param int                         $stackPtr  The position where this token was
	 *                                               found.
	 * @param int                         $currScope The position of the current scope.
	 *
	 * @return void
	 */
	protected function processTokenWithinScope(File $phpcsFile, $stackPtr, $currScope)
	{
		$tokens = $phpcsFile->getTokens();

		// Determine if this is a function which needs to be examined.
		$conditions = $tokens[$stackPtr]['conditions'];
		end($conditions);
		$deepestScope = key($conditions);
		if ($deepestScope !== $currScope) {
			return;
		}

		$methodName = $phpcsFile->getDeclarationName($stackPtr);
		if ($methodName === null) {
			// Ignore closures.
			return;
		}

		// Ignore magic methods.
		if (preg_match('|^__[^_]|', $methodName) !== 0) {
			$magicPart = strtolower(substr($methodName, 2));
			if (isset($this->magicMethods[$magicPart]) === true
				|| isset($this->methodsDoubleUnderscore[$magicPart]) === true
			) {
				return;
			}
		}
		// Ignore methods that are ONLY UPPER CASE.
		if (strtoupper($methodName) === $methodName) {
			return;
		}

		$testName = ltrim($methodName, '_');
		if ($testName !== '' &&  Common::isCamelCaps($testName, false, true, false) === false) {
			$error     = 'Method name "%s" is not in camel caps format';
			$className = $phpcsFile->getDeclarationName($currScope);
			if (isset($className) === false) {
				$className = '[Anonymous Class]';
			}

			$errorData = [$className.'::'.$methodName];
			$phpcsFile->addError($error, $stackPtr, 'NotCamelCaps', $errorData);
			$phpcsFile->recordMetric($stackPtr, 'CamelCase method name', 'no');
		} else {
			$phpcsFile->recordMetric($stackPtr, 'CamelCase method name', 'yes');
		}

	}//end processTokenWithinScope()


	/**
	 * Processes the tokens outside the scope.
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being processed.
	 * @param int                         $stackPtr  The position where this token was
	 *                                               found.
	 *
	 * @return void
	 */
	protected function processTokenOutsideScope(File $phpcsFile, $stackPtr)
	{

	}//end processTokenOutsideScope()


}//end class
