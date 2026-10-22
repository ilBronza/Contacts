<?php

namespace IlBronza\Contacts\Tests\Feature;

use IlBronza\Contacts\Helpers\ContactEmailConfirmationHelper;
use IlBronza\Contacts\Helpers\ContactEmailVerificationHelper;
use IlBronza\Contacts\Jobs\SendContactEmailVerification;
use IlBronza\Contacts\Mail\ContactEmailVerificationMail;
use IlBronza\Contacts\Models\Contact;
use IlBronza\Contacts\Tests\Fixtures\Models\Owner;
use IlBronza\Contacts\Tests\Fixtures\Models\ScopedContact;
use IlBronza\Contacts\Tests\TestCase;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Mail\PendingMail;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;

class SendEmailVerificationsCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Http::fake();
        Http::preventStrayRequests();
        config(['contacts.emailVerification.verifalia' => ['username' => null, 'password' => null]]);
    }

    public function test_default_batch_is_ten_and_subsequent_runs_move_to_remaining_contacts(): void
    {
        $owner = Owner::create();
        for ($i = 0; $i < 12; $i++)
            $owner->addContact('person' . $i . '@example.com', 'email');

        $this->artisan('contacts:send-email-verifications')
            ->expectsOutput('Email inviate: 10. Saltati: 0. Errori: 0.')->assertExitCode(0);
        Mail::assertSentCount(10);
        $this->assertSame(0, Contact::where('existence_verified', true)->count());

        $this->artisan('contacts:send-email-verifications')
            ->expectsOutput('Email inviate: 2. Saltati: 0. Errori: 0.')->assertExitCode(0);
        $this->artisan('contacts:send-email-verifications')
            ->expectsOutput('Email inviate: 0. Saltati: 0. Errori: 0.')->assertExitCode(0);
        Mail::assertSentCount(12);
        Http::assertNothingSent();
    }

    public function test_single_contact_and_custom_limit_are_supported(): void
    {
        $first = Owner::create()->addContact('shared@example.com', 'email');
        $second = Owner::create()->addContact('shared@example.com', 'email');
        $third = Owner::create()->addContact('third@example.com', 'email');

        $this->artisan('contacts:send-email-verifications', ['contact' => $second->getKey()])->assertExitCode(0);
        $this->assertNull($first->fresh()->email_verification_token_hash);
        $this->get(Mail::sent(ContactEmailVerificationMail::class)->sole()->verificationUrl)->assertOk();
        $this->assertTrue($second->fresh()->existence_verified);
        $this->assertFalse($first->fresh()->existence_verified);

        $this->artisan('contacts:send-email-verifications', ['--limit' => 1])->assertExitCode(0);
        Mail::assertSentCount(2);
        $this->assertNotNull($first->fresh()->email_verification_token_hash);
        $this->assertNull($third->fresh()->email_verification_token_hash);
        $this->artisan('contacts:send-email-verifications', ['contact' => $second->getKey()])
            ->expectsOutput('Email inviate: 0. Saltati: 0. Errori: 0.')->assertExitCode(0);
    }

    #[DataProvider('invalidLimits')]
    public function test_invalid_or_large_limits_are_rejected(string $limit): void
    {
        Owner::create()->addContact('person@example.com', 'email');
        $this->artisan('contacts:send-email-verifications', ['--limit' => $limit])->assertExitCode(2);
        Mail::assertNothingSent();
    }

    public static function invalidLimits(): array
    {
        return [['0'], ['-1'], ['26'], ['1.5'], ['many'], ['']];
    }

    public function test_only_valid_unverified_email_types_without_live_links_are_selected(): void
    {
        $this->freezeTime();
        config(['contacts.contactTypes.pec' => ['normalizer' => 'email', 'rules' => ['email']]]);
        $owner = Owner::create();
        $invalid = $owner->addContact('legacy@example.com', 'email');
        $invalid->newQuery()->whereKey($invalid->getKey())->update(['contact' => 'invalid']);
        $verified = $owner->addContact('verified@example.com', 'email');
        $verified->forceFill(['existence_verified' => true])->save();
        $owner->addContact('+390212345678', 'phone');
        $pending = $owner->addContact('pending@example.com', 'email');
        $pendingUrl = app(ContactEmailVerificationHelper::class)->createVerificationUrl($pending);
        $expired = $owner->addContact('expired@example.com', 'email');
        config(['contacts.emailVerification.email.expires_days' => 1]);
        $expiredUrl = app(ContactEmailVerificationHelper::class)->createVerificationUrl($expired);
        $owner->addContact('pec@example.com', 'pec');
        $deleted = $owner->addContact('deleted@example.com', 'email');
        $deleted->delete();
        $this->travel(2)->days();
        config(['contacts.emailVerification.email.expires_days' => 7]);

        $this->artisan('contacts:send-email-verifications')
            ->expectsOutput('Email inviate: 2. Saltati: 1. Errori: 0.')->assertExitCode(0);
        Mail::assertSentCount(2);
        Mail::assertSent(ContactEmailVerificationMail::class, fn ($mail) => $mail->hasTo('pec@example.com'));
        Mail::assertSent(ContactEmailVerificationMail::class, fn ($mail) => $mail->hasTo('expired@example.com'));
        $this->get($pendingUrl)->assertOk();
        $this->get($expiredUrl)->assertForbidden();
        $this->assertFalse($expired->fresh()->existence_verified);
    }

    public function test_legacy_tokens_without_a_known_expiry_are_preserved(): void
    {
        $contact = Owner::create()->addContact('person@example.com', 'email');
        $url = app(ContactEmailVerificationHelper::class)->createVerificationUrl($contact);
        $contact->newQuery()->whereKey($contact->getKey())->update(['email_verification_expires_at' => null]);

        $this->artisan('contacts:send-email-verifications', ['contact' => $contact->getKey()])->assertExitCode(0);

        Mail::assertNothingSent();
        $this->get($url)->assertOk();
    }

    public function test_missing_contact_is_reported(): void
    {
        $this->artisan('contacts:send-email-verifications', ['contact' => 999])->assertExitCode(1);
        Mail::assertNothingSent();
    }

    public function test_failure_is_reported_without_exceeding_the_limit_and_other_contacts_continue(): void
    {
        $owner = Owner::create();
        $failed = $owner->addContact('failed@example.com', 'email');
        $owner->addContact('sent@example.com', 'email');
        $untouched = $owner->addContact('untouched@example.com', 'email');
        $pendingFailure = Mockery::mock(PendingMail::class);
        $pendingFailure->shouldReceive('send')->once()->andThrow(new RuntimeException('Transport unavailable'));
        $pendingSuccess = Mockery::mock(PendingMail::class);
        $pendingSuccess->shouldReceive('send')->once();
        Mail::shouldReceive('to')->once()->with('failed@example.com')->andReturn($pendingFailure);
        Mail::shouldReceive('to')->once()->with('sent@example.com')->andReturn($pendingSuccess);
        Log::shouldReceive('error')->once()->with(
            'Contacts: invio o accodamento email di verifica fallito.',
            Mockery::on(fn (array $context) => $context['contact_id'] === $failed->getKey()
                && $context['operation'] === 'send' && $context['exception'] instanceof RuntimeException),
        );

        $this->artisan('contacts:send-email-verifications', ['--limit' => 2])
            ->expectsOutput('Email inviate: 1. Saltati: 0. Errori: 1.')->assertExitCode(1);
        $this->assertNull($failed->fresh()->email_verification_token_hash);
        $this->assertNull($failed->fresh()->email_verification_expires_at);
        $this->assertNull($untouched->fresh()->email_verification_token_hash);
    }

    #[DataProvider('diagnosticModes')]
    public function test_errors_are_always_visible_even_when_the_logger_is_broken(bool $verbose, bool $brokenLogger): void
    {
        $this->withoutMockingConsoleOutput();
        $owner = Owner::create();
        $owner->addContact('first@example.com', 'email');
        $owner->addContact('second@example.com', 'email');
        $failure = new RuntimeException('Transport <smtp> unavailable');
        $helper = Mockery::mock(ContactEmailConfirmationHelper::class)->makePartial();
        $helper->shouldReceive('sendIfNeeded')->twice()->andThrow($failure);
        $this->app->instance(ContactEmailConfirmationHelper::class, $helper);

        $log = Log::shouldReceive('error')->twice()->with(
            'Contacts: invio o accodamento email di verifica fallito.',
            Mockery::on(fn (array $context) => $context['exception'] === $failure && $context['operation'] === 'send'),
        );
        if ($brokenLogger)
            $log->andThrow(new RuntimeException('Log file not writable'));
        else
            $log->andReturnNull();

        $options = ['--limit' => 2];
        if ($verbose)
            $options['--verbose'] = true;
        $this->assertSame(1, Artisan::call('contacts:send-email-verifications', $options));
        $output = Artisan::output();

        $this->assertStringContainsString('RuntimeException: Transport <smtp> unavailable', $output);
        $this->assertStringContainsString('Email inviate: 0. Saltati: 0. Errori: 2.', $output);
        if ($verbose) {
            $this->assertStringContainsString('Canale log: ' . config('logging.default'), $output);
            $this->assertStringContainsString(config_path('logging.php'), $output);
        } else {
            $this->assertStringNotContainsString('Canale log:', $output);
        }
        if ($brokenLogger) {
            $this->assertStringContainsString('Impossibile registrare l\'errore nei log', $output);
            $this->assertStringContainsString('RuntimeException: Log file not writable', $output);
        }
        Mail::assertNothingSent();
    }

    public static function diagnosticModes(): array
    {
        return [[false, false], [true, false], [false, true], [true, true]];
    }

    public function test_failures_are_written_to_the_configured_log_even_for_exceptions_excluded_from_reporting(): void
    {
        $contact = Owner::create()->addContact('person@example.com', 'email');
        $failure = ValidationException::withMessages(['email' => 'Email verification failed']);
        $this->assertFalse(app(ExceptionHandler::class)->shouldReport($failure));
        $helper = Mockery::mock(ContactEmailConfirmationHelper::class)->makePartial();
        $helper->shouldReceive('sendIfNeeded')->once()->andThrow($failure);
        $this->app->instance(ContactEmailConfirmationHelper::class, $helper);
        $path = tempnam(sys_get_temp_dir(), 'contacts-command-log-');
        config([
            'logging.default' => 'contacts_test_file',
            'logging.channels.contacts_test_file' => ['driver' => 'single', 'path' => $path, 'level' => 'error'],
        ]);

        try {
            $this->artisan('contacts:send-email-verifications', ['contact' => $contact->getKey()])->assertExitCode(1);

            $contents = file_get_contents($path);
            $this->assertStringContainsString('Contacts: invio o accodamento email di verifica fallito.', $contents);
            $this->assertStringContainsString('"contact_id":' . $contact->getKey(), $contents);
            $this->assertStringContainsString('"operation":"send"', $contents);
            $this->assertStringContainsString('Email verification failed', $contents);
            $this->assertStringContainsString('ValidationException', $contents);
        } finally {
            Log::forgetChannel('contacts_test_file');
            unlink($path);
        }
    }

    public function test_queue_mode_dispatches_a_small_batch_using_the_host_connection_and_queue(): void
    {
        Queue::fake();
        config(['queue.connections.mailtests.driver' => 'database']);
        $owner = Owner::create();
        for ($i = 0; $i < 3; $i++)
            $owner->addContact('person' . $i . '@example.com', 'email');

        $this->artisan('contacts:send-email-verifications', [
            '--queue' => true, '--connection' => 'mailtests', '--queue-name' => 'confirmations', '--limit' => 2,
        ])->expectsOutput('Job accodati: 2. Saltati: 0. Errori: 0.')->assertExitCode(0);

        Queue::assertPushed(SendContactEmailVerification::class, 2);
        Queue::assertPushedOn('confirmations', SendContactEmailVerification::class,
            fn ($job) => $job->connection === 'mailtests' && $job->afterCommit === true);
        Mail::assertNothingSent();
        $this->assertSame(0, Contact::whereNotNull('email_verification_token_hash')->count());
    }

    public function test_sync_queue_connection_is_explicitly_reported_and_executes_the_job(): void
    {
        config(['queue.default' => 'sync']);
        Owner::create()->addContact('person@example.com', 'email');

        $this->artisan('contacts:send-email-verifications', ['--queue' => true])
            ->expectsOutput('La connessione queue usa sync: i job vengono eseguiti subito.')->assertExitCode(0);
        Mail::assertSentCount(1);
    }

    public function test_queue_options_require_queue_mode_and_a_valid_connection(): void
    {
        Queue::fake();
        $this->artisan('contacts:send-email-verifications', ['--queue-name' => 'mail'])->assertExitCode(2);
        $this->artisan('contacts:send-email-verifications', ['--connection' => 'database'])->assertExitCode(2);
        $this->artisan('contacts:send-email-verifications', ['--queue' => true, '--connection' => 'missing'])->assertExitCode(2);
        config(['queue.connections.discard.driver' => 'null']);
        $this->artisan('contacts:send-email-verifications', ['--queue' => true, '--connection' => 'discard'])->assertExitCode(2);
        Queue::assertNothingPushed();
        Mail::assertNothingSent();
    }

    public function test_host_model_override_and_its_global_scopes_are_used(): void
    {
        $owner = Owner::create();
        $owner->addContact('hidden@example.com', 'email');
        $owner->addContact('visible@example.com', 'email');
        config(['contacts.models.contact.class' => ScopedContact::class]);

        $this->artisan('contacts:send-email-verifications')->assertExitCode(0);

        Mail::assertSentCount(1);
        Mail::assertSent(ContactEmailVerificationMail::class, fn ($mail) => $mail->hasTo('visible@example.com'));
    }
}
