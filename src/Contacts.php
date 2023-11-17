<?php

namespace IlBronza\Contacts;

use IlBronza\CRUD\Providers\RouterProvider\IbRouter;
use IlBronza\CRUD\Providers\RouterProvider\RoutedObjectInterface;
use IlBronza\CRUD\Traits\IlBronzaPackages\IlBronzaPackagesTrait;

class Contacts implements RoutedObjectInterface
{
    use IlBronzaPackagesTrait;

    static $packageConfigPrefix = 'contacts';


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