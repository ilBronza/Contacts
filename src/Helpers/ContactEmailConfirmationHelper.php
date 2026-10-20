<?php

namespace IlBronza\Contacts\Helpers;

use IlBronza\Contacts\Contacts;
use IlBronza\Contacts\Mail\ContactEmailVerificationMail;
use IlBronza\Contacts\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use LogicException;
use RuntimeException;

class ContactEmailConfirmationHelper
{
    public function send(Contact $contact): void
    {
        $url = $this->createVerificationUrl($contact);

        Mail::to(Contacts::normalizeContact((string) $contact->getContact(), 'email'))
            ->send(new ContactEmailVerificationMail($url, $this->expiresInDays()));
    }

    /** Create a new one-use link, revoking any previously issued link. */
    public function createVerificationUrl(Contact $contact): string
    {
        if (! $contact->exists || $contact->isDirty(['contact', 'contacttype_slug']))
            throw new LogicException('Save the contact and its email address before requesting confirmation.');

        if (! $this->isEmailContact($contact))
            throw new LogicException('Only contacts with a valid email address can receive a confirmation link.');

        $token = bin2hex(random_bytes(32));
        $url = URL::temporarySignedRoute(
            config('contacts.routePrefix') . '.email.verify',
            now()->addDays($this->expiresInDays()),
            ['contact' => $contact->getKey(), 'token' => $token],
        );

        $hash = $contact->getConnection()->transaction(function () use ($contact, $token) {
            $current = $contact->newQuery()->whereKey($contact->getKey())->lockForUpdate()->first();

            if (! $current
                || $current->getRawOriginal('contact') !== $contact->getRawOriginal('contact')
                || $current->getRawOriginal('contacttype_slug') !== $contact->getRawOriginal('contacttype_slug'))
                throw new RuntimeException('The contact changed or was deleted. Reload it before requesting confirmation.');

            $hash = $this->tokenHash($current, $token);
            $current->newQuery()->whereKey($current->getKey())->update(['email_verification_token_hash' => $hash]);

            return $hash;
        });

        $contact->email_verification_token_hash = $hash;
        $contact->syncOriginalAttribute('email_verification_token_hash');

        return $url;
    }

    public function confirm(Request $request): Contact
    {
        abort_unless($request->isMethod('GET'), 405);
        abort_unless(URL::hasValidSignature($request), 403);
        $token = $request->route('token');
        abort_unless(is_string($token) && preg_match('/\A[a-f0-9]{64}\z/', $token), 403);

        $class = Contact::getProjectClassName();
        $model = new $class;

        return $model->getConnection()->transaction(function () use ($model, $request, $token) {
            $contact = $model->newQuery()->whereKey($request->route('contact'))->lockForUpdate()->firstOrFail();

            abort_unless($this->isEmailContact($contact)
                && hash_equals((string) $contact->email_verification_token_hash, $this->tokenHash($contact, $token)), 403);

            $contact->newQuery()->whereKey($contact->getKey())->update([
                'existence_verified' => true,
                'email_verification_token_hash' => null,
            ]);

            return $contact->refresh();
        });
    }

    private function tokenHash(Contact $contact, string $token): string
    {
        // Bind the token to the exact stored address and type, even for raw SQL edits.
        return hash('sha256', $token . "\0" . $contact->getRawOriginal('contact') . "\0" . $contact->getRawOriginal('contacttype_slug'));
    }

    private function isEmailContact(Contact $contact): bool
    {
        $type = $contact->contacttype_slug;

        return ($type === 'email' || config('contacts.contactTypes.' . $type . '.normalizer') === 'email')
            && Contacts::isValidEmail(Contacts::normalizeContact((string) $contact->getContact(), 'email'));
    }

    private function expiresInDays(): int
    {
        $days = (int) config('contacts.emailVerification.email.expires_days', 7);

        if ($days < 1)
            throw new LogicException('Email confirmation links must expire after at least one day.');

        return $days;
    }
}
