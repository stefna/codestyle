<?php declare(strict_types=1);

namespace Stefna\Sniffs\ControlStructures;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;
use Stefna\Utils\TokenCollection;
use PHP_CodeSniffer\Standards\PSR12\Sniffs\ControlStructures\ControlStructureSpacingSniff as Psr12ControlStructureSpacingSniff;

final class ControlStructureSpacingSniff implements Sniff
{
	public int $indent = 4;

	private Psr12ControlStructureSpacingSniff $psr12ControlStructureSpacing;

	public function __construct()
	{
		$this->psr12ControlStructureSpacing = new Psr12ControlStructureSpacingSniff();
	}

	public function register(): array
	{
		return $this->psr12ControlStructureSpacing->register();
	}

	public function process(File $phpcsFile, int $stackPtr): void
	{
		$tokens = new TokenCollection($phpcsFile->getTokens());

		if ($tokens->code($stackPtr) !== T_IF) {
			$this->psr12ControlStructureSpacing->process($phpcsFile, $stackPtr);
			return;
		}

		if (!$tokens->hasParenthesisOpener($stackPtr) || !$tokens->hasParenthesisCloser($stackPtr)) {
			return;
		}

		$parenOpener = $tokens->parenthesisOpener($stackPtr);
		$parenCloser = $tokens->parenthesisCloser($stackPtr);

		if ($tokens->sameLine($parenOpener, $parenCloser)) {
			$this->psr12ControlStructureSpacing->process($phpcsFile, $stackPtr);
			return;
		}

		$next = $phpcsFile->findNext(T_WHITESPACE, $parenOpener + 1, $parenCloser, exclude: true);
		if (!$next) {
			// No conditions; parse error.
			return;
		}

		// Check the first expression.
		if ($tokens->line($next) !== ($tokens->line($parenOpener) + 1)) {
			$hasMultipleOperators = $phpcsFile->findNext(Tokens::BOOLEAN_OPERATORS, $parenOpener, $parenCloser);
			if ($hasMultipleOperators) {
				$this->psr12ControlStructureSpacing->process($phpcsFile, $stackPtr);
				return;
			}
		}
		else {
			$this->psr12ControlStructureSpacing->process($phpcsFile, $stackPtr);
			return;
		}

		// Special check for if statements wtih 1 operator

		// Check the indent of each line
		$first = $phpcsFile->findFirstOnLine(T_WHITESPACE, $stackPtr, true);
		// Reduce required indent level by 1 for this special case
		$requiredIndent = ($tokens->column($first) + $this->indent - 1) - $this->indent;
		for ($i = $parenOpener; $i < $parenCloser; $i++) {
			if (
				$tokens->column($i) !== 1
				|| $tokens->line($i + 1) > $tokens->line($i)
				|| isset(Tokens::COMMENT_TOKENS[$tokens->code($i)])
			) {
				continue;
			}

			if (($i + 1) === $parenCloser) {
				break;
			}

			// Leave indentation inside multi-line strings.
			if (
				isset(Tokens::TEXT_STRING_TOKENS[$tokens->code($i)])
				|| isset(Tokens::COMMENT_TOKENS[$tokens->code($i)])
			) {
				continue;
			}

			if ($tokens->code($i) !== T_WHITESPACE) {
				$foundIndent = 0;
			}
			else {
				$foundIndent = $tokens->length($i);
			}

			if ($foundIndent < $requiredIndent) {
				$error = 'Each line in a multi-line control structure must be indented at least once; expected at least %s spaces, but found %s';
				$data = [
					$requiredIndent,
					$foundIndent,
				];

				$fix = $phpcsFile->addFixableError($error, $i, 'LineIndent', $data);
				if ($fix) {
					$padding = str_repeat(' ', $requiredIndent);
					if ($foundIndent === 0) {
						$phpcsFile->fixer->addContentBefore($i, $padding);
					}
					else {
						$phpcsFile->fixer->replaceToken($i, $padding);
					}
				}
			}
		}

		// Check the closing parenthesis
		$prev = $phpcsFile->findPrevious(T_WHITESPACE, $parenCloser - 1, $parenOpener, true);

		if ($tokens->line($parenCloser) === ($tokens->length($prev) + 1)) {
			$error = 'The closing parenthesis of a multi-line control structure must be on the same line as the last expression';
			$fix = $phpcsFile->addFixableError($error, $parenCloser, 'CloseParenthesisLine');
			if ($fix) {
				$prevWhiteSpace = $phpcsFile->findPrevious(T_WHITESPACE, $parenCloser, $parenCloser - 2);
				if ($prevWhiteSpace) {
					$phpcsFile->fixer->replaceToken($prevWhiteSpace, '');
				}
			}
		}

		if (!$tokens->sameLine($parenCloser, $prev)) {
			$requiredIndent = $tokens->column($first) - 1;
			$foundIndent = $tokens->column($parenCloser) - 1;
			if ($foundIndent !== $requiredIndent) {
				$error = 'The closing parenthesis of a multi-line control structure must be indented to the same level as start of the control structure; expected %s spaces but found %s';
				$data = [
					$requiredIndent,
					$foundIndent,
				];
				$fix = $phpcsFile->addFixableError($error, $parenCloser, 'CloseParenthesisIndent', $data);
				if ($fix) {
					$padding = str_repeat(' ', $requiredIndent);
					if ($foundIndent === 0) {
						$phpcsFile->fixer->addContentBefore($parenCloser, $padding);
					}
					else {
						$phpcsFile->fixer->replaceToken($parenCloser - 1, $padding);
					}
				}
			}
		}
	}
}
