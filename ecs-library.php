<?php

use PHP_CodeSniffer\Standards\Generic\Sniffs\Arrays\DisallowLongArraySyntaxSniff;
use Stefna\Sniffs\Commenting\DocCommentSniff;
use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\ValueObject\Option;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return static function (ECSConfig $ecsConfig): void {
	// A. full sets
	$ecsConfig->sets([SetList::PSR_12]);
	$ecsConfig->indentation(Option::INDENTATION_TAB);

	$ecsConfig->rule(DisallowLongArraySyntaxSniff::class);

	// B. standalone rule
	$ecsConfig->ruleWithConfiguration(DocCommentSniff::class, [
		'syntax' => 'short',
	]);
};
