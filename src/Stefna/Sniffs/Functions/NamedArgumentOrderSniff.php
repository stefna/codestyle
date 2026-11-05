<?php declare(strict_types=1);

namespace Stefna\Sniffs\Functions;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;
use ReflectionClass;
use ReflectionFunction;
use SlevomatCodingStandard\Helpers\ClassHelper;
use SlevomatCodingStandard\Helpers\FixerHelper;
use SlevomatCodingStandard\Helpers\FunctionHelper;
use SlevomatCodingStandard\Helpers\NamespaceHelper;
use SlevomatCodingStandard\Helpers\TokenHelper;
use SlevomatCodingStandard\Helpers\UseStatementHelper;

final class NamedArgumentOrderSniff implements Sniff
{
	public const CODE_ORDER = 'Order';

	/** @var array<string, array<string, list<string>>> */
	private array $fileClasses = [];

	/** @var array<string, list<string>> */
	private array $fileFunctions = [];

	private ?string $currentFile = null;

	public function register(): array
	{
		return [
			T_OPEN_PARENTHESIS,
		];
	}

	public function process(File $phpcsFile, int $stackPtr): void
	{
		$tokens = $phpcsFile->getTokens();

		if (!isset($tokens[$stackPtr]['parenthesis_closer'])) {
			return;
		}

		$closer = $tokens[$stackPtr]['parenthesis_closer'];

		if (isset($tokens[$stackPtr]['parenthesis_owner'])) {
			$ownerCode = $tokens[$tokens[$stackPtr]['parenthesis_owner']]['code'];
			$ignoredOwners = [
				T_IF,
				T_ELSEIF,
				T_FOR,
				T_FOREACH,
				T_WHILE,
				T_SWITCH,
				T_CATCH,
				T_FUNCTION,
				T_CLOSURE,
				T_FN,
				T_DECLARE,
				T_USE,
				T_MATCH,
			];
			if (in_array($ownerCode, $ignoredOwners, true)) {
				return;
			}
		}

		$prevEffective = TokenHelper::findPreviousEffective($phpcsFile, $stackPtr - 1);
		if ($prevEffective === null) {
			return;
		}

		$arguments = $this->getCallArguments($phpcsFile, $stackPtr, $closer);
		if (empty($arguments)) {
			return;
		}

		$hasNamed = false;
		foreach ($arguments as $arg) {
			if ($arg['param_name'] !== null) {
				$hasNamed = true;
				break;
			}
		}

		if (!$hasNamed) {
			return;
		}

		$this->indexFile($phpcsFile);

		$declaredParams = $this->resolveDeclaredParameters($phpcsFile, $stackPtr, $prevEffective);
		if ($declaredParams === null || empty($declaredParams)) {
			return;
		}

		$paramIndexMap = array_flip($declaredParams);
		$targetIndices = [];

		foreach ($arguments as $i => $arg) {
			if ($arg['param_name'] !== null) {
				if (isset($paramIndexMap[$arg['param_name']])) {
					$targetIndices[$i] = $paramIndexMap[$arg['param_name']];
				}
				else {
					$targetIndices[$i] = count($declaredParams) + $i;
				}
			}
			else {
				$targetIndices[$i] = $i;
			}
		}

		$isSorted = true;
		for ($i = 1; $i < count($targetIndices); $i++) {
			if ($targetIndices[$i] < $targetIndices[$i - 1]) {
				$isSorted = false;
				break;
			}
		}

		if ($isSorted) {
			return;
		}

		$error = sprintf(
			'Named arguments are not in the order defined by the function/method. Expected order: %s',
			implode(', ', $declaredParams),
		);
		$fix = $phpcsFile->addFixableError($error, $arguments[0]['start'], self::CODE_ORDER);

		if ($fix) {
			$sortedArgs = $arguments;
			uksort($sortedArgs, static function (int $a, int $b) use ($targetIndices): int {
				return $targetIndices[$a] <=> $targetIndices[$b];
			});
			$sortedArgs = array_values($sortedArgs);

			$phpcsFile->fixer->beginChangeset();
			foreach ($arguments as $idx => $origArg) {
				FixerHelper::change($phpcsFile, $origArg['start'], $origArg['end'], $sortedArgs[$idx]['content']);
			}
			$phpcsFile->fixer->endChangeset();
		}
	}

