<?php
namespace IlBronza\Contacts\Tests\Feature;

use IlBronza\Contacts\Models\Contact;
use IlBronza\Contacts\Models\Contacttype;
use IlBronza\Contacts\Tests\Fixtures\Models\Owner;
use IlBronza\Contacts\Tests\Fixtures\Models\OtherOwner;
use IlBronza\Contacts\Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ContactPersistenceTest extends TestCase
{
    public function test_shared_address_does_not_move_between_owners(): void
    {
        $first = Owner::create();
        $second = Owner::create();
        $a = $first->addContact('shared@example.com', 'email');
        $b = $second->addContact('shared@example.com', 'email');
        $this->assertNotSame($a->id, $b->id);
        $this->assertSame(1, $first->contacts()->count());
        $this->assertSame(1, $second->contacts()->count());
    }

    public function test_same_id_in_different_morph_types_is_isolated(): void
    {
        $owner = Owner::create();
        $other = OtherOwner::findOrFail($owner->id);
        $first = $owner->addContact('shared@example.com', 'email');
        $second = $other->addContact('shared@example.com', 'email');
        $this->assertNotSame($first->id, $second->id);
        $this->assertSame('owner', $first->fresh()->contactable_type);
        $this->assertSame('other-owner', $second->fresh()->contactable_type);
    }

    public function test_duplicate_normalized_contact_is_reused_and_loaded_collection_is_refreshed(): void
    {
        $owner = Owner::create();
        $this->assertCount(0, $owner->getContacts());
        $first = $owner->addContact(' Person@EXAMPLE.COM ', 'email');
        $second = $owner->addContact('Person@example.com', 'email');
        $this->assertSame($first->id, $second->id);
        $this->assertSame('Person@example.com', $second->contact);
        $this->assertCount(1, $owner->getContacts());
    }

    public function test_same_owner_can_use_same_text_for_different_types(): void
    {
        $owner = Owner::create();
        $first = $owner->addContact('+390212345678', 'mobile');
        $second = $owner->addContact('+390212345678', 'phone');
        $this->assertNotSame($first->id, $second->id);
    }

    public function test_invalid_contact_rolls_back_new_contact_type(): void
    {
        try {
            Owner::create()->addContact('not-an-email', 'email');
            $this->fail('Expected a validation error.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('contact', $e->errors());
        }
        $this->assertSame(0, Contact::count());
        $this->assertSame(0, Contacttype::count());
    }

    public function test_unsaved_owner_is_rejected_without_writes(): void
    {
        $this->expectException(\LogicException::class);
        (new Owner)->addContact('person@example.com', 'email');
    }

    public function test_deleted_contact_is_not_silently_restored(): void
    {
        $owner = Owner::create();
        $first = $owner->addContact('person@example.com', 'email');
        $first->delete();
        $second = $owner->addContact('person@example.com', 'email');
        $this->assertNotSame($first->id, $second->id);
        $this->assertTrue($first->fresh()->trashed());
        $this->assertSame(1, $owner->contacts()->count());
    }

    public function test_direct_save_and_quiet_save_validate_changes(): void
    {
        $contact = Owner::create()->addContact('person@example.com', 'email');
        $contact->contact = 'invalid';
        foreach (['save', 'saveQuietly'] as $method) {
            try {
                $contact->$method();
                $this->fail('Expected validation for ' . $method);
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('contact', $e->errors());
            }
        }
        $this->assertSame('person@example.com', $contact->fresh()->contact);
    }

    public function test_unrelated_updates_preserve_legacy_values(): void
    {
        $contact = Owner::create()->addContact('person@example.com', 'email');
        DB::table($contact->getTable())->where('id', $contact->id)->update(['contact' => 'legacy invalid email']);
        $contact->refresh();
        $contact->sorting_index = 2;
        $contact->save();
        $this->assertSame('legacy invalid email', $contact->fresh()->contact);
    }

    public function test_type_relation_is_remembered_and_nullable_type_is_safe(): void
    {
        $contact = Owner::create()->addContact('person@example.com', 'email')->fresh();
        $this->assertFalse($contact->relationLoaded('contacttype'));
        $type = $contact->getContacttype();
        $this->assertTrue($contact->relationLoaded('contacttype'));
        $this->assertSame($type, $contact->getContacttype());
        $empty = new Contact;
        $this->assertNull($empty->getContacttype());
        $this->assertNull($empty->getIconString());
        $this->assertNull($empty->getContactable());
    }

    public function test_changing_type_invalidates_loaded_relation_and_applies_new_rules(): void
    {
        $owner = Owner::create();
        $contact = $owner->addContact('person@example.com', 'email');
        $contact->getContacttype();
        $phoneType = $owner->addContact('+390212345678', 'phone')->contacttype_slug;
        $contact->contacttype_slug = $phoneType;
        $contact->contact = '+39 (02) 1234-5678';
        $contact->save();
        $this->assertSame('phone', $contact->getContacttype()->slug);
        $this->assertSame('+390212345678', $contact->fresh()->contact);
    }
}
