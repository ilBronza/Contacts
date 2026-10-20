<?php

namespace IlBronza\Contacts\Support;

use IlBronza\Contacts\Contracts\EmailExistenceVerifier;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use LogicException;
use RuntimeException;

class VerifaliaEmailExistenceVerifier implements EmailExistenceVerifier
{
    private const ENDPOINT = 'https://api.verifalia.com/v2.7/email-validations';

    public function verify(string $email): ?bool
    {
        $username = config('contacts.emailVerification.verifalia.username');
        $password = config('contacts.emailVerification.verifalia.password');

        if (! is_string($username) || trim($username) === '' || ! is_string($password) || $password === '')
            throw new LogicException('Configure CONTACTS_VERIFALIA_USERNAME and CONTACTS_VERIFALIA_PASSWORD before verifying email addresses.');

        // Keep unfinished jobs across requests so retries do not spend another credit.
        $key = 'contacts:verifalia:' . hash('sha256', $username . "\0" . $email);

        return Cache::lock($key . ':lock', 45)->block(5, function () use ($email, $username, $password, $key) {
            $jobId = Cache::get($key);
            $request = Http::withBasicAuth($username, $password)->acceptJson()
                ->connectTimeout(5)->timeout(35)->withoutRedirecting();

            try {
                $response = $jobId
                    ? $request->get(self::ENDPOINT . '/' . rawurlencode($jobId), ['waitTime' => 30000])
                    : $request->post(self::ENDPOINT . '?waitTime=30000', [
                        'entries' => [['inputData' => $email]],
                        'quality' => 'Standard',
                    ]);
            } catch (ConnectionException) {
                // Do not include credentials, email addresses or response bodies in errors.
                throw new RuntimeException('Verifalia could not be reached. No email verification result was saved.');
            }

            if (! in_array($response->status(), [200, 202], true)) {
                if ($jobId && in_array($response->status(), [404, 410], true))
                    Cache::forget($key);

                throw new RuntimeException('Verifalia verification failed (HTTP ' . $response->status() . '). Check credentials, permissions and available credits.');
            }

            $data = $response->json();
            $id = data_get($data, 'overview.id');
            $status = data_get($data, 'overview.status');

            if (! is_string($id) || ! preg_match('/\A[0-9a-f]{8}(?:-[0-9a-f]{4}){3}-[0-9a-f]{12}\z/i', $id)
                || ($jobId && $id !== $jobId))
                throw new RuntimeException('Verifalia returned an invalid job identifier.');

            if ($status === 'InProgress') {
                Cache::put($key, $id, now()->addDay());

                return null;
            }

            if (in_array($status, ['Deleted', 'Expired'], true)) {
                Cache::forget($key);

                throw new RuntimeException('The Verifalia job is no longer available. Retry to start a new verification.');
            }

            if ($status !== 'Completed')
                throw new RuntimeException('Verifalia returned an unexpected job state.');

            $entries = data_get($data, 'entries.data');

            if (! is_array($entries) || count($entries) !== 1
                || ($entries[0]['inputData'] ?? null) !== $email
                || ! is_string($entries[0]['status'] ?? null))
                throw new RuntimeException('Verifalia returned an invalid result for the requested email address.');

            Cache::forget($key);
            $entry = $entries[0];

            // Classifications are customizable: never accept Deliverable alone.
            if ($entry['status'] === 'Success' && ($entry['classification'] ?? null) === 'Deliverable')
                return true;

            if (in_array($entry['status'], [
                'MailboxDoesNotExist', 'DomainDoesNotExist', 'DomainHasNullMx',
                'DomainIsMisconfigured', 'ServerDoesNotSupportInternationalMailboxes',
                'UnacceptableDomainLiteral',
            ], true))
                return false;

            // Catch-all, greylisting, timeouts, overrides and future codes are inconclusive.
            return null;
        });
    }
}
