<?php

namespace IlBronza\Contacts\Models;

use IlBronza\CRUD\Models\PackagedBaseModel;
use IlBronza\CRUD\Models\Scopes\ActiveScope;
use IlBronza\Contacts\Models\Contacttype;

class Contact extends PackagedBaseModel
{
	static $deletingRelationships = [];

	static $packageConfigPrefix = 'contacts';
	static $modelConfigPrefix = 'contact';

	public function contacttype()
	{
		return $this->belongsTo(Contacttype::getProjectClassName());
	}

	public function contactable()
	{
		return $this->morphTo()->withoutGlobalScope(ActiveScope::class);
	}

	public function scopeByType($query, Contacttype $contacttype)
	{
		return $query->where('contacttype_slug', $contacttype->getKey());
	}
}