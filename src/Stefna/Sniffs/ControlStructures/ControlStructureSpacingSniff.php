<?php declare(strict_types=1);

namespace Stefna\Sniffs\ControlStructures;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Standards\PSR12\Sniffs\ControlStructures\ControlStructureSpacingSniff as Psr12ControlStructureSpacingSniff;
use PHP_CodeSniffer\Util\Tokens;

final class ControlStructureSpacingSniff implements Sniff
{
	public int $indent = 4;
	private Psr12ControlStructureSpacingSniff $psr12ControlStructureSpacing;

	public function __construct()
	{
		$this->psr12ControlStructureSpacing = new Psr12ControlStructureSpacingSniff();
	}

	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array<int|string>
	 */
	public function register()
	{
		return [
			T_IF,
			T_WHILE,
			T_FOREACH,
			T_FOR,
			T_SWITCH,
			T_ELSEIF,
			T_CATCH,
			T_MATCH,
		];

	}//end register()


	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
	 * @param int                         $stackPtr  The position of the current token
	 *                                               in the stack passed in $tokens.
	 *
	 * @return void
	 */
	public function process(File $phpcsFile, $stackPtr)
	{
		$tokens = $phpcsFile->getTokens();

		if (isset($tokens[$stackPtr]['parenthesis_opener']) === false
			|| isset($tokens[$stackPtr]['parenthesis_closer']) === false
		) {
			return;
		}

		if ($tokens[$stackPtr]['type'] !== 'T_IF') {
			$this->psr12ControlStructureSpacing->process($phpcsFile, $stackPtr);
			return;
		}

		$parenOpener = $tokens[$stackPtr]['parenthesis_opener'];
		$parenCloser = $tokens[$stackPtr]['parenthesis_closer'];

		if ($tokens[$parenOpener]['line'] === $tokens[$parenCloser]['line']) {
			$this->psr12ControlStructureSpacing->process($phpcsFile, $stackPtr);
			return;
		}

		$next = $phpcsFile->findNext(T_WHITESPACE, ($parenOpener + 1), $parenCloser, true);
		if ($next === false) {
			// No conditions; parse error.
			return;
		}

		// Check the first expression.
		if ($tokens[$next]['line'] !== ($tokens[$parenOpener]['line'] + 1)) {
			$hasMultipleOperators = $phpcsFile->findNext(Tokens::$booleanOperators, $parenOpener, $parenCloser);
			if ($hasMultipleOperators) {
				$this->psr12ControlStructureSpacing->process($phpcsFile, $stackPtr);
				return;
			}
		}
		else {
			$this->psr12ControlStructureSpacing->process($phpcsFile, $stackPtr);
			return;
		}

		// Special check for if statements with 1 operation

		// Check the indent of each line.
		$first          = $phpcsFile->findFirstOnLine(T_WHITESPACE, $stackPtr, true);
		// Reduce required ident level by 1 for this special case
		$requiredIndent = ($tokens[$first]['column'] + $this->indent - 1) - $this->indent;
		for ($i = $parenOpener; $i < $parenCloser; $i++) {
			if ($tokens[$i]['column'] !== 1
				|| $tokens[($i + 1)]['line'] > $tokens[$i]['line']
				|| isset(Tokens::$commentTokens[$tokens[$i]['code']]) === true
			) {
				continue;
			}

			if (($i + 1) === $parenCloser) {
				break;
			}

			// Leave indentation inside multi-line strings.
			if (isset(Tokens::$textStringTokens[$tokens[$i]['code']]) === true
				|| isset(Tokens::$heredocTokens[$tokens[$i]['code']]) === true
			) {
				continue;
			}

			if ($tokens[$i]['code'] !== T_WHITESPACE) {
				$foundIndent = 0;
			} else {
				$foundIndent = $tokens[$i]['length'];
			}

			if ($foundIndent < $requiredIndent) {
				$error = 'Each line in a multi-line control structure must be indented at least once; expected at least %s spaces, but found %s';
				$data  = [
					$requiredIndent,
					$foundIndent,
				];
				$fix   = $phpcsFile->addFixableError($error, $i, 'LineIndent', $data);
				if ($fix === true) {
					$padding = str_repeat(' ', $requiredIndent);
					if ($foundIndent === 0) {
						$phpcsFile->fixer->addContentBefore($i, $padding);
					} else {
						$phpcsFile->fixer->replaceToken($i, $padding);
					}
				}
			}
		}//end for

		// Check the closing parenthesis.
		$prev = $phpcsFile->findPrevious(T_WHITESPACE, ($parenCloser - 1), $parenOpener, true);

		if ($tokens[$parenCloser]['line'] === ($tokens[$prev]['line'] + 1)) {
			$error = 'The closing parenthesis of a multi-line control structure must be on the same line as the last expression';
			$fix   = $phpcsFile->addFixableError($error, $parenCloser, 'CloseParenthesisLine');
			if ($fix === true) {
				$prevWhitespace = $phpcsFile->findPrevious(
					[T_WHITESPACE],
					$parenCloser,
					$parenCloser - 2,
				);
				if ($prevWhitespace) {
					$phpcsFile->fixer->replaceToken($prevWhitespace, '');
				}
			}//end if
		}//end if

		if ($tokens[$parenCloser]['line'] !== $tokens[$prev]['line']) {
			$requiredIndent = ($tokens[$first]['column'] - 1);
			$foundIndent    = ($tokens[$parenCloser]['column'] - 1);
			if ($foundIndent !== $requiredIndent) {
				$error = 'The closing parenthesis of a multi-line control structure must be indented to the same level as start of the control structure; expected %s spaces but found %s';
				$data  = [
					$requiredIndent,
					$foundIndent,
				];
				$fix   = $phpcsFile->addFixableError($error, $parenCloser, 'CloseParenthesisIndent', $data);
				if ($fix === true) {
					$padding = str_repeat(' ', $requiredIndent);
					if ($foundIndent === 0) {
						$phpcsFile->fixer->addContentBefore($parenCloser, $padding);
					} else {
						$phpcsFile->fixer->replaceToken(($parenCloser - 1), $padding);
					}
				}
			}
		}

	}//end process()
}
