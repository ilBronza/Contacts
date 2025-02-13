<?php

namespace IlBronza\Contacts\Providers\DatatablesFields;

use IlBronza\Contacts\Models\Contacttype;
use IlBronza\Datatables\DatatablesFields\DatatableField;

class DatatableFieldMobile extends DatatableFieldSingle
{
	public $width = '12rem';
	public string $contacttype = 'mobile';
}