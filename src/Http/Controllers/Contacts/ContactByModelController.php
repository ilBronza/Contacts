<?php

namespace IlBronza\Contacts\Http\Controllers\Contacts;

use IlBronza\Contacts\Http\Controllers\BaseContactPackageController;
use IlBronza\Contacts\Models\Contact;
use Illuminate\Database\Eloquent\Relations\Relation;

use function get_class_methods;

class ContactByModelController extends BaseContactPackageController
{
	public $allowedMethods = ['byModel'];
	static $modelConfigPrefix = 'contact';

	public function byModel(string $modelClass, string $modelId)
	{
		$contactableClass = Contact::make()->getActualClassNameForMorph($modelClass);

		$model = $contactableClass::find($key = $modelId);

		if(! $model->userCanSee())
			abort(403);

		$contacts = $model->getContacts('conctacttype');

		return view('contacts::contacts._table', compact('contacts', 'key', 'model'))->render();
	}

    public function getIndexElements()
    {
        $with = $this->getIndexElementsRelationsArray();

        return $this->getModelClass()::with($with)->get();
    }

}