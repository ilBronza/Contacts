<?php

namespace IlBronza\Contacts\Http\Controllers\Contacts;

use IlBronza\AccountManager\Models\User;
use IlBronza\CRUD\Traits\CRUDCreateStoreByParentModelTrait;
use IlBronza\Contacts\Http\Controllers\BaseContactPackageController;

class ContactCreateController extends BaseContactPackageController
{
    use CRUDCreateStoreByParentModelTrait;

    static $modelConfigPrefix = 'contact';

    public $allowedMethods = [
        'createBy',
        'storeBy',
    ];

    public function getAssociableClassesList() : array
    {
        return [
            User::getProjectClassName()
        ];
    }

    public function getGenericParametersFile() : ? string
    {
        return config('contacts.models.contact.parametersFiles.create');
    }
}