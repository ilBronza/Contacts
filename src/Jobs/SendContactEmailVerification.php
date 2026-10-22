<?php

namespace IlBronza\Contacts\Jobs;

use IlBronza\Contacts\Helpers\ContactEmailConfirmationHelper;
use IlBronza\Contacts\Models\Contact;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use LogicException;

class SendContactEmailVerification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public bool $deleteWhenMissingModels = true;

    protected string $addressFingerprint;

    public function __construct(public Contact $contact)
    {
        if (! $contact->exists || $contact->isDirty(['contact', 'contacttype_slug']))
            throw new LogicException('Save the contact and its email address before requesting confirmation.');

        $this->contact = $contact->withoutRelations();
        $this->addressFingerprint = $this->fingerprint();
        $this->afterCommit();
    }

    public function handle(ContactEmailConfirmationHelper $helper): void
    {
        // SerializesModels reloads the contact: never send to a changed address.
        if (! hash_equals($this->addressFingerprint, $this->fingerprint()))
            return;

        $helper->sendIfNeeded($this->contact);
    }

    private function fingerprint(): string
    {
        return hash('sha256', $this->contact->getRawOriginal('contact') . "\0" . $this->contact->getRawOriginal('contacttype_slug'));
    }
}
