<?php

namespace IlBronza\Contacts\Http\Parameters\TableFields;

use IlBronza\Datatables\Providers\FieldsGroupParametersFile;

class ContactTableRelatedFieldsParameters extends FieldsGroupParametersFile
{
	static function getFieldsGroup() : array
	{
		return [
            'translationPrefix' => 'contacts',
            'fields' => [
                // 'contactable' => 'relations.BelongsTo',
                // 'contacttype' => 'relations.BelongsTo',
                'sorting_index' => 'flat',
                'contact' => 'flat',

                'mySelfDelete' => 'links.delete'
            ]
        ];
	}
}