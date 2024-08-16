<?php

namespace IlBronza\Contacts\Http\Controllers\Contacts;

use IlBronza\AccountManager\Models\User;
use IlBronza\CRUD\Traits\CRUDCreateStoreByParentModelTrait;
use IlBronza\Contacts\Http\Controllers\BaseContactPackageController;

use function config;

class ContactCreateController extends BaseContactPackageController
{
	public bool $hasPolimorphicRelationship = true;

	use CRUDCreateStoreByParentModelTrait;

    static $modelConfigPrefix = 'contact';

    public $allowedMethods = [
        'createBy',
        'storeBy',
    ];

    public function getAssociableClassesList() : array
    {
		$result = [];

		foreach(config('contacts.associableModelTypesFullClass') as $class)
			$result[] = $class::make()->getMorphClass();

		return $result;
    }

    public function getGenericParametersFile() : ? string
    {
        return config('contacts.models.contact.parametersFiles.create');
    }
}