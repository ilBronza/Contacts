<?php

namespace IlBronza\Contacts\Providers\DatatablesFields;

use IlBronza\Contacts\Models\Contacttype;
use IlBronza\Datatables\DatatablesFields\DatatableField;

class DatatableFieldSingle extends DatatableField
{
	public string $contacttype;

	public function getContacttype()
	{
		return $this->contacttype;
	}

	public function transformValue($value)
	{
		if(! $value)
			return null;
		
		foreach($value as $_value)
			if($_value->contacttype_slug == $this->getContacttype())
				return $_value->getFullString();

		return null;
	}
}