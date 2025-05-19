<?php

namespace IlBronza\Contacts\Http\Controllers;

use IlBronza\CRUD\Http\Controllers\BasePackageController;

class BaseContactPackageController extends BasePackageController
{
	public $configModelClassName = 'contact';
    static $packageConfigPrefix = 'contacts';
    // static $configFileName = 'contacts';
}