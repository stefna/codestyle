<?php declare(strict_types=1);

namespace Stefna\Sniffs\Attributes;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

final class AttributeFormatSniff implements Sniff
{
	public int $maxLineLength = 120;
	public int $multiArgLineLength = 80;
	public int $multiArgCount = 3;

	public function register(): array
	{
		return [
			T_ATTRIBUTE,
		];
	}

	public function process(File $phpcsFile, int $stackPtr): void
	{
		$tokens = $phpcsFile->getTokens();

		if (!isset($tokens[$stackPtr]['attribute_closer'])) {
			return;
		}

		$attributeCloser = $tokens[$stackPtr]['attribute_closer'];

		$openParen = $phpcsFile->findNext(T_OPEN_PARENTHESIS, $stackPtr + 1, $attributeCloser);
		if ($openParen === false) {
			return;
		}

		if (!isset($tokens[$openParen]['parenthesis_closer'])) {
			return;
		}

		$closeParen = $tokens[$openParen]['parenthesis_closer'];
		if ($closeParen > $attributeCloser) {
			return;
		}

		$arguments = $this->parseArguments($phpcsFile, $openParen, $closeParen);
		$argCount = count($arguments);

		if ($argCount === 0) {
			return;
		}

		$isSingleLine = ($tokens[$stackPtr]['line'] === $tokens[$attributeCloser]['line']);

		if ($isSingleLine) {
			$lineLength = $this->calculateLineLength($phpcsFile, $stackPtr);

			if ($lineLength > $this->maxLineLength) {
				$error = 'Attribute declaration exceeds %d characters;'
					. ' must be split into multiline with one argument per line';
				$fix = $phpcsFile->addFixableError($error, $stackPtr, 'AttributeTooLong', [$this->maxLineLength]);
				if ($fix) {
					$this->fixToMultiline(
						$phpcsFile,
						$stackPtr,
						$openParen,
						$closeParen,
						$attributeCloser,
						$arguments,
					);
				}
				return;
			}

			if ($argCount >= $this->multiArgCount) {
				if ($lineLength > $this->multiArgLineLength) {
					$error = 'Attribute with %d or more arguments exceeds %d characters;'
						. ' must be split into multiline with one argument per line';
					$fix = $phpcsFile->addFixableError(
						$error,
						$stackPtr,
						'TooManyArguments',
						[$this->multiArgCount, $this->multiArgLineLength],
					);
					if ($fix) {
						$this->fixToMultiline(
							$phpcsFile,
							$stackPtr,
							$openParen,
							$closeParen,
							$attributeCloser,
							$arguments,
						);
					}
					return;
				}

				$warning = 'Attribute has %d arguments; consider splitting into multiline';
				$phpcsFile->addWarning($warning, $stackPtr, 'TooManyArgumentsWarning', [$argCount]);
				return;
			}
		}
		else {
			$hasError = false;

			if ($tokens[$arguments[0]['start']]['line'] === $tokens[$openParen]['line']) {
				$error = 'Each attribute argument must be on its own line in a multiline attribute';
				$fix = $phpcsFile->addFixableError($error, $arguments[0]['start'], 'OneArgumentPerLine');
				if ($fix) {
					$hasError = true;
				}
			}

			for ($i = 1; $i < $argCount; $i++) {
				if ($tokens[$arguments[$i]['start']]['line'] <= $tokens[$arguments[$i - 1]['end']]['line']) {
					$error = 'Each attribute argument must be on its own line in a multiline attribute';
					$fix = $phpcsFile->addFixableError($error, $arguments[$i]['start'], 'OneArgumentPerLine');
					if ($fix) {
						$hasError = true;
					}
				}
			}

			if ($tokens[$closeParen]['line'] === $tokens[$arguments[$argCount - 1]['end']]['line']) {
				$error = 'Attribute closing parenthesis and bracket must be on their own line';
				$fix = $phpcsFile->addFixableError($error, $closeParen, 'CloserNotOwnLine');
				if ($fix) {
					$hasError = true;
				}
			}

			if ($hasError) {
				$this->fixToMultiline($phpcsFile, $stackPtr, $openParen, $closeParen, $attributeCloser, $arguments);
			}
		}
	}