	/**
	 * @return list<array{
	 *     start: int,
	 *     end: int,
	 *     comma: ?int,
	 *     param_name: ?string,
	 *     param_name_ptr: ?int,
	 *     content: string,
	 * }>
	 */
	private function getCallArguments(File $phpcsFile, int $openParen, int $closeParen): array
	{
		$tokens = $phpcsFile->getTokens();
		$arguments = [];
		$currentStart = null;
		$paramName = null;
		$paramNamePtr = null;

		$i = $openParen + 1;
		while ($i < $closeParen) {
			$code = $tokens[$i]['code'];

			if ($currentStart === null) {
				if ($code === T_WHITESPACE || in_array($code, Tokens::$commentTokens, true)) {
					$i++;
					continue;
				}
				$currentStart = $i;
				if ($code === T_PARAM_NAME) {
					$paramName = $tokens[$i]['content'];
					$paramNamePtr = $i;
				}
			}

			if (
				isset($tokens[$i]['bracket_closer'])
				&& $tokens[$i]['bracket_closer'] > $i
				&& in_array($tokens[$i]['code'], [T_OPEN_SHORT_ARRAY, T_OPEN_SQUARE_BRACKET], true)
			) {
				$i = $tokens[$i]['bracket_closer'] + 1;
				continue;
			}
			if (
				isset($tokens[$i]['parenthesis_closer'])
				&& $tokens[$i]['parenthesis_closer'] > $i
				&& $tokens[$i]['code'] === T_OPEN_PARENTHESIS
			) {
				$i = $tokens[$i]['parenthesis_closer'] + 1;
				continue;
			}
			if (
				isset($tokens[$i]['scope_closer'])
				&& $tokens[$i]['scope_closer'] > $i
				&& in_array($tokens[$i]['code'], [T_OPEN_CURLY_BRACKET, T_CLOSURE, T_MATCH], true)
			) {
				$i = $tokens[$i]['scope_closer'] + 1;
				continue;
			}

			if ($code === T_COMMA) {
				$lastNonWhitespace = TokenHelper::findPreviousEffective($phpcsFile, $i - 1);
				if ($lastNonWhitespace !== null && $currentStart !== null) {
					$arguments[] = [
						'start' => $currentStart,
						'end' => $lastNonWhitespace,
						'comma' => $i,
						'param_name' => $paramName,
						'param_name_ptr' => $paramNamePtr,
						'content' => TokenHelper::getContent($phpcsFile, $currentStart, $lastNonWhitespace),
					];
				}
				$currentStart = null;
				$paramName = null;
				$paramNamePtr = null;
				$i++;
				continue;
			}

			$i++;
		}

		if ($currentStart !== null) {
			$lastNonWhitespace = TokenHelper::findPreviousEffective($phpcsFile, $closeParen - 1);
			if ($lastNonWhitespace !== null) {
				$arguments[] = [
					'start' => $currentStart,
					'end' => $lastNonWhitespace,
					'comma' => null,
					'param_name' => $paramName,
					'param_name_ptr' => $paramNamePtr,
					'content' => TokenHelper::getContent($phpcsFile, $currentStart, $lastNonWhitespace),
				];
			}
		}

		return $arguments;
	}

