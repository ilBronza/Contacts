<?php

namespace IlBronza\Contacts\Helpers;

use IlBronza\Contacts\Contacts;
use IlBronza\Contacts\Contracts\EmailExistenceVerifier;
use IlBronza\Contacts\Models\Contact;
use LogicException;
use RuntimeException;

class ContactEmailVerificationHelper
{
    public function __construct(protected EmailExistenceVerifier $verifier) {}

    /**
     * Check the individual mailbox through the provider, without saving.
     * Null means that the provider cannot determine whether it exists.
     */
    public function isValid(Contact $contact): ?bool
    {
        $type = $contact->contacttype_slug;

        if ($type !== 'email' && config('contacts.contactTypes.' . $type . '.normalizer') !== 'email')
            return false;

        $email = Contacts::normalizeContact((string) $contact->getContact(), 'email');

        if (! Contacts::isValidEmail($email))
            return false;

        return $this->verifier->verify($email);
    }

    /**
     * Persist a positive flag only when the provider confirms the mailbox.
     * Inconclusive results clear the flag; provider failures leave it untouched.
     */
    public function verify(Contact $contact): ?bool
    {
        if (! $contact->exists || $contact->isDirty(['contact', 'contacttype_slug']))
            throw new LogicException('Save the contact and its email address before verifying it.');

        $result = $this->isValid($contact);
        $verified = $result === true;

        // Lock only after the request completes. Compare in PHP so case-insensitive SQL
        // collations cannot match a different email local part.
        $contact->getConnection()->transaction(function () use ($contact, $verified) {
            $current = $contact->newQuery()->whereKey($contact->getKey())->lockForUpdate()->first();

            if (! $current
                || $current->getRawOriginal('contact') !== $contact->getRawOriginal('contact')
                || $current->getRawOriginal('contacttype_slug') !== $contact->getRawOriginal('contacttype_slug'))
                throw new RuntimeException('The contact changed or was deleted during email verification. Reload it and retry.');

            $current->newQuery()->whereKey($current->getKey())->update(['existence_verified' => $verified]);
        });

        $contact->existence_verified = $verified;
        $contact->syncOriginalAttribute('existence_verified');

        return $result;
    }
}
