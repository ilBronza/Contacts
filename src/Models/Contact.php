<?php

namespace IlBronza\Contacts\Models;

use IlBronza\CRUD\Models\BaseModel;
use IlBronza\CRUD\Traits\Model\PackagedModelsTrait;
use IlBronza\Contacts\Models\Contacttype;

class Contact extends BaseModel
{
	use PackagedModelsTrait;

	static $deletingRelationships = [];

	static $packageConfigPrefix = 'contacts';
	static $modelConfigPrefix = 'contact';

	public function contacttype()
	{
		return $this->belongsTo(Contacttype::getProjectClassName());
	}

	public function contactable()
	{
		return $this->morphTo();
	}
}