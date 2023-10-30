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
                ],
                'width' => ['1-2@m']
            ]
        ];
    }
}
