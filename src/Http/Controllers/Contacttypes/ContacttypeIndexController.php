<?php

namespace IlBronza\Contacts\Http\Controllers\Contacttypes;

use IlBronza\Contacts\Http\Controllers\BaseContactPackageController;
use IlBronza\CRUD\Http\Controllers\Traits\StandardTraits\PackageStandardIndexTrait;

class ContacttypeIndexController extends BaseContactPackageController
{
    use PackageStandardIndexTrait;

    static $modelConfigPrefix = 'contacttype';
    public $allowedMethods = ['index'];

    public function getIndexElementsRelationsArray() : array
    {
        return [
        ];
    }

    public function getIndexElements()
    {
        $with = $this->getIndexElementsRelationsArray();

        return $this->getModelClass()::with($with)->get();
    }
    
}

