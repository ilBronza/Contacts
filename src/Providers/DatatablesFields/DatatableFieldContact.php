<?php

namespace IlBronza\Contacts\Providers\DatatablesFields;

use IlBronza\Contacts\Models\Contacttype;
use IlBronza\Datatables\DatatablesFields\DatatableField;

class DatatableFieldContact extends DatatableField
{
	public function transformValue($value)
	{
		return $value->getFullString();
	}
}