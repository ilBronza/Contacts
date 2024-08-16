<?php

namespace IlBronza\Contacts\Http\Controllers\Parameters\RelationshipsManagers;

use IlBronza\CRUD\Providers\RelationshipsManager\RelationshipsManager;

class ContacttypeRelationshipManager Extends RelationshipsManager
{
	public  function getAllRelationsParameters() : array
	{
		return [
			'show' => [
				'relations' => [
					'contacts' => config('contacts.models.contact.controllers.index')
				]
			]
		];
	}
}