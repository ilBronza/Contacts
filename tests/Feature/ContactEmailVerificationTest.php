<?php

namespace IlBronza\Contacts\Tests\Feature;

use Closure;
use IlBronza\Contacts\Contracts\EmailExistenceVerifier;
use IlBronza\Contacts\Helpers\ContactEmailVerificationHelper;
use IlBronza\Contacts\Models\Contact;
use IlBronza\Contacts\Tests\Fixtures\Models\Owner;
use IlBronza\Contacts\Tests\TestCase;
use Illuminate\Support\Facades\DB;
use LogicException;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;

class ContactEmailVerificationTest extends TestCase
{
    #[DataProvider('providerResults')]
    public function test_only_a_positive_mailbox_result_sets_the_flag(?bool $result): void
    {
        $contact = Owner::create()->addContact('Person@EXAMPLE.COM', 'email');
        $helper = $this->helper($result);

        $this->assertSame($result, $helper->verify($contact));
        $this->assertSame($result === true, $contact->existence_verified);
        $this->assertSame($result === true, $contact->fresh()->existence_verified);
    }

    public static function providerResults(): array
    {
        return ['confirmed' => [true], 'invalid' => [false], 'inconclusive' => [null]];
    }

    public function test_read_only_check_sends_the_normalized_email_without_saving(): void
    {
        $contact = new Contact;
        $contact->contacttype_slug = 'email';
        $contact->contact = ' Person@EXAMPLE.COM ';
        $verifier = Mockery::mock(EmailExistenceVerifier::class);
        $verifier->shouldReceive('verify')->once()->with('Person@example.com')->andReturn(true);

        $this->assertTrue((new ContactEmailVerificationHelper($verifier))->isValid($contact));
        $this->assertFalse($contact->existence_verified);
        $this->assertSame(' Person@EXAMPLE.COM ', $contact->contact);
        $this->assertSame(0, Contact::count());
    }

    public function test_custom_email_types_are_supported(): void
    {
        config(['contacts.contactTypes.pec' => ['normalizer' => 'email', 'rules' => ['email']]]);
        $contact = Owner::create()->addContact('person@example.com', 'pec');
        $this->assertTrue($this->helper(true)->verify($contact));
        $this->assertTrue($contact->fresh()->existence_verified);
    }

    #[DataProvider('invalidContacts')]
    public function test_invalid_or_non_email_contacts_do_not_call_the_provider(?string $type, ?string $address): void
    {
        $contact = new Contact;
        $contact->contacttype_slug = $type;
        $contact->contact = $address;
        $verifier = Mockery::mock(EmailExistenceVerifier::class);
        $verifier->shouldNotReceive('verify');

        $this->assertFalse((new ContactEmailVerificationHelper($verifier))->isValid($contact));
    }

    public static function invalidContacts(): array
    {
        return [
            ['phone', '+390212345678'],
            ['other', 'person@example.com'],
            [null, 'person@example.com'],
            ['email', null],
            ['email', 'bad..address@example.com'],
        ];
    }

    #[DataProvider('providerResults')]
    public function test_rechecking_updates_a_previous_success(?bool $result): void
    {
        $contact = Owner::create()->addContact('person@example.com', 'email');
        $this->helper(true)->verify($contact);

        $this->assertSame($result, $this->helper($result)->verify($contact));
        $this->assertSame($result === true, $contact->fresh()->existence_verified);
    }

    public function test_legacy_invalid_email_can_be_marked_unverified_without_resaving_it(): void
    {
        $contact = Owner::create()->addContact('person@example.com', 'email');
        DB::table($contact->getTable())->where('id', $contact->id)->update([
            'contact' => 'legacy invalid email', 'existence_verified' => true,
        ]);
        $contact->refresh();
        $verifier = Mockery::mock(EmailExistenceVerifier::class);
        $verifier->shouldNotReceive('verify');

        $this->assertFalse((new ContactEmailVerificationHelper($verifier))->verify($contact));
        $this->assertFalse($contact->fresh()->existence_verified);
        $this->assertSame('legacy invalid email', $contact->fresh()->contact);
    }

    public function test_provider_failure_preserves_the_previous_verification(): void
    {
        $contact = Owner::create()->addContact('person@example.com', 'email');
        $this->helper(true)->verify($contact);
        $helper = $this->helper(false, fn () => throw new RuntimeException('Provider unavailable'));

        try {
            $helper->verify($contact);
            $this->fail('Expected a provider failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Provider unavailable', $exception->getMessage());
        }

        $this->assertTrue($contact->existence_verified);
        $this->assertTrue($contact->fresh()->existence_verified);
    }

    public function test_verification_does_not_save_unrelated_pending_changes(): void
    {
        $contact = Owner::create()->addContact('person@example.com', 'email');
        $contact->sorting_index = 42;

        $this->assertTrue($this->helper(true)->verify($contact));
        $this->assertNull($contact->fresh()->sorting_index);
        $this->assertTrue($contact->isDirty('sorting_index'));
        $this->assertFalse($contact->isDirty('existence_verified'));
    }

    public function test_unsaved_or_changed_addresses_cannot_be_marked_verified(): void
    {
        $existing = Owner::create()->addContact('person@example.com', 'email');
        $existing->contact = 'new@example.com';
        $verifier = Mockery::mock(EmailExistenceVerifier::class);
        $verifier->shouldNotReceive('verify');
        $helper = new ContactEmailVerificationHelper($verifier);

        foreach ([new Contact, $existing] as $contact) {
            try {
                $helper->verify($contact);
                $this->fail('Expected an unsaved address to be rejected.');
            } catch (LogicException $exception) {
                $this->assertStringContainsString('Save the contact', $exception->getMessage());
            }
        }

        $this->assertFalse($existing->fresh()->existence_verified);
    }

    #[DataProvider('changedEmails')]
    public function test_a_concurrent_email_change_is_not_marked_verified_or_overwritten(string $newEmail): void
    {
        $contact = Owner::create()->addContact('person@example.com', 'email');
        $helper = $this->helper(true, function () use ($contact, $newEmail) {
            $other = $contact->fresh();
            $other->contact = $newEmail;
            $other->save();
        });

        try {
            $helper->verify($contact);
            $this->fail('Expected a concurrent change to be detected.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('changed or was deleted', $exception->getMessage());
        }

        $this->assertSame($newEmail, $contact->fresh()->contact);
        $this->assertFalse($contact->fresh()->existence_verified);
    }

    public static function changedEmails(): array
    {
        return [['new@example.com'], ['Person@example.com']];
    }

    public function test_a_contact_deleted_during_verification_is_not_marked_verified(): void
    {
        $contact = Owner::create()->addContact('person@example.com', 'email');
        $helper = $this->helper(true, fn () => $contact->fresh()->delete());

        try {
            $helper->verify($contact);
            $this->fail('Expected a concurrent deletion to be detected.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('changed or was deleted', $exception->getMessage());
        }

        $this->assertTrue($contact->fresh()->trashed());
        $this->assertFalse($contact->fresh()->existence_verified);
    }

    private function helper(?bool $result, ?Closure $onVerify = null): ContactEmailVerificationHelper
    {
        $verifier = Mockery::mock(EmailExistenceVerifier::class);
        $verifier->shouldReceive('verify')->andReturnUsing(function () use ($result, $onVerify) {
            if ($onVerify)
                $onVerify();

            return $result;
        });

        return new ContactEmailVerificationHelper($verifier);
    }
}
