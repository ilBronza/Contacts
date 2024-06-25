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
                'mySelfTargetType.contactable' => 'translatedClassBasename',
                'contactable' => 'relations.BelongsTo',
                'contacttype' => 'relations.BelongsTo',
                'contact' => 'flat',

                'mySelfDelete' => 'links.delete'
            ]
        ];
	}
}