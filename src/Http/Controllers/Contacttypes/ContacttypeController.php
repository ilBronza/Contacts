<?php

namespace IlBronza\Contacts\Http\Controllers\Contacttypes;

use IlBronza\CRUD\Http\Controllers\Traits\PackageStandardIndexTrait;
use IlBronza\CRUD\Traits\CRUDBelongsToManyTrait;
use IlBronza\CRUD\Traits\CRUDCreateStoreTrait;
use IlBronza\CRUD\Traits\CRUDDeleteTrait;
use IlBronza\CRUD\Traits\CRUDDestroyTrait;
use IlBronza\CRUD\Traits\CRUDEditUpdateTrait;
use IlBronza\CRUD\Traits\CRUDRelationshipTrait;
use IlBronza\CRUD\Traits\CRUDShowTrait;
use IlBronza\CRUD\Traits\CRUDUpdateEditorTrait;
use IlBronza\Contacts\Http\Controllers\BaseContactPackageController;
use IlBronza\Contacts\Models\Contacttype;
use Illuminate\Http\Request;

class ContacttypeController extends BaseContactPackageController
{
    use PackageStandardIndexTrait;

    static $modelConfigPrefix = 'contacttype';

    use CRUDShowTrait;
    use CRUDEditUpdateTrait;
    use CRUDUpdateEditorTrait;
    use CRUDCreateStoreTrait;

    use CRUDDeleteTrait;
    use CRUDDestroyTrait;

    use CRUDRelationshipTrait;
    use CRUDBelongsToManyTrait;

    public function getGenericParametersFile() : ? string
    {
        return config('contacts.models.contacttype.parametersFiles.show');
    }

    /**
     * http methods allowed. remove non existing methods to get a 403
     **/
    public $allowedMethods = [
        'index',
        'show',
        'edit',
        'update',
        'create',
        'store',
        'destroy',
    ];

    public function getIndexElementsRelationsArray() : array
    {
        return [];
    }

    public function getRelationshipsManagerClass()
    {
        return config("contacts.models.contacttype.relationshipsManagerClasses.show");
    }

    public function getIndexElements()
    {
        return $this->getModelClass()::withCount('contacts')->get();
    }

    public function getContacttype(int|string $contacttype)
    {
        return $this->getModelClass()::find($contacttype);
    }

    public function show(int|string $contacttype)
    {
        $contacttype = $this->getContacttype($contacttype);

        return $this->_show($contacttype);
    }

    public function edit(int|string $contacttype)
    {
        $contacttype = $this->getContacttype($contacttype);

        return $this->_edit($contacttype);
    }

    public function update(Request $request, int|string $contacttype)
    {
        $contacttype = $this->getContacttype($contacttype);

        return $this->_update($request, $contacttype);
    }

    public function destroy(int|string $contacttype)
    {
        $contacttype = $this->getContacttype($contacttype);

        return $this->_destroy($contacttype);
    }
}