	private function indexFile(File $phpcsFile): void
	{
		$filename = $phpcsFile->getFilename();
		if ($this->currentFile === $filename) {
			return;
		}

		$this->currentFile = $filename;
		$this->fileClasses = [];
		$this->fileFunctions = [];

		$tokens = $phpcsFile->getTokens();
		for ($i = 0; $i < count($tokens); $i++) {
			if ($tokens[$i]['code'] !== T_FUNCTION) {
				continue;
			}

			$classPtr = FunctionHelper::findClassPointer($phpcsFile, $i);
			$name = FunctionHelper::getName($phpcsFile, $i);
			$paramNames = array_map(
				static fn (string $param): string => ltrim($param, '$'),
				FunctionHelper::getParametersNames($phpcsFile, $i),
			);

			if ($classPtr === null) {
				$fqName = FunctionHelper::getFullyQualifiedName($phpcsFile, $i);
				$this->fileFunctions[$fqName] = $paramNames;
				$this->fileFunctions[$name] = $paramNames;
			}
			else {
				$className = ClassHelper::getName($phpcsFile, $classPtr);
				$fqClassName = ClassHelper::getFullyQualifiedName($phpcsFile, $classPtr);
				$this->fileClasses[$className][$name] = $paramNames;
				$this->fileClasses[$fqClassName][$name] = $paramNames;
			}
		}
	}

	/**
	 * @return list<string>|null
	 */
	private function resolveDeclaredParameters(File $phpcsFile, int $openParen, int $prevEffective): ?array
	{
		$tokens = $phpcsFile->getTokens();
		$prevCode = $tokens[$prevEffective]['code'];

		// Attribute: #[Attr(a: 1)]
		if ($this->isInsideAttribute($phpcsFile, $openParen)) {
			$attrClass = $tokens[$prevEffective]['content'];
			return $this->resolveMethodParameters($phpcsFile, $attrClass, '__construct', $openParen);
		}

		$beforePrev = TokenHelper::findPreviousEffective($phpcsFile, $prevEffective - 1);
		if ($beforePrev === null) {
			if (in_array($prevCode, [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED, T_NAME_RELATIVE], true)) {
				return $this->resolveFunctionParameters($phpcsFile, $tokens[$prevEffective]['content'], $openParen);
			}
			return null;
		}

		$beforePrevCode = $tokens[$beforePrev]['code'];

		// Constructor: new Class(...)
		if ($beforePrevCode === T_NEW) {
			$className = $tokens[$prevEffective]['content'];
			return $this->resolveMethodParameters($phpcsFile, $className, '__construct', $openParen);
		}

		// Static call: Class::method(...) or self::method(...)
		if ($beforePrevCode === T_DOUBLE_COLON) {
			$methodName = $tokens[$prevEffective]['content'];
			$classTokenPtr = TokenHelper::findPreviousEffective($phpcsFile, $beforePrev - 1);
			if ($classTokenPtr !== null) {
				$className = $tokens[$classTokenPtr]['content'];
				if (in_array($className, ['self', 'static'], true)) {
					$classPtr = ClassHelper::getClassPointer($phpcsFile, $openParen);
					if ($classPtr !== null) {
						$className = ClassHelper::getName($phpcsFile, $classPtr);
					}
				}
				elseif ($className === 'parent') {
					$classPtr = ClassHelper::getClassPointer($phpcsFile, $openParen);
					if ($classPtr !== null) {
						$extendsPtr = TokenHelper::findNext($phpcsFile, T_EXTENDS, $classPtr);
						if ($extendsPtr !== null) {
							$parentNamePtr = TokenHelper::findNextEffective($phpcsFile, $extendsPtr + 1);
							if ($parentNamePtr !== null) {
								$className = $tokens[$parentNamePtr]['content'];
							}
						}
					}
				}
				return $this->resolveMethodParameters($phpcsFile, $className, $methodName, $openParen);
			}
		}

		// Method call: $var->method(...) or $var?->method(...)
		if (in_array($beforePrevCode, [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR], true)) {
			$methodName = $tokens[$prevEffective]['content'];
			$varTokenPtr = TokenHelper::findPreviousEffective($phpcsFile, $beforePrev - 1);
			if ($varTokenPtr !== null) {
				$varName = $tokens[$varTokenPtr]['content'];
				if ($varName === '$this') {
					$classPtr = ClassHelper::getClassPointer($phpcsFile, $openParen);
					if ($classPtr !== null) {
						$className = ClassHelper::getName($phpcsFile, $classPtr);
						return $this->resolveMethodParameters($phpcsFile, $className, $methodName, $openParen);
					}
				}

				// Check (new Class())->method(...)
				if (
					$tokens[$varTokenPtr]['code'] === T_CLOSE_PARENTHESIS
					&& isset($tokens[$varTokenPtr]['parenthesis_opener'])
				) {
					$instOpener = $tokens[$varTokenPtr]['parenthesis_opener'];
					$insideNew = TokenHelper::findNextEffective($phpcsFile, $instOpener + 1);
					if ($insideNew !== null && $tokens[$insideNew]['code'] === T_NEW) {
						$instClassPtr = TokenHelper::findNextEffective($phpcsFile, $insideNew + 1);
						if ($instClassPtr !== null) {
							$className = $tokens[$instClassPtr]['content'];
							return $this->resolveMethodParameters($phpcsFile, $className, $methodName, $openParen);
						}
					}
				}

				// Infer variable type by searching backward
				$inferredClass = $this->inferVariableType($phpcsFile, $varName, $varTokenPtr);
				if ($inferredClass !== null) {
					return $this->resolveMethodParameters($phpcsFile, $inferredClass, $methodName, $openParen);
				}

				// Fallback: check if method exists in current file classes
				$candidateParams = [];
				foreach ($this->fileClasses as $className => $methods) {
					if (isset($methods[$methodName])) {
						$candidateParams[] = $methods[$methodName];
					}
				}
				if (count($candidateParams) === 1) {
					return $candidateParams[0];
				}
				if (count($candidateParams) > 1) {
					$first = $candidateParams[0];
					$allSame = true;
					foreach ($candidateParams as $candidate) {
						if ($candidate !== $first) {
							$allSame = false;
							break;
						}
					}
					if ($allSame) {
						return $first;
					}
				}
			}
			return null;
		}

		// Function call: function_name(...)
		if (in_array($prevCode, [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED, T_NAME_RELATIVE], true)) {
			$funcName = $tokens[$prevEffective]['content'];
			return $this->resolveFunctionParameters($phpcsFile, $funcName, $openParen);
		}

		return null;
	}

