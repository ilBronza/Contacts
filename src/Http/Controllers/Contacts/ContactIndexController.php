<?php

namespace IlBronza\Contacts\Http\Controllers\Contacts;

use IlBronza\CRUD\Http\Controllers\Traits\PackageStandardIndexTrait;
use IlBronza\Contacts\Http\Controllers\BaseContactPackageController;

class ContactIndexController extends BaseContactPackageController
{
    use PackageStandardIndexTrait;

    static $modelConfigPrefix = 'contact';
    public $allowedMethods = ['index'];

    public $avoidCreateButton = true;

    public function getIndexElementsRelationsArray() : array
    {
        return [
            'contacttype',
            'contactable'
        ];
    }

    public function getIndexElements()
    {
        $with = $this->getIndexElementsRelationsArray();

        return $this->getModelClass()::with($with)->get();
    }

}