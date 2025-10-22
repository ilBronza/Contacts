<?php

use IlBronza\AccountManager\Models\User;
use IlBronza\Clients\Models\Client;
use IlBronza\Contacts\Http\Controllers\Contacts\ContactByModelController;
use IlBronza\Contacts\Http\Controllers\Contacts\ContactCreateController;
use IlBronza\Contacts\Http\Controllers\Contacts\ContactDestroyController;
use IlBronza\Contacts\Http\Controllers\Contacts\ContactEditUpdateController;
use IlBronza\Contacts\Http\Controllers\Contacts\ContactIndexController;
use IlBronza\Contacts\Http\Controllers\Contacts\ContactShowController;
use IlBronza\Contacts\Http\Controllers\Contacttypes\ContacttypeController;
use IlBronza\Contacts\Http\Controllers\Contacttypes\ContacttypeIndexController;
use IlBronza\Contacts\Http\Controllers\Parameters\RelationshipsManagers\ContacttypeRelationshipManager;
use IlBronza\Contacts\Http\Parameters\FieldsetsParameters\ContacttypeFieldsetsParameters;
use IlBronza\Contacts\Http\Parameters\FieldsetsParameters\CreateContactFieldsetsParameters;
use IlBronza\Contacts\Http\Parameters\TableFields\ContactTableFieldsParameters;
use IlBronza\Contacts\Http\Parameters\TableFields\ContactTableRelatedFieldsParameters;
use IlBronza\Contacts\Http\Parameters\TableFields\ContacttypeTableFieldsParameters;
use IlBronza\Contacts\Models\Contact;
use IlBronza\Contacts\Models\Contacttype;
use IlBronza\Operators\Models\Operator;

return [

    'enabled' => false,

    'datatableFieldWidths' => [
		'datatableFieldContact' => 'auto',
        'datatableFieldEmail' => '15em',
        'datatableFieldMobile' => '10em',
	    'datatableFieldSingle' => 'auto'
    ],

	'roles' => [
		'show' => [
			'any' => [
				'contacts'
			]
		]
	],

    'routePrefix' => 'contacts',

	'associableModelTypesFullClass' => [
		User::class,
		Client::class,
		Operator::class
	],
    
    'models' => [
        'contact' => [
            'table' => 'contacts__contacts',
            'class' => Contact::class,
	        'roles' => [
		        'show' => [
					'any' => [
						'contacts'
					]
		        ]
	        ],
            'controllers' => [
				'index' => ContactIndexController::class,
				'byModel' => ContactByModelController::class,
				'show' => ContactShowController::class,
				'edit' => ContactEditUpdateController::class,
                'createBy' => ContactCreateController::class,
                'storeBy' => ContactCreateController::class,
                'destroy' => ContactDestroyController::class
            ],
            'parametersFiles' => [
	            'create' => CreateContactFieldsetsParameters::class,
	            'edit' => CreateContactFieldsetsParameters::class,
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
                'index' => ContacttypeIndexController::class,
                'create' => ContacttypeController::class,
                'store' => ContacttypeController::class,
                'show' => ContacttypeController::class,
                'edit' => ContacttypeController::class,
                'update' => ContacttypeController::class,
                'destroy' => ContacttypeController::class
            ],
            'relationshipsManagerClasses' => [
                'show' => ContacttypeRelationshipManager::class
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