	private function isInsideAttribute(File $phpcsFile, int $openParen): bool
	{
		$tokens = $phpcsFile->getTokens();
		for ($i = $openParen - 1; $i >= 0; $i--) {
			if ($tokens[$i]['code'] === T_ATTRIBUTE) {
				if (isset($tokens[$i]['attribute_closer']) && $tokens[$i]['attribute_closer'] > $openParen) {
					return true;
				}
			}
			if ($tokens[$i]['code'] === T_SEMICOLON || $tokens[$i]['code'] === T_OPEN_CURLY_BRACKET) {
				break;
			}
		}
		return false;
	}

	private function inferVariableType(File $phpcsFile, string $varName, int $stackPtr): ?string
	{
		$tokens = $phpcsFile->getTokens();

		// Check backwards in current scope
		for ($i = $stackPtr - 1; $i >= 0; $i--) {
			if ($tokens[$i]['code'] === T_VARIABLE && $tokens[$i]['content'] === $varName) {
				$equal = TokenHelper::findNextEffective($phpcsFile, $i + 1);
				if ($equal !== null && $tokens[$equal]['code'] === T_EQUAL) {
					$newPtr = TokenHelper::findNextEffective($phpcsFile, $equal + 1);
					if ($newPtr !== null && $tokens[$newPtr]['code'] === T_NEW) {
						$classPtr = TokenHelper::findNextEffective($phpcsFile, $newPtr + 1);
						$validClassTypes = [
							T_STRING,
							T_NAME_QUALIFIED,
							T_NAME_FULLY_QUALIFIED,
							T_NAME_RELATIVE,
						];
						if ($classPtr !== null && in_array($tokens[$classPtr]['code'], $validClassTypes, true)) {
							return $tokens[$classPtr]['content'];
						}
					}
				}
			}
			if ($tokens[$i]['code'] === T_FUNCTION) {
				// Check function parameters
				$paramNames = FunctionHelper::getParametersNames($phpcsFile, $i);
				$typeHints = FunctionHelper::getParametersTypeHints($phpcsFile, $i);
				foreach ($paramNames as $idx => $param) {
					if ($param === $varName && isset($typeHints[$idx]) && $typeHints[$idx] !== null) {
						return $typeHints[$idx]->getTypeHint();
					}
				}
				break;
			}
		}

		return null;
	}

