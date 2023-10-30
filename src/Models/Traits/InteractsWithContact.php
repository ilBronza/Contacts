<?php

namespace IlBronza\Contacts\Models\Traits;

use IlBronza\Contacts\Models\Contact;
use IlBronza\Contacts\Models\Contacttype;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait InteractsWithContact
{
    public function contacts() : MorphMany
    {
    	return $this->morphMany(Contact::getProjectClassName(), 'contactable');
    }

    public function addContact(string $contactString, string $type) : Contact
    {
        $contacttype = Contacttype::getProjectClassName()::firstOrNew(['name' => $type]);

        if(! $contacttype->exists)
            $contacttype->save();

        $contact = Contact::getProjectClassName()::make();
        $contact->contact = $contactString;
        $contact->contacttype_slug = $contacttype->getKey();

        $this->contacts()->save($contact);

        return $contact;
    }
}