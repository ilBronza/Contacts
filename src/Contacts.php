<?php

namespace IlBronza\Contacts;

use IlBronza\CRUD\Providers\RouterProvider\IbRouter;
use IlBronza\CRUD\Providers\RouterProvider\RoutedObjectInterface;
use IlBronza\CRUD\Traits\IlBronzaPackages\IlBronzaPackagesTrait;
use Illuminate\Support\Facades\Validator;

class Contacts implements RoutedObjectInterface
{
    use IlBronzaPackagesTrait;

    static $packageConfigPrefix = 'contacts';

    static public function isValidEmail(string $wannabeEmail) : bool
    {
        $validator = Validator::make(
            ['email' => $wannabeEmail],
            ['email' => 'required|email']
        );

        return $validator->passes();
    }

    static public function extractEmails(string $wannabeEmails) : array|false
    {
        $pattern = '/[a-z0-9_\-\+\.]+@[a-z0-9\-]+\.([a-z]{2,4})(?:\.[a-z]{2})?/i';
        
        preg_match_all($pattern, $wannabeEmails, $matches);

        return $matches[0];
    }

    static public function isValidPhoneNumber(string $phoneNumber) : bool
    {
        return preg_match('/^\+?[0-9]{9,}(X[0-9]{1,})?$/', $phoneNumber);
    }

    static public function extractPhoneNumber(string $wannabePhone) : ? string
    {
        $result = preg_replace('/[^0-9X+]/', '', $wannabePhone);

        if(static::isValidPhoneNumber($result))
            return $result;

        return null;
    }


    public function manageMenuButtons()
    {
        if(! $menu = app('menu'))
            return;

        $settings = $menu->provideSettingsButton();

        $contactsButton = $menu->createButton([
            'name' => 'contactsmanager',
            'icon' => 'user-gear',
            'text' => 'contacts::contacts.contacts'
        ]);

        $settings->addChild($contactsButton);

        $contactsButton->addChild(
            $menu->createButton([
                'name' => 'contacts.index',
                'icon' => 'users',
                'text' => 'contacts::contacts.index',
                'href' => IbRouter::route($this, 'contacts.index')
            ])
        );

        $contactsButton->addChild(
            $menu->createButton([
                'name' => 'contacttypes.index',
                'icon' => 'users',
                'text' => 'contacts::contacts.contacttypes',
                'href' => IbRouter::route($this, 'contacttypes.index')
            ])
        );
    }
}