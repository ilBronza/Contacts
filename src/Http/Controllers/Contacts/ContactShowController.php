<?php

namespace IlBronza\Contacts\Http\Controllers\Contacts;

use IlBronza\CRUD\Http\Controllers\BasePackageShowTrait;
use IlBronza\Contacts\Http\Controllers\BaseContactPackageController;

class ContactShowController extends BaseContactPackageController
{
    use BasePackageShowTrait;

    public function show(string $contact)
    {
        $contact = $this->findModel($contact);

        return $this->_show($contact);
    }
}
