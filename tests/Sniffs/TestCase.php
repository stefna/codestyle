<?php declare(strict_types=1);

namespace StefnaTest\Sniffs;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use PHP_CodeSniffer\Config;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Files\LocalFile;
use PHP_CodeSniffer\Runner;
use PHP_CodeSniffer\Util\Common;

/**
* @based of https://github.com/slevomat/coding-standard/blob/master/SlevomatCodingStandard/Sniffs/TestCase.php
*/
class TestCase extends PHPUnitTestCase
{
	protected function checkFile(string $fileVairant): File
	{

		$codeSniffer = new Runner();
		$codeSniffer->config = new Config(['-s', '--standard=phpcs.xml']);
		$codeSniffer->init();

		$sniffFqcn = static::getSniffFqcn();
		$sniff = new $sniffFqcn();

		$codeSniffer->ruleset->populateTokenListeners();

		$filePath = static::getSniffDataVariantFilePath($fileVairant);

		$file = new LocalFile($filePath, $codeSniffer->ruleset, $codeSniffer->config);
		$file->process();

		return $file;
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

	protected static function getSniffDataVariantFilePath(string $variant): string
	{
		$fqcn = static::getSniffFqcn();
		$parts = explode('\\', $fqcn);
		$name = array_pop($parts);
		$group = array_pop($parts);

		return sprintf(
			'%s/%s/data/%s.%s.php',
			__DIR__,
			$group,
			$name,
			$variant
		);
	}

	protected static function assertNoSniffErrorInFile(File $phpCsFile): void
	{
		$errors = $phpCsFile->getErrors();
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

	protected static function assertSniffError(File $phpcsFile, int $line, string $code, ?string $message = null): void
	{
		$errors = $phpcsFile->getErrors();
		self::assertTrue(isset($errors[$line]), sprintf('Expected error on line %s, but none found.', $line));

		$sniffCode = sprintf('%s.%s', self::getSniffName(), $code);

		self::assertTrue(
			self::hasError($errors[$line], $sniffCode, $message),
			sprintf(
				'Expected error %s%s, but none found on line %d.%sErrors found on line %d:%s%s%s',
				$sniffCode,
				$message !== null
					? sprintf(' with message "%s"', $message)
					: '',
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
	 * @param list<list<array{source: string, message: string}>> $errorsOnLine
	 */
	private static function hasError(array $errorsOnLine, string $sniffCode, ?string $message): bool
	{
		$hasError = false;

		foreach ($errorsOnLine as $errorsOnPosition) {
			foreach ($errorsOnPosition as $error) {
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
					$hasError = true;
					break;
				}
			}
		}

		return $hasError;
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

	protected static function assertAllFixedInFile(File $phpcsFile): void
	{
		$okFilePath = static::getSniffDataVariantFilePath('OK');

		// $phpcsFile->disableCaching();
		$phpcsFile->fixer->fixFile();
		self::assertStringEqualsFile($okFilePath, $phpcsFile->fixer->getContents());
	}
}
