<?php

namespace IlBronza\Contacts\Tests\Feature;

use IlBronza\Contacts\Helpers\ContactEmailVerificationHelper;
use IlBronza\Contacts\Mail\ContactEmailVerificationMail;
use IlBronza\Contacts\Models\Contact;
use IlBronza\Contacts\Tests\Fixtures\Models\Owner;
use IlBronza\Contacts\Tests\TestCase;
use Illuminate\Mail\PendingMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use LogicException;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;

class ContactEmailConfirmationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Http::fake();
        Http::preventStrayRequests();
        config(['contacts.emailVerification.verifalia' => ['username' => null, 'password' => null]]);
    }

    public function test_email_contains_a_working_link_valid_for_seven_days_without_using_verifalia(): void
    {
        app()->setLocale('it');
        $this->freezeTime();
        $contact = Owner::create()->addContact(' Person@EXAMPLE.COM ', 'email');
        $this->helper()->sendVerificationEmail($contact);

        $mail = Mail::sent(ContactEmailVerificationMail::class)->sole();
        $this->assertTrue($mail->hasTo('Person@example.com'));
        $this->assertSame(7, $mail->expiresInDays);
        $mail->assertSeeInHtml($mail->verificationUrl);
        $mail->assertSeeInHtml('7 giorni');
        $mail->assertSeeInText($mail->verificationUrl);
        $this->assertFalse($contact->fresh()->existence_verified);
        $this->assertNotNull($contact->fresh()->email_verification_token_hash);
        $this->assertArrayNotHasKey('email_verification_token_hash', $contact->fresh()->toArray());
        parse_str(parse_url($mail->verificationUrl, PHP_URL_QUERY), $query);
        $this->assertSame(now()->addDays(7)->timestamp, (int) $query['expires']);
        $this->assertSame((int) $query['expires'], $contact->fresh()->email_verification_expires_at->timestamp);

        $this->travel(6)->days();
        $this->get($mail->verificationUrl)->assertOk()->assertSee('Indirizzo email verificato')
            ->assertHeader('Referrer-Policy', 'no-referrer');
        $this->assertTrue($contact->fresh()->existence_verified);
        $this->assertNull($contact->fresh()->email_verification_token_hash);
        $this->assertNull($contact->fresh()->email_verification_expires_at);
        Http::assertNothingSent();
    }

    public function test_custom_email_link_can_be_generated_without_sending_and_is_single_use(): void
    {
        $contact = Owner::create()->addContact('person@example.com', 'email');
        $contact->sorting_index = 12;
        $url = $this->helper()->createVerificationUrl($contact);
        Mail::assertNothingSent();
        $this->assertFalse($contact->fresh()->existence_verified);
        $this->assertNull($contact->fresh()->sorting_index);
        $this->assertTrue($contact->isDirty('sorting_index'));

        $this->get($url)->assertOk();
        $this->get($url)->assertForbidden();
        $this->assertTrue($contact->fresh()->existence_verified);
    }

    public function test_configurable_expiration_is_enforced(): void
    {
        config(['contacts.emailVerification.email.expires_days' => 2]);
        $contact = Owner::create()->addContact('person@example.com', 'email');
        $url = $this->helper()->createVerificationUrl($contact);
        $this->travel(3)->days();

        $this->get($url)->assertForbidden();
        $this->assertFalse($contact->fresh()->existence_verified);
    }

    public function test_new_link_revokes_the_previous_one(): void
    {
        $contact = Owner::create()->addContact('person@example.com', 'email');
        $old = $this->helper()->createVerificationUrl($contact);
        $new = $this->helper()->createVerificationUrl($contact);

        $this->assertNotSame($old, $new);
        $this->get($old)->assertForbidden();
        $this->assertFalse($contact->fresh()->existence_verified);
        $this->get($new)->assertOk();
        $this->assertTrue($contact->fresh()->existence_verified);
    }

    public function test_modified_or_unsigned_links_cannot_verify_an_address(): void
    {
        $contact = Owner::create()->addContact('person@example.com', 'email');
        $url = $this->helper()->createVerificationUrl($contact);
        $unsigned = strtok($url, '?');

        $this->get($unsigned)->assertForbidden();
        $this->get($url . '&extra=changed')->assertForbidden();
        $this->assertFalse($contact->fresh()->existence_verified);
    }

    public function test_link_is_bound_to_one_contact_even_when_two_contacts_share_an_email(): void
    {
        $first = Owner::create()->addContact('person@example.com', 'email');
        $second = Owner::create()->addContact('person@example.com', 'email');
        $url = $this->helper()->createVerificationUrl($first);
        $this->helper()->createVerificationUrl($second);
        $token = basename(parse_url($url, PHP_URL_PATH));
        $wrongContact = URL::temporarySignedRoute(config('contacts.routePrefix') . '.email.verify', now()->addDay(), [
            'contact' => $second->id, 'token' => $token,
        ]);

        $this->get($wrongContact)->assertForbidden();
        $this->get($url)->assertOk();
        $this->assertTrue($first->fresh()->existence_verified);
        $this->assertFalse($second->fresh()->existence_verified);
    }

    #[DataProvider('saveMethods')]
    public function test_address_changes_revoke_the_link_even_if_the_old_address_is_restored(string $method): void
    {
        $contact = Owner::create()->addContact('person@example.com', 'email');
        $url = $this->helper()->createVerificationUrl($contact);
        $contact->contact = 'new@example.com';
        $contact->$method();
        $this->assertNull($contact->fresh()->email_verification_token_hash);
        $contact->contact = 'person@example.com';
        $contact->$method();

        $this->get($url)->assertForbidden();
        $this->assertFalse($contact->fresh()->existence_verified);
    }

    public static function saveMethods(): array
    {
        return [['save'], ['saveQuietly']];
    }

    public function test_raw_address_changes_cannot_reuse_the_old_link(): void
    {
        $contact = Owner::create()->addContact('person@example.com', 'email');
        $url = $this->helper()->createVerificationUrl($contact);
        DB::table($contact->getTable())->where('id', $contact->id)->update(['contact' => 'Person@example.com']);

        $this->get($url)->assertForbidden();
        $this->assertFalse($contact->fresh()->existence_verified);
    }

    public function test_type_changes_revoke_the_link(): void
    {
        $owner = Owner::create();
        $contact = $owner->addContact('person@example.com', 'email');
        $other = $owner->addContact('person@example.com', 'other');
        $url = $this->helper()->createVerificationUrl($contact);
        $contact->contacttype_slug = $other->contacttype_slug;
        $contact->saveQuietly();

        $this->assertNull($contact->fresh()->email_verification_token_hash);
        $this->assertNull($contact->fresh()->email_verification_expires_at);
        $this->get($url)->assertForbidden();
        $this->assertFalse($contact->fresh()->existence_verified);
    }

    public function test_unrelated_updates_and_equivalent_normalization_preserve_the_link(): void
    {
        $contact = Owner::create()->addContact('Person@example.com', 'email');
        $url = $this->helper()->createVerificationUrl($contact);
        $contact->contact = ' Person@EXAMPLE.COM ';
        $contact->sorting_index = 3;
        $contact->save();

        $this->get($url)->assertOk();
        $this->assertTrue($contact->fresh()->existence_verified);
        $this->assertSame(3, $contact->fresh()->sorting_index);
    }

    public function test_custom_email_types_can_be_confirmed(): void
    {
        config(['contacts.contactTypes.pec' => ['normalizer' => 'email', 'rules' => ['email']]]);
        $contact = Owner::create()->addContact('person@example.com', 'pec');

        $this->helper()->sendVerificationEmail($contact);
        $this->get(Mail::sent(ContactEmailVerificationMail::class)->sole()->verificationUrl)->assertOk();
        $this->assertTrue($contact->fresh()->existence_verified);
    }

    public function test_unsaved_dirty_and_non_email_contacts_cannot_receive_confirmation(): void
    {
        $owner = Owner::create();
        $dirty = $owner->addContact('person@example.com', 'email');
        $dirty->contact = 'changed@example.com';
        $nonEmail = $owner->addContact('+390212345678', 'phone');

        foreach ([new Contact, $dirty, $nonEmail] as $contact) {
            try {
                $this->helper()->sendVerificationEmail($contact);
                $this->fail('Expected an ineligible contact to be rejected.');
            } catch (LogicException $exception) {
                $this->assertNotEmpty($exception->getMessage());
            }
        }

        Mail::assertNothingSent();
        $this->assertNull($dirty->fresh()->email_verification_token_hash);
    }

    public function test_stale_contact_does_not_receive_a_link_for_a_new_address(): void
    {
        $contact = Owner::create()->addContact('person@example.com', 'email');
        $current = $contact->fresh();
        $current->contact = 'new@example.com';
        $current->save();

        try {
            $this->helper()->sendVerificationEmail($contact);
            $this->fail('Expected a stale model to be rejected.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('changed or was deleted', $exception->getMessage());
        }

        Mail::assertNothingSent();
        $this->assertNull($current->fresh()->email_verification_token_hash);
    }

    public function test_deleted_contact_cannot_be_confirmed(): void
    {
        $contact = Owner::create()->addContact('person@example.com', 'email');
        $url = $this->helper()->createVerificationUrl($contact);
        $contact->delete();

        $this->get($url)->assertNotFound();
        $this->assertFalse($contact->fresh()->existence_verified);
    }

    public function test_head_request_does_not_consume_the_link(): void
    {
        $contact = Owner::create()->addContact('person@example.com', 'email');
        $url = $this->helper()->createVerificationUrl($contact);

        $this->head($url)->assertStatus(405);
        $this->assertFalse($contact->fresh()->existence_verified);
        $this->get($url)->assertOk();
    }

    public function test_failed_mail_delivery_does_not_mark_the_contact_verified(): void
    {
        $contact = Owner::create()->addContact('person@example.com', 'email');
        $pending = Mockery::mock(PendingMail::class);
        $pending->shouldReceive('send')->once()->andThrow(new RuntimeException('Mail delivery failed'));
        Mail::shouldReceive('to')->once()->with('person@example.com')->andReturn($pending);

        try {
            $this->helper()->sendVerificationEmail($contact);
            $this->fail('Expected the mail failure to be reported.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Mail delivery failed', $exception->getMessage());
        }

        $this->assertFalse($contact->fresh()->existence_verified);
    }

    public function test_branded_email_embeds_its_local_logo_and_keeps_working_links_in_both_parts(): void
    {
        app()->setLocale('it');
        $logo = tempnam(sys_get_temp_dir(), 'contacts-logo-');
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aG7cAAAAASUVORK5CYII=');
        file_put_contents($logo, $png);
        config([
            'contacts.emailVerification.email.brand_name' => 'Example Company',
            'contacts.emailVerification.email.logo_path' => $logo,
            'mail.default' => 'array',
            'mail.mailers.array' => ['transport' => 'array'],
            'mail.from' => ['address' => 'sender@example.test', 'name' => 'Example Company'],
        ]);
        Mail::swap(new \Illuminate\Mail\MailManager($this->app));

        try {
            $contact = Owner::create()->addContact('person@example.com', 'email');
            $this->helper()->sendVerificationEmail($contact);
            $message = Mail::mailer('array')->getSymfonyTransport()->messages()->sole()->getOriginalMessage();
            $html = $message->getHtmlBody();
            $this->assertStringContainsString('alt="Example Company"', $html);
            $this->assertStringContainsString('src="cid:', $html);
            $this->assertStringContainsString('copia e incolla', $html);
            $this->assertStringContainsString('7 giorni', $html);
            $this->assertStringContainsString('Example Company', $message->getTextBody());
            $this->assertCount(1, $message->getAttachments());
            $this->assertSame('inline', $message->getAttachments()[0]->getDisposition());
            $this->assertSame($png, $message->getAttachments()[0]->getBody());

            preg_match_all('/href="([^"]+)"/', $html, $matches);
            $this->assertCount(2, $matches[1]);
            $this->assertSame($matches[1][0], $matches[1][1]);
            $url = html_entity_decode($matches[1][0], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $this->assertStringContainsString($url, $message->getTextBody());
            $this->get($url)->assertOk();
            $this->assertTrue($contact->fresh()->existence_verified);
            Http::assertNothingSent();
        } finally {
            unlink($logo);
        }
    }

    public function test_missing_logo_falls_back_to_the_escaped_application_name(): void
    {
        config([
            'contacts.emailVerification.email.brand_name' => null,
            'contacts.emailVerification.email.logo_path' => __DIR__ . '/missing-logo.png',
            'app.name' => 'Example <Company>',
        ]);
        $html = (new ContactEmailVerificationMail('https://example.test/confirmation', 7))->render();

        $this->assertStringContainsString('Example &lt;Company&gt;', $html);
        $this->assertStringNotContainsString('<img', $html);
        Http::assertNothingSent();
    }

    private function helper(): ContactEmailVerificationHelper
    {
        return app(ContactEmailVerificationHelper::class);
    }
}
