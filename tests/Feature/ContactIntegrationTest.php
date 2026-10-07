<?php
namespace IlBronza\Contacts\Tests\Feature;

use IlBronza\Contacts\Http\Controllers\Contacttypes\ContacttypeIndexController;
use IlBronza\Contacts\Models\Contacttype;
use IlBronza\Contacts\Support\ContactableResolver;
use IlBronza\Contacts\Tests\Fixtures\Models\Owner;
use IlBronza\Contacts\Tests\Fixtures\Models\PolicyOwner;
use IlBronza\Contacts\Tests\TestCase;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;

class ContactIntegrationTest extends TestCase
{
    public function test_guest_cannot_store_contact(): void
    {
        $this->postJson('/contact-management/contacts/store-by/owner/1', [])->assertUnauthorized();
    }

    public function test_route_specific_roles_override_defaults(): void
    {
        $owner = Owner::create();
        $this->signIn(['contacts']);
        config(['contacts.routeRoles.contactscontacts.store' => ['contact-editor']]);
        $this->postJson('/contact-management/contacts/store-by/owner/' . $owner->id, [])->assertForbidden();
    }

    public function test_read_endpoint_renders_eager_loaded_contacts(): void
    {
        $this->signIn();
        $owner = Owner::create();
        $owner->addContact('person@example.com', 'email');
        $this->get('/contact-management/contacts/by-model/owner/' . $owner->id)
            ->assertOk()->assertSee('person@example.com');
    }

    public function test_missing_contacts_and_types_return_404(): void
    {
        $this->signIn();
        foreach (['contacts/999', 'contacts/999/edit', 'types/missing', 'types/missing/edit'] as $path)
            $this->getJson('/contact-management/' . $path)->assertNotFound();
        $this->putJson('/contact-management/contacts/999', [])->assertNotFound();
        $this->putJson('/contact-management/types/missing', [])->assertNotFound();
    }

    public function test_type_index_includes_counts(): void
    {
        Owner::create()->addContact('person@example.com', 'email');
        $types = app(ContacttypeIndexController::class)->getIndexElements();
        $this->assertSame(1, $types->sole()->contacts_count);
    }

    public function test_type_form_rejects_unsafe_icons_and_schemes(): void
    {
        $this->signIn();
        $this->postJson('/contact-management/types', [
            'name' => 'Unsafe', 'href_string' => 'javascript', 'fa_icon' => 'x" onclick="bad',
        ])->assertUnprocessable()->assertJsonValidationErrors(['href_string', 'fa_icon']);
        $this->assertSame(0, Contacttype::count());
    }

    public function test_type_form_accepts_supported_icon_and_scheme(): void
    {
        $this->signIn();
        $this->post('/contact-management/types', [
            'name' => 'Work email', 'href_string' => 'mailto', 'fa_icon' => 'envelope',
        ])->assertRedirect();
        $type = Contacttype::sole();
        $this->assertSame('mailto', $type->href_string);
        $this->assertSame('envelope', $type->fa_icon);
    }

    public function test_policy_authorizes_standard_eloquent_owners(): void
    {
        $package = dirname((new \ReflectionClass(\Spatie\Permission\PermissionServiceProvider::class))->getFileName(), 2);
        (require $package . '/database/migrations/create_permission_tables.php.stub')->up();
        $this->signIn();
        Relation::morphMap(['policy-owner' => PolicyOwner::class]);
        config(['contacts.associableModelTypesFullClass' => [PolicyOwner::class, 'Missing\\OptionalModel']]);
        $owner = PolicyOwner::create();
        Gate::define('view', fn ($user, $model) => true);
        Gate::define('update', fn ($user, $model) => true);
        $this->assertTrue($owner->is(ContactableResolver::resolve('policy-owner', (string) $owner->id, true)));
        Gate::define('update', fn ($user, $model) => false);
        $this->expectException(AuthorizationException::class);
        ContactableResolver::resolve('policy-owner', (string) $owner->id, true);
    }

    public function test_unconfigured_morph_map_alias_is_rejected(): void
    {
        $this->signIn();
        Relation::morphMap(['policy-owner' => PolicyOwner::class]);
        $owner = PolicyOwner::create();
        $this->getJson('/contact-management/contacts/create-by/policy-owner/' . $owner->id)->assertNotFound();
    }
}
