<?php declare(strict_types=1);

namespace StefnaTest\Sniffs\Attributes\data\AttributeFormatSniff;

class TwoArgsUnder120
{
	#[SomeRoute(path: '/api/v1/user/account/details', name: 'user_account_view_details')]
	public function test(): void {}
}
