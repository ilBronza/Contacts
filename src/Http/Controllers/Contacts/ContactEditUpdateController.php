<?php

namespace IlBronza\Contacts\Http\Controllers\Contacts;

use IlBronza\Contacts\Http\Controllers\BaseContactPackageController;
use IlBronza\CRUD\Traits\CRUDEditUpdateTrait;
use Illuminate\Http\Request;

use function config;

class ContactEditUpdateController extends BaseContactPackageController
{
	public $configModelClassName = 'contact';

	use CRUDEditUpdateTrait;

	public $allowedMethods = ['edit', 'update'];

	public function getEditParametersFile() : ? string
	{
		return config($this->getBaseConfigName() . ".models.$this->configModelClassName.parametersFiles.edit");
	}

	public function edit(string $contact)
	{
		$contact = $this->findModel($contact);

		return $this->_edit($contact);
	}

	public function update(Request $request, $contact)
	{
		$contact = $this->findModel($contact);

		return $this->_update($request, $contact);
	}
}
