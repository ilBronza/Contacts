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
        // Capture the entire candidate before validating; never accept a truncated domain.
        $pattern = '~[^\s<>()\[\],;:"\\\\]+@[^\s<>()\[\],;:"\\\\]+~u';
        
        preg_match_all($pattern, $wannabeEmails, $matches);

        $emails = array_map(static fn (string $email) => rtrim(trim($email, "'"), '.!?'), $matches[0] ?? []);

        return array_values(array_filter($emails, static fn (string $email) => static::isValidEmail($email)));
    }

    static public function isValidPhoneNumber(string $phoneNumber) : bool
    {
        return preg_match('/\A\+?[0-9]{7,15}(?:X[0-9]{1,10})?\z/', $phoneNumber) === 1;
    }

    static public function extractPhoneNumber(string $wannabePhone) : ? string
    {
        $phone = trim($wannabePhone);

        if (! preg_match('/\A([+0-9().\s-]+?)(?:\s*(?:x|ext\.?)\s*([0-9]{1,10}))?\z/i', $phone, $matches))
            return null;

        $result = preg_replace('/[().\s-]/', '', $matches[1]);

        if (isset($matches[2]))
            $result .= 'X' . $matches[2];

        if(static::isValidPhoneNumber($result))
            return $result;

        return null;
    }

    public static function normalizeContact(string $value, ?string $type = null) : string
    {
        $value = trim($value);
        $normalizer = config('contacts.contactTypes.' . $type . '.normalizer', 'trim');

        if ($normalizer === 'email' && ($position = strrpos($value, '@')) !== false)
            return substr($value, 0, $position + 1) . strtolower(substr($value, $position + 1));

        if ($normalizer === 'phone')
            return static::extractPhoneNumber($value) ?? $value;

        return $value;
    }

    public static function normalizeAndValidateContact(?string $value, ?string $type = null) : string
    {
        $value = static::normalizeContact((string) $value, $type);
        $rules = config('contacts.contactTypes.' . $type . '.rules', []);
        $rules = is_string($rules) ? explode('|', $rules) : $rules;

        Validator::make(['contact' => $value], [
            'contact' => array_merge(['bail', 'required', 'string', 'max:191'], $rules),
        ])->validate();

        return $value;
    }


    public function manageMenuButtons()
    {
        if(! $menu = app('menu'))
            return;

        $settings = $menu->provideSettingsButton();

        $contactsButton = $menu->createButton([
            'name' => 'contactsmanager',
            'icon' => 'address-book',
            'text' => 'contacts::contacts.contacts'
        ]);

        $settings->addChild($contactsButton);

        $contactsButton->addChild(
            $menu->createButton([
                'name' => 'contacts.index',
                'icon' => 'list',
                'text' => 'contacts::contacts.index',
                'href' => IbRouter::route($this, 'contacts.index')
            ])
        );

        $contactsButton->addChild(
            $menu->createButton([
                'name' => 'contacttypes.index',
                'icon' => 'gear',
                'text' => 'contacts::contacts.contacttypes',
                'href' => IbRouter::route($this, 'contacttypes.index')
            ])
        );
    }
}
