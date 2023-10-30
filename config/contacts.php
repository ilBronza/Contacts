<?php

use IlBronza\Contacts\Http\Controllers\Contacts\ContactCreateController;
use IlBronza\Contacts\Http\Controllers\Contacts\ContactDestroyController;
use IlBronza\Contacts\Http\Controllers\Contacts\ContactIndexController;
use IlBronza\Contacts\Http\Controllers\Contacttypes\ContacttypeController;
use IlBronza\Contacts\Http\Parameters\FieldsetsParameters\ContacttypeFieldsetsParameters;
use IlBronza\Contacts\Http\Parameters\FieldsetsParameters\CreateContactFieldsetsParameters;
use IlBronza\Contacts\Http\Parameters\TableFields\ContactTableFieldsParameters;
use IlBronza\Contacts\Http\Parameters\TableFields\ContactTableRelatedFieldsParameters;
use IlBronza\Contacts\Http\Parameters\TableFields\ContacttypeTableFieldsParameters;
use IlBronza\Contacts\Models\Contact;
use IlBronza\Contacts\Models\Contacttype;

return [

    'routePrefix' => 'contacts',
    
    'models' => [
        'contact' => [
            'table' => 'contacts__contacts',
            'class' => Contact::class,
            'controllers' => [
                'index' => ContactIndexController::class,
                'createBy' => ContactCreateController::class,
                'storeBy' => ContactCreateController::class,
                'destroy' => ContactDestroyController::class
            ],
            'parametersFiles' => [
                'create' => CreateContactFieldsetsParameters::class,
            ],
            'fieldsGroupsFiles' => [
                'index' => ContactTableFieldsParameters::class,
                'related' => ContactTableRelatedFieldsParameters::class,
            ]
        ],
        'contacttype' => [
            'table' => 'contacts__contacttypes',
            'class' => Contacttype::class,
            'controllers' => [
                'index' => ContacttypeController::class,
                'create' => ContacttypeController::class,
                'store' => ContacttypeController::class,
                'show' => ContacttypeController::class,
                'edit' => ContacttypeController::class,
                'update' => ContacttypeController::class,
                'destroy' => ContacttypeController::class
            ],
            'parametersFiles' => [
                'show' => ContacttypeFieldsetsParameters::class,
            ],
            'fieldsGroupsFiles' => [
                'index' => ContacttypeTableFieldsParameters::class,
            ]
        ]
    ]
];