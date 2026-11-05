<?php declare(strict_types=1);

namespace StefnaTest\Sniffs\Attributes\data\AttributeFormatSniff;

class ThreeOrMoreArgsOver80
{
	#[SomeRoute(path: '/api/v1/user/account', name: 'user_account_view', methods: ['GET'])]
	public function test(): void {}
}
