<?php

namespace IlBronza\Contacts\Http\Controllers\Contacts;

use IlBronza\CRUD\Traits\CRUDCreateStoreByParentModelTrait;
use IlBronza\Contacts\Http\Controllers\BaseContactPackageController;
use IlBronza\Contacts\Support\ContactableResolver;

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
        return array_map(static fn (string $class) => (new $class)->getMorphClass(), ContactableResolver::classes());
    }

    public function setStoreByProperties(string $model, string $key)
    {
        $this->polimorphicParentModel = ContactableResolver::resolve($model, $key, true);
        $this->relatedModel = $this->polimorphicParentModel->getMorphClass();
        $this->relatedKey = $this->polimorphicParentModel->getKey();
    }

    public function getModelDefaultPolimorphicParameters()
    {
        return [
            'contactable_type' => $this->relatedModel,
            'contactable_id' => $this->relatedKey,
        ];
    }

    public function getGenericParametersFile() : ? string
    {
        return config('contacts.models.contact.parametersFiles.create');
    }
}
