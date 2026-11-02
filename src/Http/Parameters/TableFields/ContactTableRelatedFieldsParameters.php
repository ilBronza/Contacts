<?php

namespace IlBronza\Contacts\Http\Parameters\TableFields;

use IlBronza\Datatables\Providers\FieldsGroupParametersFile;

class ContactTableRelatedFieldsParameters extends FieldsGroupParametersFile
{
	static function getFieldsGroup() : array
	{
		return [
            'translationPrefix' => 'contacts::fields',
            'fields' => [
                'contacttype.name' => 'flat',
                'contact' => 'flat',
                'existence_verified' => [
                    'type' => 'boolean',
                    'width' => '8em',
                ],

                'mySelfDelete' => 'links.delete'
            ]
        ];
	}
}