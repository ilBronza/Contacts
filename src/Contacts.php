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

        $button = $menu->provideButton([
                'text' => 'generals.settings',
                'name' => 'settings',
                'icon' => 'gear',
                'roles' => ['administrator']
            ]);

        $button->setFirst();

        $contactsButton = $menu->createButton([
            'name' => 'contactsmanager',
            'icon' => 'user-gear',
            'text' => 'contacts.contacts'
        ]);

        $contacttypes = $menu->createButton([
            'name' => 'contacttypes.index',
            'icon' => 'users',
            'text' => 'contacts.contacttypes',
            'href' => IbRouter::route($this, 'contacttypes.index')
        ]);

        // $rolesButton = $menu->createButton([
        //     'name' => 'roles.index',
        //     'text' => 'accountmanager.roles',
        //     'icon' => 'graduation-cap',
        //     'href' => IbRouter::route($this, 'roles.index'),
        //     'permissions' => ['roles.index']
        // ]);

        // $permissionsButton = $menu->createButton([
        //     'name' => 'permissions.index',
        //     'text' => 'accountmanager.permissions',
        //     'icon' => 'user-lock',
        //     'href' => IbRouter::route($this, 'permissions.index'),
        //     'permissions' => ['permissions.index']
        // ]);

        $button->addChild($contactsButton);

        $contactsButton->addChild($contacttypes);
        // $authButton->addChild($rolesButton);
        // $authButton->addChild($permissionsButton);


        // if(Auth::user())
        // {
        //     $account = $menu->provideButton([
        //         'name' => 'account',
        //         'image' => (($avatar = Auth::user()->getAvatarImage()) ? $avatar : null),
        //         'translatedText' => Auth::user()->getName(),
        //         'href' => IbRouter::route($this, 'users.show', [Auth::user()]),

        //         'children' => [
        //             [
        //                 'text' => 'accountmanager.edit',
        //                 'href' => IbRouter::route($this, 'accountmanager.account')
        //             ],
        //             [
        //                 'text' => 'accountmanager.editUserdata',
        //                 'href' => IbRouter::route($this, 'accountmanager.editUserdata')
        //             ],
        //             [
        //                 'text' => 'accountmanager.resetPassword',
        //                 'href' => route('password.request')
        //             ],
        //             [
        //                 'text' => 'accountmanager.logout',
        //                 'href' => IbRouter::route($this, 'accountmanager.logout')
        //             ]
        //         ]
        //     ]);

        //     $destra = $menu->provideMainRightBar();

        //     $menu->addToNavbar($account, $destra);

        //     $account->setFirst();
        // }
    }
}