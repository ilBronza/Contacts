<?php

namespace IlBronza\Contacts\Models;

use IlBronza\CRUD\Models\PackagedBaseModel;
use IlBronza\CRUD\Models\Scopes\ActiveScope;
use IlBronza\Contacts\Models\Contacttype;
use Illuminate\Database\Eloquent\Model;

use function csrf_field;
use function method_field;

class Contact extends PackagedBaseModel
{
	static $deletingRelationships = [];

	static $packageConfigPrefix = 'contacts';
	static $modelConfigPrefix = 'contact';

	public function contacttype()
	{
		return $this->belongsTo(Contacttype::getProjectClassName());
	}

	public function getContacttype() : ? Contacttype
	{
		if($this->relationLoaded('contacttype'))
			return $this->contacttype;

		$contacttype = Contacttype::gpc()::findCached($this->contacttype_slug);

		$this->relationLoaded('contacttype', $contacttype);

		return $contacttype;
	}

	public function getType() : ? Contacttype
	{
		return $this->getContacttype();
	}

	public function contactable()
	{
		return $this->morphTo()->withoutGlobalScope(ActiveScope::class);
	}

	public function getContactable() : Model
	{
		return $this->contactable;
	}

	public function scopeByType($query, Contacttype $contacttype)
	{
		return $query->where('contacttype_slug', $contacttype->getKey());
	}

	public function getContact() : ? string
	{
		return $this->contact;
	}

	public function getIconString() : ? string
	{
		return $this->getContacttype()->getIcon();
	}

	public function getFullString() : string
	{
		return view('contacts::contacts._contact', ['contact' => $this])->render();
	}

	public function getDeleteButton()
	{
		return '<form method="POST" onSubmit="return confirm(\'Sei sicuro?\');" action="' . $this->getDeleteUrl() . '">' . csrf_field() . ' ' . method_field('DELETE') . '<button class="uk-button uk-button-small" type="submit"><i class="fa-solid fa-trash"></i></button></form>';
	}
}