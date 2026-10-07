<?php

namespace IlBronza\Contacts\Http\Controllers\Contacts;

use IlBronza\Contacts\Http\Controllers\BaseContactPackageController;
use IlBronza\Contacts\Support\ContactableResolver;

class ContactByModelController extends BaseContactPackageController
{
	public $allowedMethods = ['byModel'];
	static $modelConfigPrefix = 'contact';

	public function byModel(string $modelClass, string $modelId)
	{
		$model = ContactableResolver::resolve($modelClass, $modelId);
        $key = $model->getKey();
        $contacts = $model->contacts()->with('contacttype')->get();

		return view('contacts::contacts._table', compact('contacts', 'key', 'model'))->render();
	}

}
