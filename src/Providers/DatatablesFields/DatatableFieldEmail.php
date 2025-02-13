<?php

namespace IlBronza\Contacts\Providers\DatatablesFields;

use IlBronza\Contacts\Models\Contacttype;
use IlBronza\Datatables\DatatablesFields\DatatableField;

class DatatableFieldEmail extends DatatableFieldSingle
{
	public $width = '22rem';
	public string $contacttype = 'email';
}