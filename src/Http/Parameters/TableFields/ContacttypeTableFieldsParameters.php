<?php

namespace IlBronza\Contacts\Http\Parameters\TableFields;

use IlBronza\Datatables\Providers\FieldsGroupParametersFile;

class ContacttypeTableFieldsParameters extends FieldsGroupParametersFile
{
	static function getFieldsGroup() : array
	{
		return [
            'translationPrefix' => 'contacts::fields',
            'fields' => [
                'mySelfEdit' => 'links.edit',
                'mySelfSee' => 'links.see',
				'name' => 'flat',
				'href_string' => 'flat',
				'fa_icon' => 'flat',
				'mySelfIcon.fa_icon' => 'icon',

                'contacts_count' => 'flat',

                'mySelfDelete' => 'links.delete'
            ]
        ];
	}
}