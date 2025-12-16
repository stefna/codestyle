<?php declare(strict_types=1);

namespace StefnaTest\Sniffs;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use PHP_CodeSniffer\Config;
use PHP_CodeSniffer\Files\LocalFile;
use PHP_CodeSniffer\Runner;
use PHP_CodeSniffer\Util\Common;

/**
* @based of https://github.com/slevomat/coding-standard/blob/master/SlevomatCodingStandard/Sniffs/TestCase.php
*/
class TestCase extends PHPUnitTestCase
{
	private static LocalFile $report;
	private static array $foundErrorsMap = [];

	protected function checkFile(string $fileVairant): void
	{
		self::$foundErrorsMap = [];

		$codeSniffer = new Runner();
		$codeSniffer->config = new Config(['-s', '--standard=phpcs.xml']);
		$codeSniffer->init();

		$sniffFqcn = static::getSniffFqcn();
		$sniff = new $sniffFqcn();

		$codeSniffer->ruleset->populateTokenListeners();

		$filePath = static::getSniffDataVariantFilePath($fileVairant);

		self::$report = new LocalFile($filePath, $codeSniffer->ruleset, $codeSniffer->config);
		self::$report->process();
	}

	/**
	 * @return class-string
	 */
	protected static function getSniffFqcn(): string
	{
		return str_replace('Test', '', static::class);
	}

	protected static function getSniffName(): string
	{
		return Common::getSniffCode(static::getSniffFqcn());
	}

	private static function getSniffDataFolder(): string
	{
		$fqcn = static::getSniffFqcn();
		$parts = explode('\\', $fqcn);
		$name = array_pop($parts);
		$group = array_pop($parts);

		return sprintf(
			'%s/%s/data/%s',
			__DIR__,
			$group,
			$name,
		);
	}

	protected static function getSniffDataVariantFilePath(string $variant): string
	{
		return sprintf(
			'%s/%s.php',
			self::getSniffDataFolder(),
			$variant,
		);
	}

	protected static function assertNoSniffErrorInFile(): void
	{
		$errors = self::$report->getErrors();
		$text = sprintf('No errors expected, but %d errors found:', count($errors));

		foreach ($errors as $line => $error) {
			$text .= sprintf(
				'%sLine %d:%s%s',
				PHP_EOL,
				$line,
				PHP_EOL,
				self::getFormattedErrors($error),
			);
		}

		self::assertEmpty($errors, $text);
	}

	protected static function assertSniffError(string $code, int $line, int $occurance = 1, ?string $message = null): void
	{
		$errors = self::$report->getErrors();
		self::assertTrue(isset($errors[$line]), sprintf('Expected error on line %s, but none found.', $line));

		$sniffCode = sprintf('%s.%s', self::getSniffName(), $code);

		$nrOfErrors = self::hasError($errors, $line, $sniffCode, $message);

		self::assertSame(
			$occurance,
			$nrOfErrors,
			sprintf(
				'Expected %d error %s%s, but %d found on line %d.%sErrors found on line %d:%s%s%s',
				$occurance,
				$sniffCode,
				$message !== null
					? sprintf(' with message "%s"', $message)
					: '',
				$nrOfErrors,
				$line,
				PHP_EOL . PHP_EOL,
				$line,
				PHP_EOL,
				self::getFormattedErrors($errors[$line]),
				PHP_EOL,
			),
		);
	}


	/**
	 * @param array<int, array<int, list<array{source: string, message: string}>>> $errors
	 */
	private static function hasError(array $errors, int $line, string $sniffCode, ?string $message): int
	{
		$nrOfErrors = 0;

		foreach ($errors[$line] as $column => $errorsOnPosition) {
			foreach ($errorsOnPosition as $index => $error) {
				/** @var string $errorSource */
				$errorSource = $error['source'];
				/** @var string $errorMessage */
				$errorMessage = $error['message'];

				if (
					$errorSource === $sniffCode
					&& (
						$message === null
						|| strpos($errorMessage, $message) !== false
					)
				) {
					++$nrOfErrors;
					self::$foundErrorsMap[$line] ??= [$column => []];
					self::$foundErrorsMap[$line][$column][] = $index;
				}
			}
		}

		return $nrOfErrors;
	}

	/**
	 * @param list<list<array{source: string, message: string}>> $errors
	 */
	private static function getFormattedErrors(array $errors): string
	{
		return implode(
			PHP_EOL,
			array_map(
				static fn (array $errors): string => implode(
					PHP_EOL,
					array_map(static fn (array $error): string => sprintf("\t%s: %s", $error['source'], $error['message']), $errors),
				),
				$errors,
			),
		);
	}

	protected static function assertAllErrorsChecked(): void
	{
		$errors = self::$report->getErrors();
		foreach ($errors as $line => $errorsOnLine) {
			self::assertTrue(
				array_key_exists($line, self::$foundErrorsMap),
				sprintf(
					'No error checked on line %s, but some exist%s%s',
					$line,
					PHP_EOL,
					self::getFormattedErrors($errorsOnLine),
				),
			);
			foreach ($errorsOnLine as $column => $errorOnPosition) {
				self::assertTrue(
					array_key_exists($column, self::$foundErrorsMap[$line]),
					sprintf(
						'No error checked on line %d column %d, but some exist%s%s',
						$line,
						$column,
						PHP_EOL,
						self::getFormattedErrors([$errorOnPosition]),
					),
				);
				foreach ($errorOnPosition as $index => $error) {
					self::assertTrue(
						in_array($index, self::$foundErrorsMap[$line][$column]),
						sprintf('Error has not been checked%s%s', PHP_EOL, self::getFormattedErrors([[$error]])),
					);
				}
			}
		}
	}

	protected static function assertAllFixedInFile(?string $fixedVariant = null): void
	{
		if ($fixedVariant === null) {
			$okFilePath = substr_replace(self::$report->getFilename(), '.fixed', -4, 0);
		}
		else {
			$okFilePath = static::getSniffDataVariantFilePath($fixedVariant . '.fixed');
		}

		self::assertAllErrorsChecked(self::$report);

		// self::$report->disableCaching();
		self::$report->fixer->fixFile();
		self::assertStringEqualsFile($okFilePath, self::$report->fixer->getContents());
	}
	/**
	 * @return \Generator<array{string}>
	 */
	public static function fixedFilesProvider(): \Generator
	{
		$sniffParts = explode('\\', static::getSniffFqcn());
		$name = array_pop($sniffParts);

		$searchFiles = sprintf('%s/*.fixed.php', self::getSniffDataFolder(), $name);
		$goodFiles = glob($searchFiles);

		foreach ($goodFiles as $goodFile) {
			$filePathParts = explode('/', $goodFile);
			$fileName = array_pop($filePathParts);
			$variant = explode('.', $fileName)[0];

			yield [$variant . '.fixed'];
		}
	}

	#[DataProvider('fixedFilesProvider')]
	public function testNoErrors(string $validFile): void
	{
		$this->checkFile($validFile);
		self::assertNoSniffErrorInFile();
	}
}
