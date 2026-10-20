<?php

namespace IlBronza\Contacts\Tests\Feature;

use IlBronza\Contacts\Contracts\EmailExistenceVerifier;
use IlBronza\Contacts\Helpers\ContactEmailVerificationHelper;
use IlBronza\Contacts\Support\VerifaliaEmailExistenceVerifier;
use IlBronza\Contacts\Tests\Fixtures\Models\Owner;
use IlBronza\Contacts\Tests\TestCase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;

class VerifaliaEmailExistenceVerifierTest extends TestCase
{
    private const JOB_ID = '54375b5b-a399-4bc4-abd8-b8c02e109e53';
    private const ENDPOINT = 'https://api.verifalia.com/v2.7/email-validations';

    protected function setUp(): void
    {
        parent::setUp();
        config(['contacts.emailVerification.verifalia' => [
            'username' => 'test-user', 'password' => 'test-password',
        ]]);
        Http::preventStrayRequests();
    }

    public function test_default_provider_checks_the_single_mailbox_and_persists_the_result(): void
    {
        Http::fake([self::ENDPOINT . '*' => Http::response($this->completed())]);
        $contact = Owner::create()->addContact(' Person@EXAMPLE.COM ', 'email');

        $this->assertInstanceOf(VerifaliaEmailExistenceVerifier::class, app(EmailExistenceVerifier::class));
        $this->assertTrue(app(ContactEmailVerificationHelper::class)->verify($contact));
        $this->assertTrue($contact->fresh()->existence_verified);
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request) => $request->method() === 'POST'
            && $request->url() === self::ENDPOINT . '?waitTime=30000'
            && $request['quality'] === 'Standard'
            && $request['entries'] === [['inputData' => 'Person@example.com']]
            && $request->hasHeader('Authorization', 'Basic ' . base64_encode('test-user:test-password')));
    }

    #[DataProvider('resultExamples')]
    public function test_mailbox_results_are_mapped_conservatively(string $status, string $classification, ?bool $expected): void
    {
        Http::fake([self::ENDPOINT . '*' => Http::response($this->completed($status, $classification))]);
        $this->assertSame($expected, app(EmailExistenceVerifier::class)->verify('Person@example.com'));
    }

    public static function resultExamples(): array
    {
        return [
            ['Success', 'Deliverable', true],
            ['MailboxDoesNotExist', 'Undeliverable', false],
            ['DomainHasNullMx', 'Undeliverable', false],
            ['ServerIsCatchAll', 'Risky', null],
            ['ServerIsCatchAll', 'Deliverable', null],
            ['MailboxTemporarilyUnavailable', 'Unknown', null],
            ['MailboxValidationTimeout', 'Unknown', null],
            ['OverrideMatch', 'Deliverable', null],
            ['NewProviderStatus', 'Deliverable', null],
        ];
    }

    public function test_incomplete_checks_resume_the_same_job_in_a_new_helper_instance(): void
    {
        Http::fakeSequence()->push($this->pending(), 202)->push($this->completed());
        $contact = Owner::create()->addContact('Person@example.com', 'email');

        $this->assertNull(app(ContactEmailVerificationHelper::class)->verify($contact));
        $this->assertFalse($contact->fresh()->existence_verified);
        $this->assertTrue(app(ContactEmailVerificationHelper::class)->verify($contact));
        $this->assertTrue($contact->fresh()->existence_verified);
        Http::assertSentCount(2);
        Http::assertSent(fn (Request $request) => $request->method() === 'GET'
            && $request->url() === self::ENDPOINT . '/' . self::JOB_ID . '?waitTime=30000');
    }

    public function test_polling_failure_preserves_the_job_for_retry(): void
    {
        Http::fakeSequence()->push($this->pending(), 202)->push([], 503)->push($this->completed());
        $this->assertNull(app(EmailExistenceVerifier::class)->verify('Person@example.com'));

        try {
            app(EmailExistenceVerifier::class)->verify('Person@example.com');
            $this->fail('Expected the provider error.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('HTTP 503', $exception->getMessage());
        }

        $this->assertTrue(app(EmailExistenceVerifier::class)->verify('Person@example.com'));
        $this->assertCount(1, Http::recorded(fn (Request $request) => $request->method() === 'POST'));
        $this->assertCount(2, Http::recorded(fn (Request $request) => $request->method() === 'GET'));
    }

    public function test_pending_job_is_scoped_to_the_exact_address(): void
    {
        Http::fakeSequence()->push($this->pending(), 202)
            ->push($this->completed('Success', 'Deliverable', 'person@example.com'));

        $this->assertNull(app(EmailExistenceVerifier::class)->verify('Person@example.com'));
        $this->assertTrue(app(EmailExistenceVerifier::class)->verify('person@example.com'));
        $this->assertCount(2, Http::recorded(fn (Request $request) => $request->method() === 'POST'));
    }

    public function test_a_missing_pending_job_can_be_replaced_on_an_explicit_retry(): void
    {
        Http::fakeSequence()->push($this->pending(), 202)->push([], 404)->push($this->completed());
        $this->assertNull(app(EmailExistenceVerifier::class)->verify('Person@example.com'));

        try {
            app(EmailExistenceVerifier::class)->verify('Person@example.com');
            $this->fail('Expected the missing job to be reported.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('HTTP 404', $exception->getMessage());
        }

        $this->assertTrue(app(EmailExistenceVerifier::class)->verify('Person@example.com'));
        $this->assertCount(2, Http::recorded(fn (Request $request) => $request->method() === 'POST'));
    }

    public function test_missing_credentials_fail_before_any_request(): void
    {
        config(['contacts.emailVerification.verifalia.password' => null]);
        Http::fake();

        try {
            app(EmailExistenceVerifier::class)->verify('Person@example.com');
            $this->fail('Expected missing credentials to be reported.');
        } catch (LogicException $exception) {
            $this->assertStringContainsString('CONTACTS_VERIFALIA_PASSWORD', $exception->getMessage());
        }

        Http::assertNothingSent();
    }

    #[DataProvider('errorCodes')]
    public function test_api_errors_do_not_mark_the_contact_invalid_or_leak_response_data(int $code): void
    {
        $contact = Owner::create()->addContact('Person@example.com', 'email');
        $contact->existence_verified = true;
        $contact->save();
        Http::fake([self::ENDPOINT . '*' => Http::response(['error' => 'test-password Person@example.com'], $code)]);

        try {
            app(ContactEmailVerificationHelper::class)->verify($contact);
            $this->fail('Expected a provider error.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('HTTP ' . $code, $exception->getMessage());
            $this->assertStringNotContainsString('test-password', $exception->getMessage());
            $this->assertStringNotContainsString('Person@example.com', $exception->getMessage());
        }

        $this->assertTrue($contact->fresh()->existence_verified);
        Http::assertSentCount(1);
    }

    public static function errorCodes(): array
    {
        return [[302], [401], [402], [403], [429], [500]];
    }

    public function test_connection_errors_are_reported_without_retrying_or_leaking_details(): void
    {
        Http::fake(fn () => throw new ConnectionException('secret response details'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Verifalia could not be reached.');
        app(EmailExistenceVerifier::class)->verify('Person@example.com');
    }

    public function test_malformed_responses_cannot_mark_an_address_verified(): void
    {
        $wrongEmail = $this->completed('Success', 'Deliverable', 'different@example.com');
        $noEntries = $this->completed();
        unset($noEntries['entries']);
        $badId = $this->completed();
        $badId['overview']['id'] = 'https://untrusted.example/';

        foreach ([[], $wrongEmail, $noEntries, $badId] as $data) {
            Http::fake([self::ENDPOINT . '*' => Http::response($data)]);

            try {
                app(EmailExistenceVerifier::class)->verify('Person@example.com');
                $this->fail('Expected the malformed response to be rejected.');
            } catch (RuntimeException $exception) {
                $this->assertStringContainsString('invalid', $exception->getMessage());
            }
        }
    }

    private function pending(): array
    {
        return ['overview' => ['id' => self::JOB_ID, 'status' => 'InProgress']];
    }

    private function completed(string $status = 'Success', string $classification = 'Deliverable', string $email = 'Person@example.com'): array
    {
        return [
            'overview' => ['id' => self::JOB_ID, 'status' => 'Completed'],
            'entries' => ['data' => [[
                'inputData' => $email, 'status' => $status, 'classification' => $classification,
            ]]],
        ];
    }
}
