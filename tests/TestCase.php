<?php
namespace IlBronza\Contacts\Tests;

use IlBronza\Contacts\ContactsServiceProvider;
use IlBronza\Contacts\Tests\Fixtures\Models\Owner;
use IlBronza\Contacts\Tests\Fixtures\Models\OtherOwner;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            \Spatie\Permission\PermissionServiceProvider::class,
            \IlBronza\AccountManager\AccountManagerServiceProvider::class,
            \IlBronza\Ukn\UknServiceProvider::class,
            \IlBronza\Menu\MenuServiceProvider::class,
            \IlBronza\UikitTemplate\UikitTemplateServiceProvider::class,
            \IlBronza\Buttons\ButtonsServiceProvider::class,
            \IlBronza\FormField\FormFieldServiceProvider::class,
            \IlBronza\Form\FormServiceProvider::class,
            \IlBronza\Datatables\DatatablesServiceProvider::class,
            \IlBronza\CRUD\CRUDServiceProvider::class,
            ContactsServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('app.key', 'base64:' . base64_encode(str_repeat('c', 32)));
        $app['config']->set('app.locale', 'en');
        $app['config']->set('app.template', \IlBronza\UikitTemplate\UikitTemplate::class);
        $app['config']->set('menu.serviceProviders', []);
        $app['config']->set('menu.childrenPerColumn', 8);
        $app['config']->set('activitylog.enabled', false);
        $app['config']->set('crud.broadcast.enabled', false);
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true]);
        $app['config']->set('cache.default', 'array');
        $app['config']->set('session.driver', 'array');
        $app['config']->set('contacts.associableModelTypesFullClass', [Owner::class, OtherOwner::class]);
        $app['config']->set('view.compiled', sys_get_temp_dir() . '/contacts-test-views');
        if (! is_dir($app['config']->get('view.compiled')))
            mkdir($app['config']->get('view.compiled'), 0777, true);
    }

    protected function setUp(): void
    {
        parent::setUp();
        Relation::morphMap(['owner' => Owner::class, 'other-owner' => OtherOwner::class]);
        // Each application has its own in-memory connection; no RefreshDatabase under php-wasm.
        foreach (glob(__DIR__ . '/../database/migrations/*.php') as $migration)
            (require $migration)->up();

        Schema::create('owners', function (Blueprint $table) {
            $table->id();
            $table->boolean('can_view')->default(true);
            $table->boolean('can_update')->default(true);
        });
    }

    protected function signIn(array $roles = ['contacts']): void
    {
        $user = new \IlBronza\AccountManager\Models\User;
        $user->forceFill(['id' => 1, 'name' => 'Tester', 'email' => 'tester@example.com', 'active' => true]);
        $user->setRelation('roles', collect(array_map(static fn ($role) => new \Spatie\Permission\Models\Role(['name' => $role, 'guard_name' => 'web']), $roles)));
        $this->actingAs($user);
    }
}
