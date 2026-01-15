<?php declare(strict_types=1);

namespace StefnaTest\Sniffs\Functions\data\BlankLinesSniff;

class ClassMethods
{
	public function test(): bool
	{
		var_dump(
			1,
		);

		/*
		$table->createForeignKey(
			$author,
			new ForeignReference(Accounts::NAME, 'id'),
			onDelete: ForeignAction::Cascade,
			onUpdate: ForeignAction::Cascade,
		);
		 */

		return false;
	}
}
