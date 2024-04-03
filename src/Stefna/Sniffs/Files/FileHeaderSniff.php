<?php declare(strict_types=1);

namespace Stefna\Sniffs\Files;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

final class FileHeaderSniff implements Sniff
{
	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array<int|string>
	 */
	public function register()
	{
		return [T_OPEN_TAG];
	}

	/**
	 * Processes this sniff when one of its tokens is encountered.
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
	 * @param int                         $stackPtr  The position of the current
	 *                                               token in the stack.
	 *
	 * @return int|void
	 */
	public function process(File $phpcsFile, $stackPtr)
	{
		if ($stackPtr > 0) {
			// don't process if star tag is not at beginning of file
			return;
		}
		$this->checkDeclareStatement($phpcsFile);

		$this->checkNamespace($phpcsFile);
	}

	private function checkNamespace(File $phpcsFile): void
	{
		$tokens = $phpcsFile->getTokens();
		$namespacePtr = $phpcsFile->findNext(T_NAMESPACE, 0);
		if ($namespacePtr !== false) {
			$emptyLines = [];
			// todo deal with comments
			for ($i = 0; $i < $namespacePtr; $i++) {
				if ($tokens[$i]['type'] == 'T_WHITESPACE' && $tokens[$i]['content'] === $phpcsFile->eolChar) {
					$emptyLines[] = $i;
				}
			}
			if (count($emptyLines) !== 2) {
				$error = sprintf(
					'There should only be one empty line before namespace. Found %d expected 1',
					count($emptyLines) - 1,
				);
				$fix = $phpcsFile->addFixableError($error, $namespacePtr, 'MultipleNewLinesBeforeNamespace');
				if ($fix === true) {
					$needToRemove = count($emptyLines) - 2;
					$phpcsFile->fixer->beginChangeset();
					foreach ($emptyLines as $emptyLinePtr) {
						if (!$needToRemove) {
							break;
						}
						$phpcsFile->fixer->replaceToken($emptyLinePtr, '');
						$needToRemove--;
					}
					$phpcsFile->fixer->endChangeset();
				}
			}

			$endOfNamespace = $phpcsFile->findEndOfStatement($namespacePtr);

			$namespaceParts = [];
			for ($i = $namespacePtr + 2; $i < $endOfNamespace; $i++) {
				if ($tokens[$i]['code'] === T_STRING) {
					$namespaceParts[$i] = $tokens[$i]['content'];
				}
			}
			$expectedParts = [];
			$failedParts = [];
			foreach ($namespaceParts as $index => $part) {
				if ($part[0] === strtolower($part[0])) {
					$failedParts[$index] = ucfirst($part);
					$expectedParts[] = ucfirst($part);
				}
				else {
					$expectedParts[] = $part;
				}
			}

			if ($failedParts) {
				$error = sprintf(
					'All namespace parts need to be PascalCase. Found "%s" expected "%s"',
					implode('\\', $namespaceParts),
					implode('\\', $expectedParts),
				);
				$fix = $phpcsFile->addFixableError($error, $namespacePtr, 'NonePascalCaseNamespace');
				if ($fix === true) {
					$phpcsFile->fixer->beginChangeset();
					foreach ($failedParts as $index => $fixedPart) {
						$phpcsFile->fixer->replaceToken($index, $fixedPart);
					}
					$phpcsFile->fixer->endChangeset();
				}
			}
		}
	}

	private function checkDeclareStatement(File $phpcsFile): void
	{
		$tokens = $phpcsFile->getTokens();
		$declarePtr = $phpcsFile->findNext(T_DECLARE, 0);
		if ($declarePtr !== false) {
			$declareToken = $tokens[$declarePtr];
			if ($tokens[0]['line'] !== $declareToken['line']) {
				$error = 'Declare statement needs to be on the first line';
				$fix = $phpcsFile->addFixableError($error, $declarePtr, 'DeclareNotFirstInFile');
				if ($fix === true) {
					$end = $phpcsFile->findNext(T_SEMICOLON, $declarePtr);
					$phpcsFile->fixer->beginChangeset();
					$phpcsFile->fixer->replaceToken(0, '<?php ');
					for ($i = $declarePtr; $i <= $end; $i++) {
						$phpcsFile->fixer->addContent(0, $tokens[$i]['content']);
						$phpcsFile->fixer->replaceToken($i, '');
					}
					$phpcsFile->fixer->endChangeset();
				}
			}
			elseif ($declarePtr !== 1) {
				$error = 'There should only be 1 space between open tag and declare';
				$fix = $phpcsFile->addFixableError($error, $declarePtr, 'MultipleSpacesBeforeDeclare');
				if ($fix === true) {
					$phpcsFile->fixer->beginChangeset();
					for ($i = 1; $i < $declarePtr; $i++) {
						$phpcsFile->fixer->replaceToken($i, '');
					}
					$phpcsFile->fixer->endChangeset();
				}
			}
		}
	}
}