	/**
	 * @return list<string>|null
	 */
	private function resolveMethodParameters(
		File $phpcsFile,
		string $className,
		string $methodName,
		int $pointer,
	): ?array {
		$cleanClassName = ltrim($className, '\\');

		// Check in file classes
		if (isset($this->fileClasses[$cleanClassName][$methodName])) {
			return $this->fileClasses[$cleanClassName][$methodName];
		}
		if (isset($this->fileClasses[$className][$methodName])) {
			return $this->fileClasses[$className][$methodName];
		}

		// Resolve FQCN
		$fqcn = $this->resolveFullyQualifiedName($phpcsFile, $className, $pointer);
		if (isset($this->fileClasses[$fqcn][$methodName])) {
			return $this->fileClasses[$fqcn][$methodName];
		}

		// Reflection
		$classToReflect = null;
		if (class_exists($fqcn) || interface_exists($fqcn) || trait_exists($fqcn)) {
			$classToReflect = $fqcn;
		}
		elseif (class_exists($cleanClassName) || interface_exists($cleanClassName) || trait_exists($cleanClassName)) {
			$classToReflect = $cleanClassName;
		}

		if ($classToReflect !== null) {
			try {
				$refClass = new ReflectionClass($classToReflect);
				if ($refClass->hasMethod($methodName)) {
					$refMethod = $refClass->getMethod($methodName);
					return array_map(
						static fn ($p): string => $p->getName(),
						$refMethod->getParameters(),
					);
				}
			}
			catch (\Throwable) {}
		}

		return null;
	}

	/**
	 * @return list<string>|null
	 */
	private function resolveFunctionParameters(File $phpcsFile, string $functionName, int $pointer): ?array
	{
		$cleanName = ltrim($functionName, '\\');

		// Check in file functions
		if (isset($this->fileFunctions[$cleanName])) {
			return $this->fileFunctions[$cleanName];
		}
		if (isset($this->fileFunctions[$functionName])) {
			return $this->fileFunctions[$functionName];
		}

		// Resolve FQCN for function
		$fqName = $this->resolveFullyQualifiedName($phpcsFile, $functionName, $pointer);
		if (isset($this->fileFunctions[$fqName])) {
			return $this->fileFunctions[$fqName];
		}

		// Reflection
		$funcToReflect = function_exists($fqName) ? $fqName : (function_exists($cleanName) ? $cleanName : null);
		if ($funcToReflect !== null) {
			try {
				$refFunc = new ReflectionFunction($funcToReflect);
				return array_map(
					static fn ($p): string => $p->getName(),
					$refFunc->getParameters(),
				);
			}
			catch (\Throwable) {}
		}

		return null;
	}

	private function resolveFullyQualifiedName(File $phpcsFile, string $name, int $pointer): string
	{
		if (str_starts_with($name, '\\')) {
			return ltrim($name, '\\');
		}

		$useStatements = UseStatementHelper::getUseStatementsForPointer($phpcsFile, $pointer);
		$nameParts = explode('\\', $name);
		$firstPart = $nameParts[0];

		foreach ($useStatements as $useStatement) {
			if ($useStatement->getNameAsReferencedInFile() === $firstPart) {
				$fqn = $useStatement->getFullyQualifiedTypeName();
				if (count($nameParts) > 1) {
					array_shift($nameParts);
					$fqn .= '\\' . implode('\\', $nameParts);
				}
				return ltrim($fqn, '\\');
			}
		}

		$currentNamespace = NamespaceHelper::findCurrentNamespaceName($phpcsFile, $pointer);
		if ($currentNamespace !== null) {
			return $currentNamespace . '\\' . $name;
		}

		return $name;
	}
}
