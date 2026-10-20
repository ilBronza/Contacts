<?php
namespace IlBronza\Contacts\Tests\Feature;

use IlBronza\Contacts\Http\Controllers\Contacts\ContactCreateController;
use IlBronza\Contacts\Models\Contact;
use IlBronza\Contacts\Tests\Fixtures\Models\Owner;
use IlBronza\Contacts\Tests\TestCase;
use Illuminate\Support\Facades\Route;
use Illuminate\Routing\Middleware\ValidateSignature;

class ContactAuthorizationTest extends TestCase
{
    public function test_management_routes_require_roles_and_confirmation_requires_a_signature(): void
    {
        $routes = collect(Route::getRoutes())->filter(fn ($route) => str_starts_with($route->uri(), 'contact-management/'));
        $this->assertNotEmpty($routes);
        foreach ($routes as $route) {
            if ($route->getName() === config('contacts.routePrefix') . '.email.verify') {
                $this->assertContains(ValidateSignature::class, $route->middleware());
                $this->assertContains('throttle:30,1', $route->middleware());
                $this->assertNotContains('auth', $route->middleware());
                continue;
            }

            $this->assertContains('contacts.roles', $route->middleware(), $route->uri());
        }
    }

    public function test_authenticated_user_without_package_role_cannot_create_or_store(): void
    {
        $this->signIn([]);
        $owner = Owner::create();
        $this->getJson('/contact-management/contacts/create-by/owner/' . $owner->id)->assertForbidden();
        $this->postJson('/contact-management/contacts/store-by/owner/' . $owner->id, [])->assertForbidden();
        $this->assertSame(0, Contact::count());
    }

    public function test_parent_update_permission_is_required_for_both_creation_routes(): void
    {
        $this->signIn();
        $owner = Owner::create(['can_update' => false]);
        $this->getJson('/contact-management/contacts/create-by/owner/' . $owner->id)->assertForbidden();
        $this->postJson('/contact-management/contacts/store-by/owner/' . $owner->id, [])->assertForbidden();
    }

    public function test_parent_visibility_is_required_for_reading_and_writing(): void
    {
        $this->signIn();
        $owner = Owner::create(['can_view' => false]);
        $this->getJson('/contact-management/contacts/by-model/owner/' . $owner->id)->assertForbidden();
        $this->postJson('/contact-management/contacts/store-by/owner/' . $owner->id, [])->assertForbidden();
    }

    public function test_unknown_types_and_missing_owners_return_404(): void
    {
        $this->signIn();
        foreach (['unknown/1', 'owner/999'] as $target) {
            $this->getJson('/contact-management/contacts/by-model/' . $target)->assertNotFound();
            $this->getJson('/contact-management/contacts/create-by/' . $target)->assertNotFound();
            $this->postJson('/contact-management/contacts/store-by/' . $target, [])->assertNotFound();
        }
    }

    public function test_allowed_alias_and_full_class_resolve_to_canonical_parent(): void
    {
        $this->signIn();
        $owner = Owner::create();
        foreach (['owner', Owner::class] as $type) {
            $controller = app(ContactCreateController::class);
            $controller->setStoreByProperties($type, (string) $owner->id);
            $this->assertTrue($owner->is($controller->getParentModel()));
            $this->assertSame(['contactable_type' => 'owner', 'contactable_id' => $owner->id], $controller->getModelDefaultPolimorphicParameters());
        }
    }

    public function test_authorized_store_normalizes_and_persists_contact(): void
    {
        $this->signIn();
        $owner = Owner::create();
        $type = \IlBronza\Contacts\Models\Contacttype::create(['name' => 'email']);
        $this->post('/contact-management/contacts/store-by/owner/' . $owner->id, [
            'contact' => ' Name@EXAMPLE.COM ', 'contacttype_slug' => $type->slug,
        ])->assertRedirect('/owners/' . $owner->id);
        $this->assertSame('Name@example.com', $owner->contacts()->sole()->contact);
    }

    public function test_authorized_store_rejects_invalid_email_without_writing(): void
    {
        $this->signIn();
        $owner = Owner::create();
        $type = \IlBronza\Contacts\Models\Contacttype::create(['name' => 'email']);
        $this->postJson('/contact-management/contacts/store-by/owner/' . $owner->id, [
            'contact' => 'invalid', 'contacttype_slug' => $type->slug,
        ])->assertUnprocessable()->assertJsonValidationErrors('contact');
        $this->assertSame(0, Contact::count());
    }
}
