<?php

namespace IlBronza\Contacts\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;

class ContactEmailVerificationMail extends Mailable
{
    use Queueable;

    public function __construct(public string $verificationUrl, public int $expiresInDays) {}

    public function build(): static
    {
        return $this->subject(__('contacts::verification.subject'))
            ->view('contacts::emails.verify-contact')
            ->text('contacts::emails.verify-contact-text');
    }
}
