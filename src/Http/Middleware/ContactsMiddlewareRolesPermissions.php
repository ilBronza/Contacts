<?php

namespace IlBronza\Contacts\Http\Middleware;

use IlBronza\CRUD\Middleware\CRUDBasePackageMiddlewareRolesPermissions;

/**
 * Resolves allowed roles for Contacts routes from config (contacts.defaultRoles / contacts.routeRoles).
 */
class ContactsMiddlewareRolesPermissions extends CRUDBasePackageMiddlewareRolesPermissions
{
    protected string $configPackageName = 'contacts';
}
