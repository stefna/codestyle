<?php declare(strict_types=1);

namespace Stefna\Sniffs\ControlStructures;

use PHP_CodeSniffer\Sniffs\AbstractPatternSniff;

class ElseCatchSniff extends AbstractPatternSniff
{
	/**
	 *  Obtain the patterns that this test wishes to verify
	 *
	 *  The getPatterns() method is used internally to obtain a list
	 *  of patterns to describe white-listed control structures and the
	 *  indentation that must be used within.
	 */
	protected function getPatterns()
	{
		return [
			"try {EOL...}EOL",
			"}EOLcatch (...) {EOL...}EOL",
			"do {EOL...}EOL",
			"while (...) {EOL...}EOL",
			"for (...) {EOL",
			"if (...) {EOL",
			"foreach (...) {EOL...}EOL",
			"}EOLelse {EOL...}EOL",
			"}EOLelse if (...) {EOL...}EOL",
			"}EOLelseif (...) {EOL...}EOL",
			"}EOLelse try {EOL...}EOL",
			"}EOLelse if (...) try {EOL...}EOL",
			"}EOLelseif (...) try {EOL...}EOL",
			"}EOLelse throw ",
		];
	}
}
