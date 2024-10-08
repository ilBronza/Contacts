<?php

namespace IlBronza\Contacts\Http\Parameters\TableFields;

use IlBronza\Datatables\Providers\FieldsGroupParametersFile;

class ContactTableFieldsParameters extends FieldsGroupParametersFile
{
	static function getFieldsGroup() : array
	{
		return [
            'translationPrefix' => 'contacts::fields',
            'fields' => [
                'mySelfEdit' => 'links.edit',
                'mySelfSee' => 'links.see',
                'contactable' => [
                    'translationPrefix' => 'contacts::fields',
                    'type' => 'links.see',
                    'textParameter' => 'name',
	                'width' => '250px'
                ],

                'contacttype' => 'relations.BelongsTo',
                'contact' => 'flat',

                'mySelfDelete' => 'links.delete'
            ]
        ];
	}
}