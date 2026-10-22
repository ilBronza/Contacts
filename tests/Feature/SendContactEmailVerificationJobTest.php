<?php

namespace IlBronza\Contacts\Tests\Feature;

use IlBronza\Contacts\Helpers\ContactEmailConfirmationHelper;
use IlBronza\Contacts\Jobs\SendContactEmailVerification;
use IlBronza\Contacts\Mail\ContactEmailVerificationMail;
use IlBronza\Contacts\Tests\Fixtures\Models\Owner;
use IlBronza\Contacts\Tests\TestCase;
use Illuminate\Mail\PendingMail;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;

class SendContactEmailVerificationJobTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        config(['queue.default' => 'sync']);
    }

    public function test_link_validity_starts_when_the_worker_executes_and_duplicate_jobs_do_not_resend(): void
    {
        $this->freezeTime();
        $contact = Owner::create()->addContact('person@example.com', 'email');
        $payload = serialize(new SendContactEmailVerification($contact));
        $this->assertStringNotContainsString('person@example.com', $payload);
        $this->assertNull($contact->fresh()->email_verification_token_hash);
        $this->travel(8)->days();

        app()->call([unserialize($payload), 'handle']);
        app()->call([unserialize($payload), 'handle']);

        Mail::assertSentCount(1);
        $mail = Mail::sent(ContactEmailVerificationMail::class)->sole();
        parse_str(parse_url($mail->verificationUrl, PHP_URL_QUERY), $query);
        $this->assertSame(now()->addDays(7)->timestamp, (int) $query['expires']);
        $this->assertSame((int) $query['expires'], $contact->fresh()->email_verification_expires_at->timestamp);
        $this->get($mail->verificationUrl)->assertOk();
        $this->assertTrue($contact->fresh()->existence_verified);
        $this->assertNull($contact->fresh()->email_verification_expires_at);
    }

    #[DataProvider('obsoleteJobs')]
    public function test_jobs_skip_contacts_that_are_no_longer_eligible(string $change): void
    {
        $contact = Owner::create()->addContact('person@example.com', 'email');
        $payload = serialize(new SendContactEmailVerification($contact));

        match ($change) {
            'address' => $contact->forceFill(['contact' => 'changed@example.com'])->save(),
            'type' => $contact->newQuery()->whereKey($contact->getKey())->update(['contacttype_slug' => null]),
            'verified' => $contact->forceFill(['existence_verified' => true])->save(),
            'deleted' => $contact->delete(),
            'pending' => app(ContactEmailConfirmationHelper::class)->createVerificationUrl($contact),
        };

        app()->call([unserialize($payload), 'handle']);

        Mail::assertNothingSent();
    }

    public static function obsoleteJobs(): array
    {
        return [['address'], ['type'], ['verified'], ['deleted'], ['pending']];
    }

    public function test_hard_deleted_contacts_are_discarded_by_the_queue(): void
    {
        $contact = Owner::create()->addContact('person@example.com', 'email');
        $job = new SendContactEmailVerification($contact);
        $contact->forceDelete();

        Bus::dispatchSync($job);

        Mail::assertNothingSent();
    }

    public function test_jobs_wait_for_commit_and_are_discarded_on_rollback(): void
    {
        DB::beginTransaction();
        $contact = Owner::create()->addContact('committed@example.com', 'email');
        Bus::dispatch(new SendContactEmailVerification($contact));
        Mail::assertNothingSent();
        DB::commit();
        Mail::assertSentCount(1);

        DB::beginTransaction();
        $rolledBack = Owner::create()->addContact('rollback@example.com', 'email');
        Bus::dispatch(new SendContactEmailVerification($rolledBack));
        DB::rollBack();
        Mail::assertSentCount(1);
    }

    public function test_in_progress_mail_send_prevents_a_second_sender_from_replacing_its_link(): void
    {
        $contact = Owner::create()->addContact('person@example.com', 'email');
        $pending = Mockery::mock(PendingMail::class);
        $pending->shouldReceive('send')->once()->andReturnUsing(function () use ($contact) {
            $this->assertSame(0, $contact->getConnection()->transactionLevel());
            $this->assertFalse(app(ContactEmailConfirmationHelper::class)->sendIfNeeded($contact->fresh()));
        });
        Mail::shouldReceive('to')->once()->with('person@example.com')->andReturn($pending);

        $this->assertTrue(app(ContactEmailConfirmationHelper::class)->sendIfNeeded($contact));
    }

    public function test_failed_mail_can_be_retried_without_leaving_a_pending_token(): void
    {
        $contact = Owner::create()->addContact('person@example.com', 'email');
        $payload = serialize(new SendContactEmailVerification($contact));
        $mailFake = Mail::getFacadeRoot();
        $pending = Mockery::mock(PendingMail::class);
        $pending->shouldReceive('send')->once()->andThrow(new RuntimeException('Mail failed'));
        Mail::shouldReceive('to')->once()->andReturn($pending);
        try {
            app()->call([unserialize($payload), 'handle']);
            $this->fail('Expected the queue to receive the transport failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Mail failed', $exception->getMessage());
        }

        $this->assertNull($contact->fresh()->email_verification_token_hash);
        $this->assertNull($contact->fresh()->email_verification_expires_at);
        $this->assertFalse($contact->fresh()->existence_verified);
        Mail::swap($mailFake);
        app()->call([unserialize($payload), 'handle']);
        Mail::assertSentCount(1);
    }

    public function test_failed_older_send_does_not_clear_a_newer_link(): void
    {
        $contact = Owner::create()->addContact('person@example.com', 'email');
        $url = null;
        $pending = Mockery::mock(PendingMail::class);
        $pending->shouldReceive('send')->once()->andReturnUsing(function () use ($contact, &$url) {
            $url = app(ContactEmailConfirmationHelper::class)->createVerificationUrl($contact->fresh());
            throw new RuntimeException('Older mail failed');
        });
        Mail::shouldReceive('to')->once()->andReturn($pending);

        try {
            app(ContactEmailConfirmationHelper::class)->sendIfNeeded($contact);
            $this->fail('Expected the transport failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Older mail failed', $exception->getMessage());
        }

        $this->assertNotNull($contact->fresh()->email_verification_expires_at);
        $this->get($url)->assertOk();
    }
}
