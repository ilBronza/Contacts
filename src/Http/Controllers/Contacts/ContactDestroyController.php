<?php

namespace IlBronza\Contacts\Http\Controllers\Contacts;

use IlBronza\CRUD\Traits\CRUDDeleteTrait;
use IlBronza\CRUD\Traits\CRUDDestroyTrait;
use IlBronza\Contacts\Http\Controllers\BaseContactPackageController;

class ContactDestroyController extends BaseContactPackageController
{
    use CRUDDeleteTrait;
    use CRUDDestroyTrait;

    static $modelConfigPrefix = 'contact';
    public $allowedMethods = ['destroy'];

    public function destroy(int|string $contact)
    {
        $contact = $this->getModelInstance($contact);

        return $this->_destroy($contact);
    }
}