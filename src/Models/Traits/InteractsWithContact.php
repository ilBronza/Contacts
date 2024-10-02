<?php

namespace IlBronza\Contacts\Models\Traits;

use IlBronza\Contacts\Models\Contact;
use IlBronza\Contacts\Models\Contacttype;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

trait InteractsWithContact
{
	public function getContactsByTypes(array $types = []) : Collection
	{
		return $this->getContacts()->whereIn('contacttype_slug', $types);
	}

	public function getContactsByType(string $type) : Collection
	{
		return $this->getContacts()->where('contacttype_slug', $type);
	}

	public function contacts() : MorphMany
    {
    	return $this->morphMany(Contact::getProjectClassName(), 'contactable');
    }

	public function getContacts() : Collection
	{
		return $this->contacts;
	}

	public function getMobileString() : ? string
	{
		return $this->getContactsByType('mobile')->first()?->contact;
	}

    public function addContact(string $contactString, string $type) : Contact
    {
        $contacttype = Contacttype::getProjectClassName()::firstOrNew(['name' => $type]);

        if(! $contacttype->exists)
            $contacttype->save();

        if(! $contact = Contact::getProjectClassName()::byType($contacttype)->where('contact', $contactString)->first())
            $contact = Contact::getProjectClassName()::make();

        $contact->contact = $contactString;
        $contact->contacttype_slug = $contacttype->getKey();

        $this->contacts()->save($contact);

        return $contact;
    }
}