	/**
	 * @return list<array{start: int, end: int, line: int}>
	 */
	private function parseArguments(File $phpcsFile, int $openParen, int $closeParen): array
	{
		$tokens = $phpcsFile->getTokens();
		$arguments = [];
		$currentArgStart = null;
		$currentArgEnd = null;

		$i = $openParen + 1;
		while ($i < $closeParen) {
			$token = $tokens[$i];
			$code = $token['code'];

			if ($currentArgStart === null) {
				if (in_array($code, Tokens::$emptyTokens, true)) {
					$i++;
					continue;
				}
				if ($code === T_COMMA) {
					$i++;
					continue;
				}
				$currentArgStart = $i;
			}

			if (isset($token['parenthesis_closer']) && $token['parenthesis_opener'] === $i) {
				$i = $token['parenthesis_closer'];
				$currentArgEnd = $i;
				$i++;
				continue;
			}
			if (isset($token['bracket_closer']) && $token['bracket_opener'] === $i) {
				$i = $token['bracket_closer'];
				$currentArgEnd = $i;
				$i++;
				continue;
			}
			if (isset($token['scope_closer']) && $token['scope_opener'] === $i) {
				$i = $token['scope_closer'];
				$currentArgEnd = $i;
				$i++;
				continue;
			}

			if ($code === T_COMMA) {
				if ($currentArgStart !== null) {
					$end = $phpcsFile->findPrevious(Tokens::$emptyTokens, $i - 1, $currentArgStart, true);
					$arguments[] = [
						'start' => $currentArgStart,
						'end' => $end !== false ? $end : ($currentArgEnd ?? $currentArgStart),
						'line' => $tokens[$currentArgStart]['line'],
					];
					$currentArgStart = null;
					$currentArgEnd = null;
				}
				$i++;
				continue;
			}

			if (!in_array($code, Tokens::$emptyTokens, true)) {
				$currentArgEnd = $i;
			}
			$i++;
		}

		if ($currentArgStart !== null) {
			$end = $phpcsFile->findPrevious(Tokens::$emptyTokens, $closeParen - 1, $currentArgStart, true);
			$arguments[] = [
				'start' => $currentArgStart,
				'end' => $end !== false ? $end : ($currentArgEnd ?? $currentArgStart),
				'line' => $tokens[$currentArgStart]['line'],
			];
		}

		return $arguments;
	}

	private function calculateLineLength(File $phpcsFile, int $stackPtr): int
	{
		$tokens = $phpcsFile->getTokens();
		$targetLine = $tokens[$stackPtr]['line'];

		$lastOnLine = $stackPtr;
		while (isset($tokens[$lastOnLine + 1]) && $tokens[$lastOnLine + 1]['line'] === $targetLine) {
			$lastOnLine++;
		}

		if ($tokens[$lastOnLine]['column'] !== 1 && $tokens[$lastOnLine]['content'] === $phpcsFile->eolChar) {
			$lastOnLine--;
		}

		return $tokens[$lastOnLine]['column'] + $tokens[$lastOnLine]['length'] - 1;
	}

	/**
	 * @param list<array{start: int, end: int, line: int}> $arguments
	 */
	private function fixToMultiline(
		File $phpcsFile,
		int $attributeOpener,
		int $openParen,
		int $closeParen,
		int $attributeCloser,
		array $arguments,
	): void {
		$tokens = $phpcsFile->getTokens();

		$firstOnLine = $phpcsFile->findFirstOnLine([], $attributeOpener);
		$baseIndent = '';
		if ($tokens[$firstOnLine]['code'] === T_WHITESPACE) {
			$baseIndent = $tokens[$firstOnLine]['content'];
		}

		$indentUnit = "\t";
		if (str_contains($baseIndent, '    ')) {
			$indentUnit = '    ';
		}
		$argIndent = $baseIndent . $indentUnit;

		$eol = $phpcsFile->eolChar;

		$phpcsFile->fixer->beginChangeset();

		$content = $eol;
		foreach ($arguments as $arg) {
			$argString = trim($phpcsFile->getTokensAsString($arg['start'], $arg['end'] - $arg['start'] + 1));
			$argString = rtrim($argString, ',');
			$content .= $argIndent . $argString . ',' . $eol;
		}
		$content .= $baseIndent . ')';

		for ($i = $openParen + 1; $i <= $closeParen; $i++) {
			$phpcsFile->fixer->replaceToken($i, '');
		}

		$phpcsFile->fixer->addContent($openParen, $content);

		for ($i = $closeParen + 1; $i < $attributeCloser; $i++) {
			$phpcsFile->fixer->replaceToken($i, '');
		}

		$phpcsFile->fixer->endChangeset();
	}
}
