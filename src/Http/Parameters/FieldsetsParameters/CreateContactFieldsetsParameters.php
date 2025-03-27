<?php

namespace IlBronza\Contacts\Http\Parameters\FieldsetsParameters;

use IlBronza\Contacts\Models\Contacttype;
use IlBronza\Form\Helpers\FieldsetsProvider\FieldsetParametersFile;

class CreateContactFieldsetsParameters extends FieldsetParametersFile
{
    public function _getFieldsetsParameters() : array
    {
        $contacttype = Contacttype::getProjectClassName()::make();

        return [
            'base' => [
                'translationPrefix' => 'contacts::contacts',
                'fields' => [
                    'contact' => [
                        'text' => 'string|required|max:191'
                    ],
                    'contacttype_slug' => [
                        'type' => 'select',
                        'multiple' => false,
                        'relation' => 'contacttype',
                        'rules' => "string|required|exists:{$contacttype->getTable()},{$contacttype->getKeyName()}"
                    ],
                    'sorting_index' => ['number' => 'numeric|nullable|min:0|max:65535'],
                ],
                'width' => ['1-2@m']
            ]
        ];
    }
}
