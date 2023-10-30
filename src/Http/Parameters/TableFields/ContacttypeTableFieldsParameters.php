<?php

namespace IlBronza\Contacts\Http\Parameters\TableFields;

use IlBronza\Datatables\Providers\FieldsGroupParametersFile;

class ContacttypeTableFieldsParameters extends FieldsGroupParametersFile
{
	static function getFieldsGroup() : array
	{
		return [
            'translationPrefix' => 'contacts',
            'fields' => [
                'mySelfEdit' => 'links.edit',
                'mySelfSee' => 'links.see',
                'name' => 'flat',

                'contacts_count' => 'flat',

                'mySelfDelete' => 'links.delete'
            ]
        ];
	}
}