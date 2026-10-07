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
					'href_string' => ['text' => 'string|nullable|in:' . implode(',', config('contacts.allowedHrefSchemes', ['mailto', 'tel', 'sms', 'http', 'https']))],
					'fa_icon' => ['text' => 'nullable|string|max:32|regex:/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/i'],
                ],
                'width' => ['1-2@m']
            ]
        ];
    }
}
