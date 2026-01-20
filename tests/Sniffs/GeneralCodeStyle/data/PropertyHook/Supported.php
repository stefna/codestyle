<?php declare(strict_types=1);

namespace StefnaTest\Sniffs\GeneralCodeStyle\data\Formatting;

class ClassMethods
{
	public TenantId $id {
		get {
			return $this->tenant->id;
		}
	}
}
