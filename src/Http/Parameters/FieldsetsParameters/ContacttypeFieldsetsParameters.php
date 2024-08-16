<?php

namespace IlBronza\Contacts\Http\Parameters\FieldsetsParameters;

use IlBronza\Form\Helpers\FieldsetsProvider\FieldsetParametersFile;

class ContacttypeFieldsetsParameters extends FieldsetParametersFile
{
    public function _getFieldsetsParameters() : array
    {
        return [
            'base' => [
                'translationPrefix' => 'contacts',
                'fields' => [
					'name' => ['text' => 'string|required|max:191'],
					'href_string' => ['text' => 'string|nullable|max:255'],
					'fa_icon' => ['text' => 'string|nullable|max:32'],
                ],
                'width' => ['1-2@m']
            ]
        ];
    }
}
