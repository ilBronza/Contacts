<?php

namespace IlBronza\Contacts\Models;

use IlBronza\CRUD\Models\BaseModel;
use IlBronza\CRUD\Traits\CRUDSluggableTrait;
use IlBronza\CRUD\Traits\Model\PackagedModelsTrait;
use IlBronza\Contacts\Models\Contact;

class Contacttype extends BaseModel
{
	public $incrementing = false;
	protected $keyType = 'string';
	protected $primaryKey = 'slug';

	protected $fillable = [
		'name'
	];

	use PackagedModelsTrait;

	use CRUDSluggableTrait;

	static $packageConfigPrefix = 'contacts';
	static $modelConfigPrefix = 'contacttype';
    
    public function contacts()
    {
    	return $this->hasMany(Contact::getProjectClassName());
    }

    public function getRelatedContacts()
    {
    	return $this->contacts()->with('contactable', 'contacttype')->get();
    }

	public function getHrefString() : ? string
	{
		return $this->href_string;
	}

	public function getIcon() :  ? string
	{
		if(! $this->fa_icon)
			return "<i title=\"{$this->getName()}\" class=\"fa-solid fa-address-card\"></i>";

		return "<i title=\"{$this->getName()}\" class=\"fa-solid fa-{$this->fa_icon}\"></i>";
	}

	public function hasHref() : bool
	{
		return !! $this->getHrefString();
	}
}