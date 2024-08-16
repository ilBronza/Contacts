<?php

namespace IlBronza\Contacts\Helpers;

use IlBronza\Buttons\Button;
use Illuminate\Database\Eloquent\Model;

class ContactCreatorHelper
{
    static function getCreateContactButtonByModel(Model $model) : Button
    {
        return Button::create([
            'href' => app('contacts')->route('contacts.createBy', ['model' => $model->getMorphClass(), 'key' => $model->getKey()]),
            'text' => 'contacts::contacts.createNew',
            'icon' => 'plus'
        ])->setSmall()->setPrimary();
    }
}
